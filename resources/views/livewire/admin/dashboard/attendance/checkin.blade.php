<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Attendance Check-in</h1>
        <p class="text-sm text-gray-600">Mark attendance for members. Select a session, choose a member, and mark their status.</p>
    </div>

    {{-- Session Selection --}}
    <x-card>
        <h2 class="text-lg font-semibold mb-4">Select Session</h2>
        
        <div class="grid gap-4 md:grid-cols-2">
            @if(auth()->user()->hasRole('super-admin'))
                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Chapter</label>
                    <select wire:model="chapter" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                        @foreach($chapters ?? [] as $chap)
                            <option value="{{ $chap->name }}">{{ $chap->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="md:col-span-2">
                <label class="text-sm font-medium text-gray-700">Attendance Session</label>
                <select wire:model="selectedSessionId" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                    <option value="">Choose a session...</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}">
                            {{ $session->session_name }} - {{ $session->date->format('M d, Y') }}
                            @if($session->location) ({{ $session->location }}) @endif
                        </option>
                    @endforeach
                </select>
                @if($sessions->isEmpty())
                    <p class="mt-2 text-sm text-amber-600">No open sessions available. Create a session in "Manage Attendance" first.</p>
                @endif
            </div>
        </div>
    </x-card>

    @if($selectedSessionId)
        {{-- Member Selection and Marking --}}
        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Members List --}}
            <x-card class="lg:col-span-2">
                <h2 class="text-lg font-semibold mb-4">Select Member</h2>
                
                {{-- Filters --}}
                <div class="mb-4 grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Search by Name</label>
                        <input type="text" wire:model.live.debounce.300ms="searchName" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" placeholder="Type member name..." />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Filter by Team</label>
                        <select wire:model="filterTeamId" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                            <option value="">All Teams</option>
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Members Table --}}
                <div class="max-h-96 overflow-y-auto">
                    <table class="min-w-full text-sm">
                        <thead class="sticky top-0 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Team</th>
                                <th class="px-4 py-3">Role</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($members as $member)
                                <tr class="hover:bg-gray-50 {{ $selectedUserId == $member->id ? 'bg-blue-50' : '' }}">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $member->name }}</td>
                                    <td class="px-4 py-3 text-gray-600">
                                        @foreach($member->teams as $team)
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                                                {{ $team->name }}
                                            </span>
                                        @endforeach
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $member->role ?? 'Member' }}</td>
                                    <td class="px-4 py-3">
                                        @if(in_array($member->id, $markedUserIds))
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                                Marked
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">Not marked</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if(in_array($member->id, $markedUserIds))
                                            <span class="text-xs text-gray-400">Already marked</span>
                                        @elseif($selectedUserId == $member->id)
                                            <button wire:click="clearSelection" class="text-xs text-amber-600 hover:text-amber-800">
                                                Deselect
                                            </button>
                                        @else
                                            <button wire:click="selectUser({{ $member->id }})" class="text-xs text-blue-600 hover:text-blue-800">
                                                Select
                                            </button>
                                        @endif
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
            </x-card>

            {{-- Marking Panel --}}
            <x-card>
                <h2 class="text-lg font-semibold mb-4">Mark Attendance</h2>
                
                @if($selectedUser)
                    <div class="rounded-lg bg-blue-50 p-4 mb-4">
                        <p class="text-sm font-medium text-gray-500">Selected Member</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $selectedUser->name }}</p>
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">Role:</span> {{ $selectedUser->role ?? 'Member' }}
                        </p>
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">Team:</span> 
                            @foreach($selectedUser->teams as $team)
                                {{ $team->name }}@if(!$loop->last), @endif
                            @endforeach
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700">Time (Optional - Manual Entry)</label>
                            <input type="time" wire:model="manualTime" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" />
                            <p class="mt-1 text-xs text-gray-500">Leave empty if not required</p>
                        </div>

                        <div class="grid gap-3">
                            <button wire:click="markAttendance('present')" class="w-full rounded-lg bg-green-600 px-4 py-3 text-sm font-semibold text-white hover:bg-green-700">
                                ✓ Present
                            </button>
                            <button wire:click="markAttendance('late')" class="w-full rounded-lg bg-amber-600 px-4 py-3 text-sm font-semibold text-white hover:bg-amber-700">
                                ⏰ Late
                            </button>
                            <button wire:click="markAttendance('absent')" class="w-full rounded-lg bg-red-600 px-4 py-3 text-sm font-semibold text-white hover:bg-red-700">
                                ✗ Absent
                            </button>
                        </div>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <svg class="h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <p class="mt-4 text-sm text-gray-500">Select a member from the list to mark attendance</p>
                    </div>
                @endif
            </x-card>
        </div>

        {{-- Session Stats --}}
        <x-card>
            <h2 class="text-lg font-semibold mb-4">Session Summary</h2>
            <div class="grid gap-4 sm:grid-cols-4">
                <div class="rounded-lg bg-green-50 p-4 text-center">
                    <p class="text-2xl font-bold text-green-700">
                        {{ \App\Models\AttendanceRecord::where('attendance_session_id', $selectedSessionId)->where('status', 'present')->count() }}
                    </p>
                    <p class="text-sm text-green-600">Present</p>
                </div>
                <div class="rounded-lg bg-amber-50 p-4 text-center">
                    <p class="text-2xl font-bold text-amber-700">
                        {{ \App\Models\AttendanceRecord::where('attendance_session_id', $selectedSessionId)->where('status', 'late')->count() }}
                    </p>
                    <p class="text-sm text-amber-600">Late</p>
                </div>
                <div class="rounded-lg bg-red-50 p-4 text-center">
                    <p class="text-2xl font-bold text-red-700">
                        {{ \App\Models\AttendanceRecord::where('attendance_session_id', $selectedSessionId)->where('status', 'absent')->count() }}
                    </p>
                    <p class="text-sm text-red-600">Absent</p>
                </div>
                <div class="rounded-lg bg-blue-50 p-4 text-center">
                    <p class="text-2xl font-bold text-blue-700">
                        {{ \App\Models\AttendanceRecord::where('attendance_session_id', $selectedSessionId)->count() }}
                    </p>
                    <p class="text-sm text-blue-600">Total Marked</p>
                </div>
            </div>
        </x-card>
    @endif
</div>
