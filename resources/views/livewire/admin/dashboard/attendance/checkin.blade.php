<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Attendance Check-in</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">Mark attendance for members and guests</p>
    </div>

    {{-- Session Selection --}}
    <x-card>
        <div class="grid gap-4 md:grid-cols-2">
            @if(auth()->user()->hasRole('super-admin'))
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Chapter</label>
                    <select wire:model.live="chapter" class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-white">
                        @foreach($chapters ?? [] as $chap)
                            <option value="{{ $chap->name }}">{{ $chap->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Select Session</label>
                <select wire:model.live="selectedSessionId" class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-white">
                    <option value="">-- Choose a session --</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}">
                            {{ $session->session_name }} - {{ $session->date->format('M d, Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($selectedSessionId)
            <div class="mt-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-full bg-blue-100 dark:bg-blue-800 p-2">
                        <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $sessions->find($selectedSessionId)?->session_name }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $sessions->find($selectedSessionId)?->date?->format('l, M d, Y') }}</p>
                    </div>
                </div>
            </div>
        @endif
    </x-card>

    @if($selectedSessionId)
        <div class="grid gap-6 lg:grid-cols-3">
            {{-- LEFT: Member List --}}
            <div class="lg:col-span-2">
                <x-card>
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Member List</h2>
                        <span class="rounded-full bg-blue-100 dark:bg-blue-900/30 px-3 py-1 text-xs font-semibold text-blue-700 dark:text-blue-400">
                            {{ count($members) }} members
                        </span>
                    </div>

                    {{-- Search & Filter --}}
                    <div class="mb-4 grid gap-3 sm:grid-cols-2">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" wire:model.live.debounce.300ms="searchName" placeholder="Search by name..." class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 py-2 pl-10 pr-4 text-gray-900 dark:text-white" />
                        </div>
                        
                        <select wire:model.live="filterTeamId" class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-gray-900 dark:text-white">
                            <option value="">All Teams</option>
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Members Table --}}
                    <div class="max-h-[600px] overflow-y-auto">
                        <table class="min-w-full">
                            <thead class="sticky top-0 bg-gray-50 dark:bg-gray-800 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">Member</th>
                                    <th class="px-4 py-3">Team</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($members as $member)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 {{ in_array($member->id, $markedUserIds) ? 'bg-green-50 dark:bg-green-900/10' : '' }}">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-purple-600 text-sm font-bold text-white">
                                                    {{ strtoupper(substr($member->name, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <p class="font-medium text-gray-900 dark:text-white">{{ $member->name }}</p>
                                                    @if($member->email)
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $member->email }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($member->teams->isNotEmpty())
                                                <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:text-gray-300">
                                                    {{ $member->teams->first()->name }}
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400">No team</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if(in_array($member->id, $markedUserIds))
                                                <span class="inline-flex items-center gap-1 rounded-full bg-green-100 dark:bg-green-900/30 px-2.5 py-1 text-xs font-semibold text-green-700 dark:text-green-400">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                    </svg>
                                                    Checked In
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400">Not marked</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            @if(in_array($member->id, $markedUserIds))
                                                <span class="text-xs text-gray-400">Already marked</span>
                                            @else
                                                <div class="flex justify-end gap-1">
                                                    <button wire:click="markMemberAttendance({{ $member->id }}, 'present')" title="Mark Present" class="rounded bg-green-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-green-700">
                                                        ✓
                                                    </button>
                                                    <button wire:click="markMemberAttendance({{ $member->id }}, 'late')" title="Mark Late" class="rounded bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-amber-700">
                                                        ⏰
                                                    </button>
                                                    <button wire:click="markMemberAttendance({{ $member->id }}, 'absent')" title="Mark Absent" class="rounded bg-red-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-red-700">
                                                        ✗
                                                    </button>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-12 text-center text-gray-500 dark:text-gray-400">
                                            <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            <p class="mt-2">No members found</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            {{-- RIGHT: Quick Actions --}}
            <div class="space-y-6">
                {{-- Guest Check-in --}}
                <x-card>
                    <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Guest Check-in</h2>
                    
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Guest Name *</label>
                            <input type="text" wire:model="guestName" class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-white" placeholder="e.g., John Visitor" />
                            @error('guestName')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                            <input type="text" wire:model="guestPhone" class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-white" placeholder="08012345678" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input type="email" wire:model="guestEmail" class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-white" placeholder="guest@example.com" />
                            @error('guestEmail')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Time</label>
                            <input type="time" wire:model="guestTime" class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                            <select wire:model="guestStatus" class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-white">
                                <option value="present">Present</option>
                                <option value="late">Late</option>
                                <option value="absent">Absent</option>
                            </select>
                        </div>
                        <button wire:click="markGuestAttendance" class="mt-2 w-full rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-700">
                            ✓ Check-in Guest
                        </button>
                    </div>
                </x-card>

                {{-- Session Stats --}}
                <x-card>
                    <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Session Stats</h2>
                    
                    <div class="space-y-3">
                        <div class="flex items-center justify-between rounded-lg bg-green-50 dark:bg-green-900/20 p-3">
                            <div class="flex items-center gap-2">
                                <div class="rounded-full bg-green-100 dark:bg-green-800 p-1.5">
                                    <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Present</span>
                            </div>
                            <span class="text-lg font-bold text-green-700 dark:text-green-400">
                                {{ \App\Models\AttendanceRecord::where('attendance_session_id', $selectedSessionId)->where('status', 'present')->count() }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between rounded-lg bg-amber-50 dark:bg-amber-900/20 p-3">
                            <div class="flex items-center gap-2">
                                <div class="rounded-full bg-amber-100 dark:bg-amber-800 p-1.5">
                                    <svg class="h-4 w-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Late</span>
                            </div>
                            <span class="text-lg font-bold text-amber-700 dark:text-amber-400">
                                {{ \App\Models\AttendanceRecord::where('attendance_session_id', $selectedSessionId)->where('status', 'late')->count() }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between rounded-lg bg-red-50 dark:bg-red-900/20 p-3">
                            <div class="flex items-center gap-2">
                                <div class="rounded-full bg-red-100 dark:bg-red-800 p-1.5">
                                    <svg class="h-4 w-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Absent</span>
                            </div>
                            <span class="text-lg font-bold text-red-700 dark:text-red-400">
                                {{ \App\Models\AttendanceRecord::where('attendance_session_id', $selectedSessionId)->where('status', 'absent')->count() }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between rounded-lg bg-blue-50 dark:bg-blue-900/20 p-3">
                            <div class="flex items-center gap-2">
                                <div class="rounded-full bg-blue-100 dark:bg-blue-800 p-1.5">
                                    <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Guests</span>
                            </div>
                            <span class="text-lg font-bold text-blue-700 dark:text-blue-400">{{ $guestCount }}</span>
                        </div>

                        <div class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Total Marked</span>
                                <span class="text-xl font-bold text-gray-900 dark:text-white">
                                    {{ \App\Models\AttendanceRecord::where('attendance_session_id', $selectedSessionId)->count() + $guestCount }}
                                </span>
                            </div>
                        </div>
                    </div>
                </x-card>
            </div>
        </div>
    @endif
</div>
