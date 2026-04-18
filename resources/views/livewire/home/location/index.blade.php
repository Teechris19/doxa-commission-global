<?php

use App\Models\Chapter;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {

    public $startLocation = '';
    public $chapters = [];
    public $selectedChapterId = '';
    public $selectedChapter = null;
    public $routeInfo = null;
    public $isCalculating = false;

    public function mount()
    {
        $this->chapters = Chapter::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('name')
            ->get()
            ->toArray();

        if (count($this->chapters) > 0) {
            $this->selectedChapterId = $this->chapters[0]['id'];
            $this->selectedChapter = (object) $this->chapters[0];
        }
    }

    public function updatedSelectedChapterId($chapterId)
    {
        // This is triggered automatically, but we don't update the map here
    }

    public function applyChapter()
    {
        $this->selectedChapterId = request('chapterId', $this->selectedChapterId);
        
        $chapterData = DB::table('chapters')
            ->where('id', $this->selectedChapterId)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->first();

        if ($chapterData) {
            $this->selectedChapter = $chapterData;
            $this->routeInfo = null;
            $this->startLocation = '';
        }
    }

    public function calculateRoute()
    {
        if (!$this->selectedChapter || !$this->startLocation) {
            return;
        }

        $this->isCalculating = true;

        // Geocode the start location using Nominatim
        $startCoords = $this->geocodeLocation($this->startLocation);

        if ($startCoords) {
            $this->routeInfo = [
                'start' => $startCoords,
                'destination' => [
                    'lat' => $this->selectedChapter->latitude,
                    'lng' => $this->selectedChapter->longitude,
                ],
                'destinationName' => $this->selectedChapter->name,
            ];
        }

        $this->isCalculating = false;
    }

    private function geocodeLocation($query)
    {
        try {
            $encodedQuery = urlencode($query);
            $url = "https://nominatim.openstreetmap.org/search?format=json&q={$encodedQuery}&limit=1";

            $response = file_get_contents($url);
            $data = json_decode($response, true);

            if ($data && count($data) > 0) {
                return [
                    'lat' => floatval($data[0]['lat']),
                    'lng' => floatval($data[0]['lon']),
                    'name' => $data[0]['display_name'],
                ];
            }
        } catch (\Exception $e) {
            // Handle error
        }

        return null;
    }

}; ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />

<div class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <section class="rounded-3xl border border-blue-100 bg-white p-6 shadow-[0_24px_60px_-40px_rgba(37,99,235,0.5)] sm:p-8">
        <header class="mb-8 text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-600">Visit Us</p>
            <h1 class="mt-3 text-3xl font-bold text-slate-900">Find a Chapter Near You</h1>
            <p class="mt-2 text-sm text-slate-600">Choose a chapter and get driving directions.</p>
        </header>

        @if(count($chapters) > 0)
            <div class="mb-8 flex justify-center">
                <div class="w-full max-w-xs">
                    <label for="chapter-select" class="mb-2 block text-sm font-semibold text-slate-700">Select Chapter</label>
                    <div class="flex gap-2">
                        <select
                            id="chapter-select"
                            wire:model="selectedChapterId"
                            class="w-full rounded-xl border border-blue-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        >
                            @foreach($chapters as $chapter)
                                <option value="{{ $chapter['id'] }}">{{ $chapter['name'] }}</option>
                            @endforeach
                        </select>
                        <button
                            type="button"
                            onclick="console.log('chaptersData:', chaptersData); const selectedId = parseInt(document.getElementById('chapter-select').value); const chapter = chaptersData.find(c => c.id === selectedId); console.log('chapter:', chapter); if(chapter) { const detailsEl = document.getElementById('chapter-details'); console.log('detailsEl:', detailsEl); document.getElementById('chapter-details').innerHTML = generateDetailsHTML(chapter); updateMap(); }"
                            class="shrink-0 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700"
                        >
                            Apply
                        </button>
                    </div>
                </div>
            </div>
        @endif

        @if($selectedChapter)
            <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
                <div class="space-y-5">
                    <div class="overflow-hidden rounded-2xl border border-blue-100">
                        <div id="map" class="h-[400px] w-full" 
                             data-lat="{{ $selectedChapter->latitude }}" 
                             data-lng="{{ $selectedChapter->longitude }}"
                             data-name="{{ $selectedChapter->name }}">
                        </div>
                    </div>

                    <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                        <h2 class="text-lg font-semibold text-slate-900">Get Directions</h2>
                        <div class="mt-4 flex flex-col gap-3">
                            <input
                                type="text"
                                wire:model="startLocation"
                                wire:keydown.enter="calculateRoute"
                                placeholder="Enter your starting address..."
                                class="w-full rounded-xl border border-blue-200 bg-white px-4 py-3 text-sm text-slate-900"
                            />
                            <button
                                wire:click="calculateRoute"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50"
                            >
                                <span wire:loading wire:target="calculateRoute">Calculating...</span>
                                <span wire:loading.remove wire:target="calculateRoute">Get Directions</span>
                            </button>
                        </div>
                        
                        @if($routeInfo)
                            <div class="mt-4 rounded-xl bg-white p-4">
                                <p class="text-sm font-medium text-slate-700">Route calculated!</p>
                                <p class="text-xs text-slate-500 mt-1">From: {{ $routeInfo['start']['name'] }}</p>
                                <p class="text-xs text-slate-500">To: {{ $routeInfo['destinationName'] }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <aside id="chapter-details" class="rounded-2xl border border-blue-100 bg-white p-5">
                    <h2 class="text-lg font-semibold text-slate-900">{{ $selectedChapter->name }} Details</h2>
                    <div class="mt-4 space-y-4 text-sm text-slate-600">
                        @php
                            $chapterData = (object) ($selectedChapter->data ?? []);
                            $address = isset($chapterData->address) ? $chapterData->address : (isset($chapterData->location) ? $chapterData->location : null);
                            $phone = isset($chapterData->phone) ? $chapterData->phone : null;
                            $email = isset($chapterData->email) ? $chapterData->email : null;
                        @endphp
                        @if($address)
                            <div>
                                <p class="font-semibold text-slate-800">Address</p>
                                <p>{{ $address }}</p>
                            </div>
                        @endif

                        @if($phone)
                            <div>
                                <p class="font-semibold text-slate-800">Phone</p>
                                <a href="tel:{{ $phone }}" class="text-blue-700 hover:underline">{{ $phone }}</a>
                            </div>
                        @endif

                        @if($email)
                            <div>
                                <p class="font-semibold text-slate-800">Email</p>
                                <a href="mailto:{{ $email }}" class="text-blue-700 hover:underline">{{ $email }}</a>
                            </div>
                        @endif

                        <div>
                            <p class="font-semibold text-slate-800">Service Times</p>
                            <p>Sunday: 7:00 AM, 8:30 AM, 10:00 AM, 4:00 PM</p>
                            <p>Thursday: 5:30 PM (Glory Experience)</p>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-2 sm:grid-cols-2">
                        <a href="{{ route('events.index') }}" wire:navigate class="inline-flex items-center justify-center rounded-xl border border-blue-200 px-4 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50">View Events</a>
                        <a href="{{ route('appointment') }}" wire:navigate class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Book Appointment</a>
                    </div>
                </aside>
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-blue-200 p-10 text-center text-sm text-slate-500">
                No location information available.
            </div>
        @endif
    </section>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
<script>
// Chapters data from PHP
const chaptersData = @json($chapters);

function updateMap() {
    const select = document.getElementById('chapter-select');
    const selectedId = parseInt(select.value);
    console.log('Selected chapter ID:', selectedId);
    
    // Find the selected chapter
    const selectedChapter = chaptersData.find(c => c.id === selectedId);
    console.log('Selected chapter:', selectedChapter);
    
    if (selectedChapter && selectedChapter.latitude && selectedChapter.longitude) {
        const mapEl = document.getElementById('map');
        
        // Update data attributes
        mapEl.dataset.lat = selectedChapter.latitude;
        mapEl.dataset.lng = selectedChapter.longitude;
        mapEl.dataset.name = selectedChapter.name;
        
        console.log('New coordinates:', selectedChapter.latitude, selectedChapter.longitude);
        
        // Reinitialize map
        initLocationMap();
    }
}

function generateDetailsHTML(chapter) {
    let data = {};
    try {
        data = typeof chapter.data === 'string' ? JSON.parse(chapter.data) : chapter.data;
    } catch(e) {}
    
    let html = '<h2 class="text-lg font-semibold text-slate-900">' + chapter.name + ' Details</h2>';
    html += '<div class="mt-4 space-y-4 text-sm text-slate-600">';
    
    if (data.address || data.location) {
        html += '<div><p class="font-semibold text-slate-800">Address</p><p>' + (data.address || data.location) + '</p></div>';
    }
    if (data.phone) {
        html += '<div><p class="font-semibold text-slate-800">Phone</p><a href="tel:' + data.phone + '" class="text-blue-700 hover:underline">' + data.phone + '</a></div>';
    }
    if (data.email) {
        html += '<div><p class="font-semibold text-slate-800">Email</p><a href="mailto:' + data.email + '" class="text-blue-700 hover:underline">' + data.email + '</a></div>';
    }
    
    html += '<div><p class="font-semibold text-slate-800">Service Times</p><p>Sunday: 7:00 AM, 8:30 AM, 10:00 AM, 4:00 PM</p><p>Thursday: 5:30 PM (Glory Experience)</p></div>';
    html += '</div>';
    html += '<div class="mt-6 grid gap-2 sm:grid-cols-2">';
    html += '<a href="/events" wire:navigate class="inline-flex items-center justify-center rounded-xl border border-blue-200 px-4 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50">View Events</a>';
    html += '<a href="/appointment" wire:navigate class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Book Appointment</a>';
    html += '</div>';
    
    return html;
}

document.addEventListener('DOMContentLoaded', function() {
    initLocationMap();
});

function initLocationMap() {
    const mapEl = document.getElementById('map');
    if (!mapEl) return;
    
    const lat = parseFloat(mapEl.dataset.lat);
    const lng = parseFloat(mapEl.dataset.lng);
    const name = mapEl.dataset.name || 'Destination';
    
    console.log('Initializing map with:', lat, lng, name);
    
    // Remove existing map if any
    if (window.locationMap) {
        window.locationMap.remove();
    }
    
    // Create new map
    window.locationMap = L.map('map').setView([lat, lng], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(window.locationMap);
    
    // Add destination marker
    const destinationIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
    
    const marker = L.marker([lat, lng], {icon: destinationIcon}).addTo(window.locationMap);
    marker.bindPopup('<b>' + name + '</b>').openPopup();
}
</script>
