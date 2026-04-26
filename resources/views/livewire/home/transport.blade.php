<?php

use App\Models\Chapter;
use App\Models\PickupLocation;
use App\Models\Transport;
use App\Notifications\TransportRequestUpdated;
use App\Services\NotificationRecipients;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    #[Url]
    public ?string $chapter = null;

    public ?int $chapterId = null;
    public $activeChapter = null;

    public array $pickupPoints = [];
    public array $chapters = [];
    public $selectedChapterId = '';

    public ?string $name = null;
    public ?string $phone = null;
    public ?int $pickup_location_id = null;
    public ?string $pickup_location = null;
    public ?string $pickup_time = null;
    public ?string $pickup_time_label = null;
    public ?string $user_address = null;
    public ?string $user_latitude = null;
    public ?string $user_longitude = null;

    public bool $submitted = false;
    public ?string $message = null;
    public ?string $messageType = null;

    public function mount(): void
    {
        $this->chapters = Chapter::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();

        if (count($this->chapters) > 0) {
            $this->selectedChapterId = (string) $this->chapters[0]['id'];
        }

        $this->chapterId = $this->resolveChapterId();
        $this->activeChapter = $this->chapterId ? Chapter::find($this->chapterId) : null;

        $this->loadPickupPoints();
        $this->syncSelectedPickup();
    }

    public function updatedSelectedChapterId(): void
    {
        $this->chapterId = $this->selectedChapterId ? (int) $this->selectedChapterId : null;
        $this->activeChapter = $this->chapterId ? Chapter::find($this->chapterId) : null;
        $this->loadPickupPoints();
        $this->syncSelectedPickup();
    }

    public function updatedPickupLocationId(): void
    {
        $this->syncSelectedPickup();
    }

    public function submitPickupRequest(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'pickup_location_id' => 'nullable|integer|exists:pickup_locations,id',
            'pickup_location' => 'nullable|string|max:1000|required_without:pickup_location_id',
            'pickup_time' => 'nullable|date_format:H:i',
            'user_address' => 'nullable|string|max:255',
            'user_latitude' => 'nullable|numeric|between:-90,90',
            'user_longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $selectedPickup = null;
        if (!empty($validated['pickup_location_id'])) {
            $selectedPickup = PickupLocation::query()
                ->where('id', $validated['pickup_location_id'])
                ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
                ->first();

            if (!$selectedPickup) {
                $this->addError('pickup_location_id', 'Selected pickup location is not available for this chapter.');
                return;
            }
        }

        $pickupLocationText = trim((string) ($validated['pickup_location'] ?? ''));
        $pickupTime = $validated['pickup_time'] ?? null;
        $chapterId = $this->chapterId;

        if ($selectedPickup) {
            $pickupLocationText = $selectedPickup->address ?: $selectedPickup->name;
            $pickupTime = $selectedPickup->pickup_time ?: $pickupTime;
            $chapterId = $selectedPickup->chapter_id ?: $chapterId;
        }

        if ($pickupLocationText === '') {
            $pickupLocationText = 'Pickup location to be confirmed';
        }

        try {
            $transport = Transport::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'pickup_location' => $pickupLocationText,
                'pickup_location_id' => $selectedPickup?->id,
                'pickup_time' => $pickupTime,
                'chapter_id' => $chapterId,
                'user_address' => $validated['user_address'] ?? null,
                'user_latitude' => $validated['user_latitude'] ?? null,
                'user_longitude' => $validated['user_longitude'] ?? null,
                'status' => 'pending',
            ]);

            $recipients = (new NotificationRecipients())
                ->forFunctionAndChapter('transport', $chapterId);

            foreach ($recipients as $recipient) {
                $recipient->notify(new TransportRequestUpdated($transport, 'created'));
            }

            $this->messageType = 'success';
            $this->message = 'Pickup request submitted successfully. The transport team will contact you shortly.';
            $this->submitted = true;

            $this->reset([
                'name',
                'phone',
                'pickup_location',
                'user_address',
                'user_latitude',
                'user_longitude',
            ]);

            $this->syncSelectedPickup();
        } catch (\Throwable $e) {
            $this->messageType = 'error';
            $this->message = 'Unable to submit request right now. Please try again.';
        }
    }

    private function resolveChapterId(): ?int
    {
        if ($this->chapter) {
            return Chapter::where('name', e($this->chapter))->value('id');
        }

        $user = auth()->user();
        if ($user?->chapter_id) {
            return (int) $user->chapter_id;
        }

        return Chapter::query()->value('id');
    }

    private function loadPickupPoints(): void
    {
        $this->pickupPoints = PickupLocation::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->where('is_active', true)
            ->orderBy('pickup_time')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'address',
                'description',
                'contact_person',
                'contact_phone',
                'pickup_time',
                'latitude',
                'longitude',
            ])
            ->map(function (PickupLocation $location): array {
                return [
                    'id' => $location->id,
                    'name' => $location->name,
                    'address' => $location->address,
                    'description' => $location->description,
                    'contact_person' => $location->contact_person,
                    'contact_phone' => $location->contact_phone,
                    'pickup_time' => $location->pickup_time,
                    'pickup_time_label' => $this->formatTimeLabel($location->pickup_time),
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                ];
            })
            ->toArray();
    }

    private function syncSelectedPickup(): void
    {
        if (!$this->pickup_location_id && !empty($this->pickupPoints)) {
            $this->pickup_location_id = (int) $this->pickupPoints[0]['id'];
        }

        $selected = collect($this->pickupPoints)->first(
            fn(array $point): bool => (int) $point['id'] === (int) $this->pickup_location_id
        );

        if (!$selected) {
            $this->pickup_time = null;
            $this->pickup_time_label = null;
            return;
        }

        $this->pickup_time = $selected['pickup_time'];
        $this->pickup_time_label = $selected['pickup_time_label'];

        if (!$this->pickup_location) {
        $this->pickup_location = $selected['address'] ?: $selected['name'];
    }

    }

    private function formatTimeLabel(?string $time): ?string
    {
        if (!$time) {
            return null;
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

<div class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <section class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_24px_60px_-40px_rgba(37,99,235,0.45)]">
        <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-12 text-white sm:px-10">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-100">Need a Ride</p>
            <h1 class="mt-3 text-3xl font-bold sm:text-4xl">Transportation Services</h1>
            <p class="mt-3 max-w-3xl text-sm text-blue-100 sm:text-base">
                {{ $activeChapter ? 'Pickup support for ' . $activeChapter->name : 'Pickup support for your chapter' }}.
                Select a pickup point, track it on the map, and submit your request.
            </p>
        </div>

        <div class="space-y-8 px-6 py-8 sm:px-10">
            @if(count($chapters) > 0)
                <div class="mb-4 flex items-center gap-4">
                    <div class="flex-1">
                        <label for="chapter-select" class="mb-2 block text-sm font-semibold text-slate-700">Select Chapter</label>
                        <select
                            id="chapter-select"
                            wire:model.live="selectedChapterId"
                            class="w-full rounded-xl border border-blue-200 bg-white px-4 py-3 text-sm text-slate-900"
                        >
                            @foreach($chapters as $chapter)
                                <option value="{{ $chapter['id'] }}">{{ $chapter['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            @if ($message)
                <div class="rounded-2xl border px-4 py-3 text-sm {{ $messageType === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700' }}">
                    {{ $message }}
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-[1.35fr_1fr]">
                <article wire:ignore class="rounded-2xl border border-blue-100 bg-white p-4 sm:p-5">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold text-slate-900">Pickup Map (OpenStreetMap)</h2>
                        <button
                            id="locate-me-btn"
                            type="button"
                            class="inline-flex items-center rounded-xl border border-blue-200 px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-blue-700 transition hover:bg-blue-50"
                        >
                            Use My Location
                        </button>
                    </div>

                    <div id="transport-map" class="h-[360px] w-full overflow-hidden rounded-xl border border-blue-100 bg-blue-50"></div>
                    <p id="location-status" class="mt-3 text-xs text-slate-500">Tap "Use My Location" or click the map to set your position manually.</p>
                </article>

                <article class="rounded-2xl border border-blue-100 bg-white p-4 sm:p-5">
                    <h2 class="text-lg font-semibold text-slate-900">Pickup Points</h2>
                    <p class="mt-1 text-sm text-slate-600">Choose from active chapter pickup locations.</p>

                    <div class="mt-4 max-h-[330px] space-y-3 overflow-y-auto pr-1">
                        @forelse($pickupPoints as $point)
                            <button
                                type="button"
                                data-map-focus-id="{{ $point['id'] }}"
                                class="w-full rounded-xl border border-blue-100 bg-blue-50/60 p-3 text-left transition hover:border-blue-300"
                            >
                                <p class="text-sm font-semibold text-slate-900">{{ $point['name'] }}</p>
                                @if($point['address'])
                                    <p class="mt-1 text-xs text-slate-600">{{ $point['address'] }}</p>
                                @endif
                                <div class="mt-2 flex items-center justify-between text-xs text-slate-600">
                                    <span>{{ $point['pickup_time_label'] ? 'Pickup: ' . $point['pickup_time_label'] : 'Pickup time not set' }}</span>
                                    @if($point['contact_phone'])
                                        <span>{{ $point['contact_phone'] }}</span>
                                    @endif
                                </div>
                            </button>
                        @empty
                            <div class="rounded-xl border border-dashed border-blue-200 bg-blue-50/60 p-4 text-sm text-slate-500">
                                No active pickup points are configured for this chapter yet.
                            </div>
                        @endforelse
                    </div>
                </article>
            </div>

            <article class="rounded-2xl border border-blue-100 bg-white p-5 sm:p-6">
                <h2 class="text-xl font-semibold text-slate-900">Request Pickup</h2>
                <p class="mt-1 text-sm text-slate-600">Submit your request and the transport team will follow up.</p>

                <form wire:submit="submitPickupRequest" class="mt-5 space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label for="name" class="mb-2 block text-sm font-medium text-slate-700">Your Name</label>
                            <input
                                id="name"
                                type="text"
                                wire:model="name"
                                class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900"
                                placeholder="John Doe"
                            >
                            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="phone" class="mb-2 block text-sm font-medium text-slate-700">Phone Number</label>
                            <input
                                id="phone"
                                type="tel"
                                wire:model="phone"
                                class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900"
                                placeholder="0801 234 5678"
                            >
                            @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="pickup_location_id" class="mb-2 block text-sm font-medium text-slate-700">Pickup Location</label>
                        <select
                            id="pickup_location_id"
                            wire:model.live="pickup_location_id"
                            class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900"
                        >
                            <option value="">Select a pickup point</option>
                            @foreach($pickupPoints as $point)
                                <option value="{{ $point['id'] }}">{{ $point['name'] }}{{ $point['pickup_time_label'] ? ' (' . $point['pickup_time_label'] . ')' : '' }}</option>
                            @endforeach
                        </select>
                        @error('pickup_location_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                        @if($pickup_time_label)
                            <p class="mt-2 text-xs font-medium uppercase tracking-[0.18em] text-blue-700">Selected pickup time: {{ $pickup_time_label }}</p>
                        @endif
                    </div>

                    <div>
                        <label for="pickup-location" class="mb-2 block text-sm font-medium text-slate-700">Specific Address / Landmark (Optional)</label>
                        <textarea
                            id="pickup-location"
                            rows="3"
                            wire:model="pickup_location"
                            class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900"
                            placeholder="Add nearest bus stop, street landmark, or apartment gate"
                        ></textarea>
                        @error('pickup_location') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <input id="user_latitude" type="hidden" wire:model="user_latitude">
                    <input id="user_longitude" type="hidden" wire:model="user_longitude">
                    <input id="user_address" type="hidden" wire:model="user_address">

                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700" wire:loading.attr="disabled">
                        <span wire:loading.remove>Submit Request</span>
                        <span wire:loading>Submitting...</span>
                    </button>
                </form>
            </article>
        </div>
    </section>

    <script id="pickup-locations-json" type="application/json">@json($pickupPoints)</script>

    <script>
    (() => {
        let map;
        let markers = {};
        let userMarker = null;
        let pendingUserCoordinates = null;
        let handlersBound = false;

        function setWireValue(inputId, value) {
            const input = document.getElementById(inputId);
            if (!input) {
                return;
            }

            input.value = value;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function getPickupData() {
            const dataNode = document.getElementById('pickup-locations-json');
            if (!dataNode) {
                return [];
            }

            try {
                return JSON.parse(dataNode.textContent || '[]');
            } catch (error) {
                return [];
            }
        }

        function focusPickup(locationId) {
            if (!map || !markers[locationId]) {
                return;
            }

            const marker = markers[locationId];
            map.flyTo(marker.getLatLng(), 14, { animate: true, duration: 0.8 });
            marker.openPopup();
        }

        function setLocationStatus(message) {
            const statusEl = document.getElementById('location-status');
            if (!statusEl) {
                return;
            }

            statusEl.textContent = message;
        }

        function renderUserLocation(latitude, longitude) {
            if (!map || typeof L === 'undefined') {
                pendingUserCoordinates = [latitude, longitude];
                return;
            }

            if (!userMarker) {
                userMarker = L.marker([latitude, longitude]).addTo(map).bindPopup('Your current location');
            } else {
                userMarker.setLatLng([latitude, longitude]);
            }

            map.flyTo([latitude, longitude], 14, { animate: true, duration: 0.8 });
            userMarker.openPopup();
            pendingUserCoordinates = null;
        }

        function assignUserLocation(latitude, longitude, statusPrefix = 'Current location set') {
            const lat = Number(latitude.toFixed(7));
            const lng = Number(longitude.toFixed(7));

            setWireValue('user_latitude', String(lat));
            setWireValue('user_longitude', String(lng));
            setWireValue('user_address', `Lat ${lat}, Lng ${lng}`);

            renderUserLocation(lat, lng);
            setLocationStatus(`${statusPrefix}: ${lat}, ${lng}`);
        }

        function bindGlobalHandlers() {
            if (handlersBound) {
                return;
            }

            handlersBound = true;

            document.addEventListener('click', (event) => {
                if (!(event.target instanceof Element)) {
                    return;
                }

                const focusButton = event.target.closest('[data-map-focus-id]');
                if (!focusButton) {
                    return;
                }

                const locationId = focusButton.getAttribute('data-map-focus-id');
                if (!locationId) {
                    return;
                }

                const select = document.getElementById('pickup_location_id');
                if (select) {
                    select.value = locationId;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                }

                focusPickup(locationId);
            });

            document.addEventListener('change', (event) => {
                if (!(event.target instanceof HTMLSelectElement)) {
                    return;
                }

                if (event.target.id === 'pickup_location_id') {
                    focusPickup(event.target.value);
                }
            });
        }

        function bindLocateButton() {
            const locateButton = document.getElementById('locate-me-btn');

            if (!locateButton) {
                return;
            }

            locateButton.addEventListener('click', () => {
                if (!window.isSecureContext) {
                    setLocationStatus('Location requires a secure context (https or localhost).');
                    return;
                }

                if (!navigator.geolocation) {
                    setLocationStatus('Geolocation is not supported by your browser.');
                    return;
                }

                locateButton.disabled = true;
                locateButton.classList.add('opacity-60', 'cursor-not-allowed');
                setLocationStatus('Locating you...');

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        assignUserLocation(position.coords.latitude, position.coords.longitude, 'Current location set');
                        locateButton.disabled = false;
                        locateButton.classList.remove('opacity-60', 'cursor-not-allowed');
                    },
                    (error) => {
                        let message = 'Unable to fetch your location. Please allow location access.';

                        if (error && error.code === error.PERMISSION_DENIED) {
                            message = 'Location permission denied. Allow location access in your browser settings.';
                        } else if (error && error.code === error.POSITION_UNAVAILABLE) {
                            message = 'Location is unavailable right now. Check network/GPS and try again.';
                        } else if (error && error.code === error.TIMEOUT) {
                            message = 'Location request timed out. Try again.';
                        }

                        setLocationStatus(message);
                        locateButton.disabled = false;
                        locateButton.classList.remove('opacity-60', 'cursor-not-allowed');
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0,
                    }
                );
            });
        }

        function initTransportMap() {
            const mapEl = document.getElementById('transport-map');
            if (!mapEl || typeof L === 'undefined') {
                return;
            }

            if (mapEl.dataset.mapReady === '1') {
                return;
            }

            mapEl.dataset.mapReady = '1';

            const pickupData = getPickupData();
            const pickupWithCoordinates = pickupData.filter((point) => point.latitude !== null && point.longitude !== null);

            const defaultCenter = pickupWithCoordinates[0]
                ? [Number(pickupWithCoordinates[0].latitude), Number(pickupWithCoordinates[0].longitude)]
                : [9.082, 8.6753];

            map = L.map(mapEl).setView(defaultCenter, pickupWithCoordinates.length ? 12 : 6);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            map.on('click', (event) => {
                assignUserLocation(event.latlng.lat, event.latlng.lng, 'Manual location set');
            });

            markers = {};
            const bounds = [];

            pickupWithCoordinates.forEach((point) => {
                const latitude = Number(point.latitude);
                const longitude = Number(point.longitude);

                const marker = L.marker([latitude, longitude]).addTo(map);
                marker.bindPopup(
                    `<strong>${point.name}</strong><br>${point.address ?? ''}<br>${point.pickup_time_label ? `Pickup: ${point.pickup_time_label}` : ''}`
                );

                markers[String(point.id)] = marker;
                bounds.push([latitude, longitude]);
            });

            if (bounds.length > 1) {
                map.fitBounds(bounds, { padding: [35, 35] });
            }

            bindGlobalHandlers();
            bindLocateButton();

            const select = document.getElementById('pickup_location_id');
            if (select && select.value) {
                focusPickup(select.value);
            }

            if (pendingUserCoordinates && pendingUserCoordinates.length === 2) {
                renderUserLocation(pendingUserCoordinates[0], pendingUserCoordinates[1]);
            }
        }

        function loadLeafletAssets() {
            return new Promise((resolve, reject) => {
                if (typeof L !== 'undefined') {
                    resolve();
                    return;
                }

                if (!document.getElementById('leaflet-css')) {
                    const stylesheet = document.createElement('link');
                    stylesheet.id = 'leaflet-css';
                    stylesheet.rel = 'stylesheet';
                    stylesheet.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                    document.head.appendChild(stylesheet);
                }

                const existingScript = document.getElementById('leaflet-js');
                if (existingScript) {
                    if (typeof L !== 'undefined') {
                        resolve();
                        return;
                    }

                    existingScript.addEventListener('load', () => resolve(), { once: true });
                    existingScript.addEventListener('error', () => reject(new Error('Failed to load Leaflet.')), { once: true });
                    return;
                }

                const script = document.createElement('script');
                script.id = 'leaflet-js';
                script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                script.onload = () => resolve();
                script.onerror = () => reject(new Error('Failed to load Leaflet.'));
                document.head.appendChild(script);
            });
        }

        function bootMap() {
            loadLeafletAssets()
                .then(() => {
                    setTimeout(initTransportMap, 120);
                })
                .catch(() => {
                    const statusEl = document.getElementById('location-status');
                    if (statusEl) {
                        statusEl.textContent = 'Unable to load map assets. Please refresh the page.';
                    }
                });
        }

        document.addEventListener('DOMContentLoaded', bootMap);
        document.addEventListener('livewire:navigated', bootMap);
    })();
    </script>
</div>
