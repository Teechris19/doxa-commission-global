<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Team Members</h1>
            <p class="text-sm text-gray-600">View and manage all members across teams.</p>
        </div>

        <button wire:click="$set('showCreateModal', true)" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            + Add New Member
        </button>
    </div>

    <x-card>
        {{-- Filters --}}
        <div class="mb-6 grid gap-4 md:grid-cols-3">
            @if(auth()->user()->hasRole('super-admin'))
                <div>
                    <label class="text-sm font-medium text-gray-700">Chapter</label>
                    <select wire:model.live="chapter" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                        @foreach($chapters as $chap)
                            <option value="{{ $chap->name }}">{{ $chap->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label class="text-sm font-medium text-gray-700">Search by Name</label>
                <input type="text" wire:model.live.debounce.300ms="searchName" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" placeholder="Type member name..." />
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Filter by Team</label>
                <select wire:model.live="filterTeamId" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                    <option value="">All Teams</option>
                    @foreach($teams as $team)
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Members Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Phone</th>
                        <th class="px-4 py-3">Team(s)</th>
                        <th class="px-4 py-3">Role</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($members as $member)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $member->name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $member->email ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $member->phone ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @if($member->teams->isNotEmpty())
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($member->teams as $team)
                                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                                {{ $team->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-gray-400">No team assigned</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                                    {{ $member->role ?? 'Member' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                No members found. Adjust your search or filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $members->links() }}
        </div>
    </x-card>

    {{-- Create Member Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" wire:click="$set('showCreateModal', false)">
            <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl" wire:click.stop>
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold text-gray-900">Add New Member</h2>
                    <button wire:click="$set('showCreateModal', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-gray-700">Full Name *</label>
                        <input type="text" wire:model="newMemberName" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" placeholder="e.g., John Doe" />
                        @error('newMemberName')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Email</label>
                        <input type="email" wire:model="newMemberEmail" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" placeholder="john@example.com" />
                        @error('newMemberEmail')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Phone</label>
                        <input type="text" wire:model="newMemberPhone" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" placeholder="+234 ..." />
                        @error('newMemberPhone')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Password</label>
                        <input type="password" wire:model="newMemberPassword" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" placeholder="Leave empty for default" />
                        @error('newMemberPassword')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Default: "password" if left empty</p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Role</label>
                        <select wire:model="newMemberRole" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                            <option value="Member">Member</option>
                            <option value="Team Lead">Team Lead</option>
                            <option value="Assistant">Assistant</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-gray-700">Assign to Teams</label>
                        <div class="mt-2 max-h-40 overflow-y-auto rounded-lg border border-gray-200 p-3">
                            @forelse($teams as $team)
                                <label class="flex items-center gap-2 py-1.5">
                                    <input type="checkbox" value="{{ $team->id }}" wire:model="selectedTeams" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                    <span class="text-sm text-gray-700">{{ $team->name }}</span>
                                </label>
                            @empty
                                <p class="text-sm text-gray-500">No teams available. Create a team first.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button wire:click="$set('showCreateModal', false)" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button wire:click="createMember" class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Create Member
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
