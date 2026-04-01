<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Team Members</h1>
        <p class="text-sm text-gray-600">View and manage all members across teams.</p>
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
</div>
