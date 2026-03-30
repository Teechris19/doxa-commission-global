<div class="space-y-6">
    <x-fancy-header title="Conclaves" subtitle="Manage Doxite conclaves (geographic areas)" :breadcrumbs="[
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'Conclaves']
    ]">
        <x-button wire:click="create" icon="plus" class="bg-blue-600 hover:bg-blue-700">Add Conclave</x-button>
    </x-fancy-header>

    <x-card>
        <div class="mb-4 flex items-center justify-between gap-3">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                placeholder="Search conclaves..." 
                icon="magnifying-glass"
                class="max-w-sm"
            />
            <select wire:model.live="perPage" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="10">10 per page</option>
                <option value="25">25 per page</option>
                <option value="50">50 per page</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($conclaves as $conclave)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                @if($conclave->image)
                                    <img src="{{ Storage::url($conclave->image) }}" alt="{{ $conclave->name }}" class="h-12 w-12 rounded object-cover">
                                @else
                                    <div class="h-12 w-12 rounded bg-gray-200 flex items-center justify-center">
                                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        </svg>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $conclave->name }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-600">{{ $conclave->location }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if($conclave->phone)
                                    <div class="text-sm text-gray-600">{{ $conclave->phone }}</div>
                                @endif
                                @if($conclave->email)
                                    <div class="text-sm text-gray-600">{{ $conclave->email }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $conclave->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $conclave->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <x-button wire:click="edit({{ $conclave->id }})" icon="pencil" variant="ghost" size="sm">Edit</x-button>
                                    <x-button wire:click="delete({{ $conclave->id }})" icon="trash" variant="ghost" size="sm" class="text-red-600 hover:text-red-700">Delete</x-button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                No conclaves found. Click "Add Conclave" to create one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $conclaves->links() }}
        </div>
    </x-card>

    {{-- Modal --}}
    <flux:modal wire:model="showModal" class="max-w-2xl">
        <div class="space-y-4">
            <h2 class="text-xl font-semibold">{{ $conclaveId ? 'Edit' : 'Create' }} Conclave</h2>
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input wire:model="name" label="Conclave Name *" type="text" placeholder="e.g., Abuja Conclave" />
                <flux:input wire:model="location" label="Location *" type="text" placeholder="e.g., Abuja" />
            </div>

            <flux:textarea wire:model="description" label="Description" rows="3" placeholder="Description about this conclave..." />
            <flux:input wire:model="address" label="Address" type="text" placeholder="Full address" />

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input wire:model="phone" label="Phone" type="text" placeholder="Contact number" />
                <flux:input wire:model="email" label="Email" type="email" placeholder="Contact email" />
            </div>

            <flux:input wire:model="whatsapp_link" label="WhatsApp Group Link" type="url" placeholder="https://chat.whatsapp.com/INVITE_CODE" />

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input wire:model="latitude" label="Latitude" type="text" placeholder="e.g., 9.0765" />
                <flux:input wire:model="longitude" label="Longitude" type="text" placeholder="e.g., 7.3986" />
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Conclave Image</label>
                @if($existingImage && !$image)
                    <div class="mb-2">
                        <img src="{{ Storage::url($existingImage) }}" alt="Conclave" class="h-32 rounded object-cover">
                    </div>
                @endif
                @if($image)
                    <div class="mb-2">
                        <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="h-32 rounded object-cover">
                    </div>
                @endif
                <input type="file" wire:model="image" accept="image/*" class="w-full rounded-lg border px-3 py-2">
                <p class="mt-1 text-xs text-gray-500">Recommended: 600x400px. Max: 5MB</p>
            </div>

            <div class="flex items-center gap-2">
                <flux:checkbox wire:model="isActive" label="Active" />
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <flux:button wire:click="$set('showModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="save" class="bg-blue-600 hover:bg-blue-700">Save Conclave</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
