<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Manage Attendance</h1>
        <p class="text-sm text-gray-600">Create attendance sessions for services, events, or custom gatherings.</p>
    </div>

    {{-- Create Session Card --}}
    <x-card>
        <h2 class="text-lg font-semibold mb-4">Create New Session</h2>
        
        <div class="grid gap-4 md:grid-cols-2">
            @if(auth()->user()->hasRole('super-admin'))
                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Chapter</label>
                    <select wire:model="chapter" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                        @foreach($chapters as $chap)
                            <option value="{{ $chap->name }}">{{ $chap->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label class="text-sm font-medium text-gray-700">Session Date</label>
                <input type="date" wire:model="sessionDate" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" />
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Session Type</label>
                <select wire:model="sessionType" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                    <option value="service">Service</option>
                    <option value="event">Event</option>
                    <option value="custom">Custom</option>
                </select>
            </div>

            @if($sessionType === 'service')
                <div class="md:col-span-2" wire:key="service-select">
                    <label class="text-sm font-medium text-gray-700">Select Service</label>
                    <select wire:model="selectedServiceId" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                        <option value="">Choose a service...</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}">{{ $service->name }} ({{ $service->time }})</option>
                        @endforeach
                    </select>
                </div>
            @elseif($sessionType === 'event')
                <div class="md:col-span-2" wire:key="event-select">
                    <label class="text-sm font-medium text-gray-700">Select Event</label>
                    <select wire:model="selectedEventId" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                        <option value="">Choose an event...</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}">{{ $event->title }} - {{ $event->event_date?->format('M d, Y') }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="md:col-span-2" wire:key="custom-name">
                    <label class="text-sm font-medium text-gray-700">Custom Session Name</label>
                    <input type="text" wire:model="customName" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" placeholder="e.g., Sunday 1st Service, Youth Meeting" />
                </div>
                <div class="md:col-span-2" wire:key="custom-location">
                    <label class="text-sm font-medium text-gray-700">Location (Optional)</label>
                    <input type="text" wire:model="customLocation" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" placeholder="e.g., Main Auditorium" />
                </div>
            @endif

            <div class="md:col-span-2">
                <button wire:click="createSession" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Create Session
                </button>
            </div>
        </div>
    </x-card>

    {{-- Sessions List --}}
    <x-card>
        <h2 class="text-lg font-semibold mb-4">Attendance Sessions</h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Session Name</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Location</th>
                        <th class="px-4 py-3">Records</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($sessions as $session)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $session->session_name }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                    {{ $session->session_type === 'service' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $session->session_type === 'event' ? 'bg-purple-100 text-purple-800' : '' }}
                                    {{ $session->session_type === 'custom' ? 'bg-gray-100 text-gray-800' : '' }}">
                                    {{ ucfirst($session->session_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $session->date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $session->location ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="text-sm font-medium {{ $session->records_count > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                    {{ $session->records_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                    {{ $session->status === 'open' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ ucfirst($session->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    @if($session->status === 'open')
                                        <button wire:click="closeSession({{ $session->id }})" class="text-xs text-amber-600 hover:text-amber-800">
                                            Close
                                        </button>
                                    @else
                                        <button wire:click="reopenSession({{ $session->id }})" class="text-xs text-blue-600 hover:text-blue-800">
                                            Reopen
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                No attendance sessions created yet. Create your first session above.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $sessions->links() }}
        </div>
    </x-card>
</div>
