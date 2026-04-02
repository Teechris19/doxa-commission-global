<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Subunit Management</h1>
            <p class="text-sm text-gray-600">Create subunits, assign leaders, and manage members.</p>
        </div>

        <button wire:click="$set('showCreateSubunit', true)" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            + Create Subunit
        </button>
    </div>

    @if($team)
        <x-card>
            <h2 class="text-lg font-semibold mb-2">Your Team: {{ $team->name }}</h2>
            <p class="text-sm text-gray-600">Manage subunits within your team. Each subunit must have a leader and can have multiple members.</p>
        </x-card>

        {{-- Subunits List --}}
        <div class="grid gap-6 md:grid-cols-2">
            @forelse($subunits as $subunit)
                <x-card>
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $subunit->name }}</h3>
                            @if($subunit->description)
                                <p class="mt-1 text-sm text-gray-600">{{ $subunit->description }}</p>
                            @endif
                        </div>
                        <button wire:click="deleteSubunit({{ $subunit->id }})" class="text-red-600 hover:text-red-800">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>

                    {{-- Leader Section --}}
                    <div class="mt-4 rounded-lg bg-blue-50 p-3">
                        <p class="text-xs font-semibold uppercase tracking-wider text-blue-600">Subunit Leader</p>
                        @if($subunit->leader)
                            <div class="mt-2 flex items-center justify-between">
                                <p class="text-sm font-medium text-gray-900">{{ $subunit->leader->name }}</p>
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                    Leader
                                </span>
                            </div>
                        @else
                            <p class="mt-2 text-sm text-gray-500">No leader assigned</p>
                        @endif

                        {{-- Assign Leader Dropdown --}}
                        <div class="mt-2">
                            <select
                                wire:change="assignLeader({{ $subunit->id }}, $event.target.value)"
                                class="w-full rounded-lg border border-gray-300 px-2 py-1 text-sm"
                            >
                                <option value="">Assign Leader...</option>
                                @foreach($teamMembers as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Members Section --}}
                    <div class="mt-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Members ({{ $subunit->members->count() }})</p>
                        <div class="mt-2 space-y-1">
                            @forelse($subunit->members as $member)
                                <div class="flex items-center justify-between rounded bg-gray-50 px-2 py-1">
                                    <span class="text-sm text-gray-700">{{ $member->name }}</span>
                                    @if($member->id == $subunit->leader_id)
                                        <span class="text-xs text-green-600">Leader</span>
                                    @else
                                        <button wire:click="removeMember({{ $subunit->id }}, {{ $member->id }})" class="text-xs text-red-600 hover:text-red-800">
                                            Remove
                                        </button>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">No members assigned</p>
                            @endforelse
                        </div>

                        {{-- Add Members --}}
                        <div class="mt-3">
                            <p class="text-xs font-medium text-gray-600">Add Members:</p>
                            <div class="mt-1 max-h-32 overflow-y-auto rounded border border-gray-200 p-2">
                                @foreach($teamMembers as $member)
                                    <label class="flex items-center gap-2 py-1">
                                        <input
                                            type="checkbox"
                                            value="{{ $member->id }}"
                                            wire:model="memberIds"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span class="text-sm text-gray-700">{{ $member->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <button
                                wire:click="addMembersToSubunit"
                                wire:loading.attr="disabled"
                                class="mt-2 w-full rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700"
                            >
                                Add Selected Members
                            </button>
                        </div>
                    </div>
                </x-card>
            @empty
                <div class="md:col-span-2">
                    <x-card>
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <svg class="h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">No Subunits Yet</h3>
                            <p class="mt-2 text-sm text-gray-500">Create your first subunit to organize your team members.</p>
                            <button wire:click="$set('showCreateSubunit', true)" class="mt-4 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                Create Subunit
                            </button>
                        </div>
                    </x-card>
                </div>
            @endforelse
        </div>
    @else
        <x-card>
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <svg class="h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No Team Assigned</h3>
                <p class="mt-2 text-sm text-gray-500">You need to be assigned to a team to manage subunits.</p>
            </div>
        </x-card>
    @endif

    {{-- Create Subunit Modal --}}
    @if($showCreateSubunit)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" wire:click="$set('showCreateSubunit', false)">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl" wire:click.stop>
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Create Subunit</h2>
                    <button wire:click="$set('showCreateSubunit', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Subunit Name</label>
                        <input type="text" wire:model="subunitName" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" placeholder="e.g., Ushers Group A" />
                        @error('subunitName')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Description (Optional)</label>
                        <textarea wire:model="subunitDescription" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" placeholder="Brief description of this subunit"></textarea>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Assign Leader</label>
                        <select wire:model="subunitLeaderId" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                            <option value="">Select a leader...</option>
                            @foreach($teamMembers as $member)
                                <option value="{{ $member->id }}">{{ $member->name }}</option>
                            @endforeach
                        </select>
                        @error('subunitLeaderId')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button wire:click="$set('showCreateSubunit', false)" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button wire:click="createSubunit" class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Create Subunit
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
