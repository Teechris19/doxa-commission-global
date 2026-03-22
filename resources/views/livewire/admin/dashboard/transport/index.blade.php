<?php

use App\Models\Chapter;
use App\Models\PickupLocation;
use App\Models\Transport;
use App\Notifications\TransportRequestUpdated;
use App\Services\NotificationRecipients;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions;
    use WithPagination;

    #[Url]
    public ?string $chapter = null;

    public ?int $chapterId = null;
    public ?string $chapterName = null;

    public ?string $search = null;
    public ?string $status = null;
    public int $quantity = 15;

    public ?int $locationId = null;
    public ?string $locationName = null;
    public ?string $locationAddress = null;
    public ?string $locationDescription = null;
    public ?string $locationContactPerson = null;
    public ?string $locationContactPhone = null;
    public ?string $locationPickupTime = null;
    public ?string $locationLatitude = null;
    public ?string $locationLongitude = null;
    public bool $locationIsActive = true;

    public ?int $selectedTransportId = null;
    public ?string $transportName = null;
    public ?string $transportPhone = null;
    public ?string $transportPickupLocationText = null;
    public ?int $transportPickupLocationId = null;
    public ?string $transportStatus = 'pending';
    public ?string $transportNotes = null;

    public function mount(): void
    {
        // Restrict access to team-leads (unless they are on the transport team)
        if (auth()->user()->hasRole('team-lead')) {
            $isTransportTeam = auth()->user()->teams()->whereHas('functions', fn($q) => $q->where('function', 'transport'))->exists();
            if (!$isTransportTeam) {
                abort(403, 'You do not have permission to access this page.');
            }
        }
        
        $this->resolveChapterContext();
    }

    public function with(): array
    {
        $requestsQuery = Transport::query()
            ->with('pickupLocation')
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->search, function ($q): void {
                $searchTerm = '%' . $this->search . '%';
                $q->where(function ($inner) use ($searchTerm): void {
                    $inner->where('name', 'like', $searchTerm)
                        ->orWhere('phone', 'like', $searchTerm)
                        ->orWhere('pickup_location', 'like', $searchTerm)
                        ->orWhereHas('pickupLocation', function ($locationQuery) use ($searchTerm): void {
                            $locationQuery->where('name', 'like', $searchTerm)
                                ->orWhere('address', 'like', $searchTerm);
                        });
                });
            });

        return [
            'pickupLocations' => PickupLocation::query()
                ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
                ->orderBy('pickup_time')
                ->orderBy('name')
                ->get(),
            'transportRequests' => $requestsQuery
                ->latest()
                ->paginate($this->quantity, ['*'], 'requestsPage')
                ->withQueryString(),
            'requestStats' => [
                'pending' => (clone $requestsQuery)->where('status', 'pending')->count(),
                'approved' => (clone $requestsQuery)->where('status', 'approved')->count(),
                'rejected' => (clone $requestsQuery)->where('status', 'rejected')->count(),
            ],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage('requestsPage');
    }

    public function updatingStatus(): void
    {
        $this->resetPage('requestsPage');
    }

    public function updatedQuantity(): void
    {
        $this->resetPage('requestsPage');
    }

    public function createPickupLocation(): void
    {
        $this->resetPickupLocationForm();
    }

    public function editPickupLocation(int $id): void
    {
        $location = PickupLocation::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->findOrFail($id);

        $this->locationId = $location->id;
        $this->locationName = $location->name;
        $this->locationAddress = $location->address;
        $this->locationDescription = $location->description;
        $this->locationContactPerson = $location->contact_person;
        $this->locationContactPhone = $location->contact_phone;
        $this->locationPickupTime = $this->normalizeTimeForInput($location->pickup_time);
        $this->locationLatitude = $location->latitude !== null ? (string) $location->latitude : null;
        $this->locationLongitude = $location->longitude !== null ? (string) $location->longitude : null;
        $this->locationIsActive = (bool) $location->is_active;
    }

    public function savePickupLocation(): void
    {
        if (!$this->chapterId) {
            $this->toast()->error('No Chapter', 'Select a chapter before creating pickup locations.')->send();
            return;
        }

        $validated = $this->validate([
            'locationName' => 'required|string|max:255',
            'locationAddress' => 'nullable|string|max:255',
            'locationDescription' => 'nullable|string|max:1000',
            'locationContactPerson' => 'nullable|string|max:255',
            'locationContactPhone' => 'nullable|string|max:20',
            'locationPickupTime' => 'required|date_format:H:i',
            'locationLatitude' => 'nullable|numeric|between:-90,90',
            'locationLongitude' => 'nullable|numeric|between:-180,180',
            'locationIsActive' => 'boolean',
        ]);

        $payload = [
            'name' => $validated['locationName'],
            'address' => $validated['locationAddress'] ?? null,
            'description' => $validated['locationDescription'] ?? null,
            'contact_person' => $validated['locationContactPerson'] ?? null,
            'contact_phone' => $validated['locationContactPhone'] ?? null,
            'pickup_time' => $validated['locationPickupTime'],
            'latitude' => $validated['locationLatitude'] ?? null,
            'longitude' => $validated['locationLongitude'] ?? null,
            'is_active' => (bool) ($validated['locationIsActive'] ?? true),
            'chapter_id' => $this->chapterId,
        ];

        if ($this->locationId) {
            $location = PickupLocation::query()
                ->where('chapter_id', $this->chapterId)
                ->findOrFail($this->locationId);
            $location->update($payload);
            $this->toast()->success('Updated', 'Pickup location updated successfully.')->send();
        } else {
            PickupLocation::create($payload);
            $this->toast()->success('Created', 'Pickup location created successfully.')->send();
        }

        $this->resetPickupLocationForm();
        $this->dispatch('$closeModal', 'pickup-location-modal');
    }

    public function togglePickupLocationStatus(int $id): void
    {
        $location = PickupLocation::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->findOrFail($id);

        $location->is_active = !$location->is_active;
        $location->save();

        $this->toast()->success('Updated', 'Pickup location status changed successfully.')->send();
    }

    public function removePickupLocation(int $id): void
    {
        $location = PickupLocation::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->findOrFail($id);

        if ($location->transportRequests()->where('status', 'pending')->exists()) {
            $this->toast()->error('Blocked', 'Cannot delete a location with pending transport requests.')->send();
            return;
        }

        Transport::where('pickup_location_id', $location->id)->update(['pickup_location_id' => null]);
        $location->delete();

        $this->toast()->success('Deleted', 'Pickup location deleted successfully.')->send();
    }

    public function loadTransport(int $id): void
    {
        $request = Transport::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->findOrFail($id);

        $this->selectedTransportId = $request->id;
        $this->transportName = $request->name;
        $this->transportPhone = $request->phone;
        $this->transportPickupLocationText = $request->pickup_location;
        $this->transportPickupLocationId = $request->pickup_location_id;
        $this->transportStatus = $request->status;
        $this->transportNotes = $request->notes;
    }

    public function saveTransport(): void
    {
        if (!$this->selectedTransportId) {
            return;
        }

        $validated = $this->validate([
            'transportStatus' => 'required|in:pending,approved,rejected',
            'transportNotes' => 'nullable|string|max:1000',
            'transportPickupLocationId' => 'nullable|integer|exists:pickup_locations,id',
        ]);

        $request = Transport::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->findOrFail($this->selectedTransportId);

        $pickupLocationText = $request->pickup_location;
        $pickupTime = $request->pickup_time;

        if (!empty($validated['transportPickupLocationId'])) {
            $pickup = PickupLocation::query()
                ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
                ->find($validated['transportPickupLocationId']);

            if ($pickup) {
                $pickupLocationText = $pickup->address ?: $pickup->name;
                $pickupTime = $pickup->pickup_time;
            }
        }

        $request->update([
            'pickup_location_id' => $validated['transportPickupLocationId'] ?? null,
            'pickup_location' => $pickupLocationText,
            'pickup_time' => $pickupTime,
            'status' => $validated['transportStatus'],
            'notes' => $validated['transportNotes'] ?? null,
            'processed_at' => $validated['transportStatus'] === 'pending' ? null : now(),
        ]);

        $recipients = (new NotificationRecipients())
            ->forFunctionAndChapter('transport', $request->chapter_id);

        foreach ($recipients as $recipient) {
            $recipient->notify(new TransportRequestUpdated($request, $validated['transportStatus']));
        }

        $this->toast()->success('Updated', 'Transport request updated successfully.')->send();
        $this->dispatch('$closeModal', 'transport-request-modal');
    }

    public function changeStatus(int $id, string $status): void
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            return;
        }

        $request = Transport::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->findOrFail($id);

        $request->update([
            'status' => $status,
            'processed_at' => now(),
        ]);

        $recipients = (new NotificationRecipients())
            ->forFunctionAndChapter('transport', $request->chapter_id);

        foreach ($recipients as $recipient) {
            $recipient->notify(new TransportRequestUpdated($request, $status));
        }

        $this->toast()->success('Done', 'Transport request status updated.')->send();
    }

    public function deleteTransport(int $id): void
    {
        $this->dialog()
            ->error('Are you sure you want to delete this transport request?')
            ->hook([
                'ok' => [
                    'method' => 'confirmDeleteTransport',
                    'params' => [$id],
                ],
            ])
            ->send();
    }

    public function confirmDeleteTransport(int $id): void
    {
        $request = Transport::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->findOrFail($id);

        $request->delete();
        $this->toast()->success('Deleted', 'Transport request deleted successfully.')->send();
    }

    private function resetPickupLocationForm(): void
    {
        $this->reset([
            'locationId',
            'locationName',
            'locationAddress',
            'locationDescription',
            'locationContactPerson',
            'locationContactPhone',
            'locationPickupTime',
            'locationLatitude',
            'locationLongitude',
        ]);

        $this->locationIsActive = true;
    }

    private function resolveChapterContext(): void
    {
        $chapter = null;

        if ($this->chapter) {
            $chapter = Chapter::where('name', e($this->chapter))->first();
        }

        if (!$chapter) {
            $user = auth()->user();
            if ($user?->chapter_id) {
                $chapter = Chapter::find($user->chapter_id);
            }
        }

        $this->chapterId = $chapter?->id;
        $this->chapterName = $chapter?->name;
    }

    private function normalizeTimeForInput(?string $time): ?string
    {
        if (!$time) {
            return null;
        }

        foreach (['H:i:s', 'H:i'] as $format) {
            $dateTime = \DateTime::createFromFormat($format, $time);
            if ($dateTime !== false) {
                return $dateTime->format('H:i');
            }
        }

        return null;
    }

    public function formatDisplayTime(?string $time): string
    {
        if (!$time) {
            return 'Not set';
        }

        foreach (['H:i:s', 'H:i'] as $format) {
            $dateTime = \DateTime::createFromFormat($format, $time);
            if ($dateTime !== false) {
                return $dateTime->format('g:i A');
            }
        }

        return $time;
    }

}; ?>

<div>
    <x-fancy-header
        title="Transport Management"
        subtitle="Manage chapter pickup locations and transport requests"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Transportation']
        ]"
    />

    <div class="mb-6 flex flex-wrap items-center gap-3 text-sm">
        <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-blue-700">
            Chapter: {{ $chapterName ?? 'Not selected' }}
        </span>
        <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-amber-700">
            Pending: {{ $requestStats['pending'] ?? 0 }}
        </span>
        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-emerald-700">
            Approved: {{ $requestStats['approved'] ?? 0 }}
        </span>
        <span class="rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-rose-700">
            Rejected: {{ $requestStats['rejected'] ?? 0 }}
        </span>
    </div>

    <x-modal id="pickup-location-modal" :title="$locationId ? 'Edit Pickup Location' : 'Create Pickup Location'" size="2xl">
        <form wire:submit.prevent="savePickupLocation" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Location Name</label>
                    <input wire:model.lazy="locationName" type="text" class="w-full rounded-lg border px-3 py-2" />
                    @error('locationName') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Pickup Time</label>
                    <input wire:model.lazy="locationPickupTime" type="time" class="w-full rounded-lg border px-3 py-2" />
                    @error('locationPickupTime') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Address</label>
                <input id="locationAddress" wire:model.lazy="locationAddress" type="text" class="w-full rounded-lg border px-3 py-2" placeholder="Street, area, city" />
                @error('locationAddress') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Description</label>
                <textarea wire:model.lazy="locationDescription" rows="2" class="w-full rounded-lg border px-3 py-2" placeholder="Landmark or directions"></textarea>
                @error('locationDescription') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Contact Person</label>
                    <input wire:model.lazy="locationContactPerson" type="text" class="w-full rounded-lg border px-3 py-2" />
                    @error('locationContactPerson') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Contact Phone</label>
                    <input wire:model.lazy="locationContactPhone" type="text" class="w-full rounded-lg border px-3 py-2" />
                    @error('locationContactPhone') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Latitude</label>
                    <input id="locationLatitude" wire:model.lazy="locationLatitude" type="text" readonly class="w-full rounded-lg border bg-zinc-50 px-3 py-2" placeholder="Pick from map" />
                    @error('locationLatitude') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Longitude</label>
                    <input id="locationLongitude" wire:model.lazy="locationLongitude" type="text" readonly class="w-full rounded-lg border bg-zinc-50 px-3 py-2" placeholder="Pick from map" />
                    @error('locationLongitude') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div wire:ignore class="rounded-xl border border-blue-100 bg-blue-50/40 p-3">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-700">Map Picker</p>
                    <button
                        id="pickup-map-use-location"
                        type="button"
                        class="rounded-md border border-blue-200 bg-white px-2 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-blue-700"
                    >
                        Use Current Location
                    </button>
                </div>
                <div class="mb-3 grid grid-cols-1 gap-2 sm:grid-cols-[1fr_auto]">
                    <input
                        id="pickup-map-search-input"
                        type="search"
                        class="w-full rounded-md border border-blue-200 bg-white px-3 py-2 text-sm"
                        placeholder="Search for a location or address"
                        autocomplete="off"
                    />
                    <button
                        id="pickup-map-search-btn"
                        type="button"
                        class="rounded-md border border-blue-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-blue-700"
                    >
                        Search
                    </button>
                </div>
                <div id="pickup-map-search-results" class="mb-3 hidden space-y-2"></div>
                <div id="pickup-admin-map" class="h-64 w-full overflow-hidden rounded-lg border border-blue-100 bg-white"></div>
                <p id="pickup-map-status" class="mt-2 text-xs text-zinc-600">Search for a place, click map, or use current location to set pickup coordinates.</p>
            </div>

            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="locationIsActive" class="rounded border-zinc-300" />
                Active location
            </label>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" x-on:click="$modalClose('pickup-location-modal')" class="rounded-lg border px-4 py-2 text-sm">Cancel</button>
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Save Location</button>
            </div>
        </form>
    </x-modal>

    <x-modal id="transport-request-modal" title="Transport Request Details" size="xl">
        <form wire:submit.prevent="saveTransport" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Requester</label>
                    <input type="text" readonly wire:model="transportName" class="w-full rounded-lg border bg-zinc-50 px-3 py-2" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Phone</label>
                    <input type="text" readonly wire:model="transportPhone" class="w-full rounded-lg border bg-zinc-50 px-3 py-2" />
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Assigned Pickup Location</label>
                <select wire:model="transportPickupLocationId" class="w-full rounded-lg border px-3 py-2">
                    <option value="">Unassigned</option>
                    @foreach($pickupLocations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }} ({{ $this->formatDisplayTime($location->pickup_time) }})</option>
                    @endforeach
                </select>
                @error('transportPickupLocationId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                @if($transportPickupLocationText)
                    <p class="mt-2 text-xs text-slate-500">Current request location: {{ $transportPickupLocationText }}</p>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Status</label>
                    <select wire:model="transportStatus" class="w-full rounded-lg border px-3 py-2">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    @error('transportStatus') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Notes</label>
                    <textarea wire:model="transportNotes" rows="2" class="w-full rounded-lg border px-3 py-2" placeholder="Optional notes for transport team"></textarea>
                    @error('transportNotes') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" x-on:click="$modalClose('transport-request-modal')" class="rounded-lg border px-4 py-2 text-sm">Cancel</button>
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Save Request</button>
            </div>
        </form>
    </x-modal>

    <x-card class="mb-6">
        <div class="mb-4 flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Pickup Locations</h2>
                <p class="text-sm text-slate-500">Create and manage chapter pickup points and times.</p>
            </div>
            <button
                type="button"
                x-on:click="$wire.call('createPickupLocation').then(() => { $modalOpen('pickup-location-modal'); setTimeout(() => window.dispatchEvent(new CustomEvent('pickup-map-open')), 160); })"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white"
            >
                Add Pickup Location
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700">Location</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700">Pickup Time</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700">Contact</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700">Coordinates</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700">Status</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white">
                    @forelse($pickupLocations as $location)
                        <tr>
                            <td class="px-3 py-3">
                                <p class="font-medium text-zinc-900">{{ $location->name }}</p>
                                @if($location->address)
                                    <p class="text-xs text-zinc-500">{{ $location->address }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-zinc-700">{{ $this->formatDisplayTime($location->pickup_time) }}</td>
                            <td class="px-3 py-3 text-zinc-700">
                                <p>{{ $location->contact_person ?: 'N/A' }}</p>
                                <p class="text-xs text-zinc-500">{{ $location->contact_phone ?: 'No phone' }}</p>
                            </td>
                            <td class="px-3 py-3 text-xs text-zinc-600">
                                @if($location->latitude !== null && $location->longitude !== null)
                                    {{ $location->latitude }}, {{ $location->longitude }}
                                @else
                                    Not set
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $location->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-200 text-zinc-600' }}">
                                    {{ $location->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="rounded-md border border-blue-200 px-2 py-1 text-xs font-medium text-blue-700"
                                        x-on:click="$wire.call('editPickupLocation', {{ $location->id }}).then(() => { $modalOpen('pickup-location-modal'); setTimeout(() => window.dispatchEvent(new CustomEvent('pickup-map-open')), 160); })"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="togglePickupLocationStatus({{ $location->id }})"
                                        class="rounded-md border border-amber-200 px-2 py-1 text-xs font-medium text-amber-700"
                                    >
                                        {{ $location->is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="removePickupLocation({{ $location->id }})"
                                        class="rounded-md border border-rose-200 px-2 py-1 text-xs font-medium text-rose-700"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-zinc-500">No pickup locations found for this chapter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-card>
        <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Transport Requests</h2>
                <p class="text-sm text-slate-500">Review, approve, reject, and track pickup requests.</p>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <input wire:model.live.debounce.300ms="search" type="text" class="rounded-lg border px-3 py-2 text-sm" placeholder="Search name, phone, location" />
                <select wire:model.live="status" class="rounded-lg border px-3 py-2 text-sm">
                    <option value="">All status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <select wire:model.live="quantity" class="rounded-lg border px-3 py-2 text-sm">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700">Requester</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700">Pickup</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700">Status</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700">Requested At</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white">
                    @forelse($transportRequests as $request)
                        <tr>
                            <td class="px-3 py-3">
                                <p class="font-medium text-zinc-900">{{ $request->name }}</p>
                                <p class="text-xs text-zinc-500">{{ $request->phone }}</p>
                            </td>
                            <td class="px-3 py-3 text-zinc-700">
                                <p>{{ $request->pickupLocation?->name ?? $request->pickup_location }}</p>
                                <p class="text-xs text-zinc-500">{{ $request->pickup_time ? $this->formatDisplayTime($request->pickup_time) : 'Time not set' }}</p>
                            </td>
                            <td class="px-3 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $request->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($request->status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-zinc-600">{{ $request->created_at?->format('M d, Y H:i') }}</td>
                            <td class="px-3 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="rounded-md border border-blue-200 px-2 py-1 text-xs font-medium text-blue-700"
                                        x-on:click="$wire.call('loadTransport', {{ $request->id }}).then(() => $modalOpen('transport-request-modal'))"
                                    >
                                        View
                                    </button>

                                    @if($request->status === 'pending')
                                        <button type="button" wire:click="changeStatus({{ $request->id }}, 'approved')" class="rounded-md border border-emerald-200 px-2 py-1 text-xs font-medium text-emerald-700">Approve</button>
                                        <button type="button" wire:click="changeStatus({{ $request->id }}, 'rejected')" class="rounded-md border border-rose-200 px-2 py-1 text-xs font-medium text-rose-700">Reject</button>
                                    @endif

                                    <button type="button" wire:click="deleteTransport({{ $request->id }})" class="rounded-md border border-zinc-300 px-2 py-1 text-xs font-medium text-zinc-600">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-6 text-center text-zinc-500">No transport requests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $transportRequests->links() }}
        </div>
    </x-card>

    @script
    <script>
        (() => {
            let pickupAdminMap = null;
            let pickupMarker = null;
            let leafletLoadingPromise = null;
            let modalObserverBound = false;
            let modalTriggerBound = false;
            let geocodeAbortController = null;

            function setStatus(message) {
                const statusEl = document.getElementById('pickup-map-status');
                if (!statusEl) {
                    return;
                }

                statusEl.textContent = message;
            }

            function setWireValue(inputId, value) {
                const input = document.getElementById(inputId);
                if (!input) {
                    return;
                }

                input.value = value;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }

            function roundCoordinate(value) {
                return Number(value).toFixed(7);
            }

            function setAddressValue(address) {
                if (typeof address !== 'string') {
                    return;
                }

                const normalized = address.trim().slice(0, 255);
                if (normalized === '') {
                    return;
                }

                setWireValue('locationAddress', normalized);
            }

            function clearSearchResults() {
                const resultsEl = document.getElementById('pickup-map-search-results');
                if (!resultsEl) {
                    return;
                }

                resultsEl.innerHTML = '';
                resultsEl.classList.add('hidden');
            }

            function applySearchedLocation(result) {
                const lat = Number(result?.lat);
                const lng = Number(result?.lon);

                if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                    setStatus('Selected location has invalid coordinates.');
                    return;
                }

                placeMarker(lat, lng, true, 'Location selected');
                setAddressValue(result?.display_name || '');
                clearSearchResults();
            }

            function renderSearchResults(results) {
                const resultsEl = document.getElementById('pickup-map-search-results');
                if (!resultsEl) {
                    return;
                }

                resultsEl.innerHTML = '';
                resultsEl.classList.remove('hidden');

                if (!Array.isArray(results) || results.length === 0) {
                    const emptyState = document.createElement('p');
                    emptyState.className = 'rounded-md border border-dashed border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-600';
                    emptyState.textContent = 'No matching location found. Try a more specific search.';
                    resultsEl.appendChild(emptyState);
                    return;
                }

                const list = document.createElement('ul');
                list.className = 'space-y-2';

                results.forEach((result) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'w-full rounded-md border border-blue-200 bg-white px-3 py-2 text-left text-xs text-zinc-700 hover:border-blue-300';
                    button.textContent = result.display_name || `${result.lat}, ${result.lon}`;
                    button.addEventListener('click', () => applySearchedLocation(result));

                    const item = document.createElement('li');
                    item.appendChild(button);
                    list.appendChild(item);
                });

                resultsEl.appendChild(list);
            }

            async function fetchSearchResults(query) {
                if (geocodeAbortController) {
                    geocodeAbortController.abort();
                }

                geocodeAbortController = new AbortController();

                const normalizedQuery = query.toLowerCase().includes('nigeria')
                    ? query
                    : `${query}, Nigeria`;

                const params = new URLSearchParams({
                    q: normalizedQuery,
                    format: 'jsonv2',
                    limit: '6',
                    addressdetails: '1',
                    countrycodes: 'ng',
                });

                const response = await fetch(`https://nominatim.openstreetmap.org/search?${params.toString()}`, {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                    },
                    signal: geocodeAbortController.signal,
                });

                if (!response.ok) {
                    throw new Error(`Search failed with status ${response.status}`);
                }

                const payload = await response.json();
                return Array.isArray(payload) ? payload : [];
            }

            function bindMapSearch() {
                const input = document.getElementById('pickup-map-search-input');
                const button = document.getElementById('pickup-map-search-btn');

                if (!input || !button || input.dataset.bound === '1') {
                    return;
                }

                input.dataset.bound = '1';
                button.dataset.bound = '1';

                const performSearch = async () => {
                    const query = input.value.trim();

                    if (query.length < 3) {
                        clearSearchResults();
                        setStatus('Enter at least 3 characters to search for a location.');
                        return;
                    }

                    button.disabled = true;
                    button.classList.add('opacity-60', 'cursor-not-allowed');
                    setStatus('Searching location...');

                    try {
                        const results = await fetchSearchResults(query);
                        renderSearchResults(results);

                        if (results.length > 0) {
                            setStatus('Select a search result to fill address, latitude, and longitude.');
                        } else {
                            setStatus('No matching location found. Try another search.');
                        }
                    } catch (error) {
                        if (error && error.name === 'AbortError') {
                            return;
                        }

                        clearSearchResults();
                        setStatus('Search is unavailable right now. Try again in a moment.');
                    } finally {
                        button.disabled = false;
                        button.classList.remove('opacity-60', 'cursor-not-allowed');
                    }
                };

                button.addEventListener('click', performSearch);
                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        performSearch();
                    }
                });
            }

            function placeMarker(latitude, longitude, center = true, statusPrefix = 'Coordinates set') {
                if (!pickupAdminMap || typeof L === 'undefined') {
                    return;
                }

                const lat = Number(latitude);
                const lng = Number(longitude);

                if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                    return;
                }

                const latRounded = roundCoordinate(lat);
                const lngRounded = roundCoordinate(lng);

                setWireValue('locationLatitude', latRounded);
                setWireValue('locationLongitude', lngRounded);

                const markerLatLng = [Number(latRounded), Number(lngRounded)];

                if (!pickupMarker) {
                    pickupMarker = L.marker(markerLatLng, { draggable: true }).addTo(pickupAdminMap);
                    pickupMarker.on('dragend', () => {
                        const position = pickupMarker.getLatLng();
                        placeMarker(position.lat, position.lng, false, 'Coordinates updated');
                        reverseGeocode(position.lat, position.lng);
                    });
                } else {
                    pickupMarker.setLatLng(markerLatLng);
                }

                if (center) {
                    pickupAdminMap.flyTo(markerLatLng, 14, { animate: true, duration: 0.8 });
                }

                setStatus(`${statusPrefix}: ${latRounded}, ${lngRounded}`);
                
                // Reverse geocode to get the address
                reverseGeocode(lat, lng);
            }

            async function reverseGeocode(lat, lng) {
                try {
                    setStatus('Getting address...');
                    const response = await fetch(
                        `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`
                    );
                    
                    if (!response.ok) {
                        throw new Error('Reverse geocoding failed');
                    }
                    
                    const data = await response.json();
                    if (data && data.display_name) {
                        setAddressValue(data.display_name);
                        setStatus('Address set from map location');
                    } else {
                        setStatus('Coordinates set, but address not found. You can enter address manually.');
                    }
                } catch (error) {
                    console.error('Reverse geocoding error:', error);
                    setStatus('Coordinates set, but unable to fetch address. Enter address manually.');
                }
            }

            function bindUseCurrentLocation() {
                const button = document.getElementById('pickup-map-use-location');
                if (!button || button.dataset.bound === '1') {
                    return;
                }

                button.dataset.bound = '1';

                button.addEventListener('click', () => {
                    if (!window.isSecureContext) {
                        setStatus('Current location requires https or localhost.');
                        return;
                    }

                    if (!navigator.geolocation) {
                        setStatus('Geolocation is not supported by this browser.');
                        return;
                    }

                    button.disabled = true;
                    button.classList.add('opacity-60', 'cursor-not-allowed');
                    setStatus('Locating current position...');

                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            placeMarker(position.coords.latitude, position.coords.longitude, true, 'Current location set');
                            button.disabled = false;
                            button.classList.remove('opacity-60', 'cursor-not-allowed');
                        },
                        (error) => {
                            let message = 'Unable to fetch current location.';

                            if (error && error.code === error.PERMISSION_DENIED) {
                                message = 'Location permission denied. Allow it in browser settings.';
                            } else if (error && error.code === error.POSITION_UNAVAILABLE) {
                                message = 'Location unavailable. Check network/GPS.';
                            } else if (error && error.code === error.TIMEOUT) {
                                message = 'Location timed out. Try again.';
                            }

                            setStatus(message);
                            button.disabled = false;
                            button.classList.remove('opacity-60', 'cursor-not-allowed');
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 15000,
                            maximumAge: 0,
                        }
                    );
                });
            }

            function hydrateCoordinatesFromInputs() {
                const latInput = document.getElementById('locationLatitude');
                const lngInput = document.getElementById('locationLongitude');

                if (!latInput || !lngInput) {
                    return;
                }

                const lat = parseFloat(latInput.value);
                const lng = parseFloat(lngInput.value);

                if (Number.isFinite(lat) && Number.isFinite(lng)) {
                    placeMarker(lat, lng, true, 'Coordinates loaded');
                } else {
                    setStatus('Search for a place, click map, or use current location to set pickup coordinates.');
                }
            }

            function resolveMapElement() {
                const mapElements = Array.from(document.querySelectorAll('#pickup-admin-map'));
                if (mapElements.length === 0) {
                    return null;
                }

                return mapElements.find((element) => element.offsetWidth > 0 && element.offsetHeight > 0) || mapElements[0];
            }

            function initPickupMap() {
                const mapEl = resolveMapElement();
                if (!mapEl || typeof L === 'undefined') {
                    return;
                }

                if (mapEl.offsetWidth === 0 || mapEl.offsetHeight === 0) {
                    setTimeout(initPickupMap, 180);
                    return;
                }

                if (pickupAdminMap && pickupAdminMap.getContainer() !== mapEl) {
                    pickupAdminMap.remove();
                    pickupAdminMap = null;
                    pickupMarker = null;
                }

                if (!pickupAdminMap) {
                    pickupAdminMap = L.map(mapEl).setView([9.082, 8.6753], 6);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(pickupAdminMap);

                    pickupAdminMap.on('click', (event) => {
                        placeMarker(event.latlng.lat, event.latlng.lng, false, 'Manual location set');
                    });
                }

                setTimeout(() => {
                    if (pickupAdminMap) {
                        pickupAdminMap.invalidateSize();
                    }
                }, 120);
                setTimeout(() => {
                    if (pickupAdminMap) {
                        pickupAdminMap.invalidateSize();
                    }
                }, 280);

                bindUseCurrentLocation();
                bindMapSearch();
                hydrateCoordinatesFromInputs();
            }

            function loadLeafletAssets() {
                if (typeof L !== 'undefined') {
                    return Promise.resolve();
                }

                if (leafletLoadingPromise) {
                    return leafletLoadingPromise;
                }

                leafletLoadingPromise = new Promise((resolve, reject) => {
                    if (!document.getElementById('leaflet-css')) {
                        const stylesheet = document.createElement('link');
                        stylesheet.id = 'leaflet-css';
                        stylesheet.rel = 'stylesheet';
                        stylesheet.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                        stylesheet.onerror = () => {
                            stylesheet.href = 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css';
                        };
                        document.head.appendChild(stylesheet);
                    }

                    const existingScript = document.getElementById('leaflet-js');
                    if (existingScript) {
                        if (typeof L !== 'undefined') {
                            resolve();
                            return;
                        }
                        existingScript.remove();
                    }

                    const scriptSources = [
                        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
                        'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js',
                    ];
                    let sourceIndex = 0;

                    const script = document.createElement('script');
                    script.id = 'leaflet-js';
                    script.onload = () => resolve();
                    script.onerror = () => {
                        sourceIndex += 1;

                        if (sourceIndex < scriptSources.length) {
                            script.src = scriptSources[sourceIndex];
                            return;
                        }

                        reject(new Error('Leaflet failed to load.'));
                    };
                    script.src = scriptSources[sourceIndex];
                    document.head.appendChild(script);
                });

                return leafletLoadingPromise;
            }

            function bootPickupMap() {
                loadLeafletAssets()
                    .then(() => {
                        setTimeout(initPickupMap, 80);
                    })
                    .catch(() => {
                        setStatus('Unable to load map assets. Refresh and try again.');
                    });
            }

            function observeModalOpen() {
                if (modalObserverBound) {
                    return;
                }

                modalObserverBound = true;
                const observer = new MutationObserver(() => {
                    const mapEl = resolveMapElement();
                    if (mapEl && mapEl.offsetWidth > 0 && mapEl.offsetHeight > 0) {
                        bootPickupMap();
                    }
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                });
            }

            function bindModalOpenTriggers() {
                if (modalTriggerBound) {
                    return;
                }

                modalTriggerBound = true;
                document.addEventListener('click', (event) => {
                    if (!(event.target instanceof Element)) {
                        return;
                    }

                    const trigger = event.target.closest('[x-on\\:click]');
                    if (!trigger) {
                        return;
                    }

                    const expression = trigger.getAttribute('x-on:click') || '';
                    if (expression.includes('pickup-location-modal')) {
                        setTimeout(bootPickupMap, 220);
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', bootPickupMap);
            document.addEventListener('livewire:navigated', bootPickupMap);
            window.addEventListener('pickup-map-open', bootPickupMap);
            document.addEventListener('DOMContentLoaded', observeModalOpen);
            document.addEventListener('DOMContentLoaded', bindModalOpenTriggers);
        })();
    </script>
    @endscript
</div>
