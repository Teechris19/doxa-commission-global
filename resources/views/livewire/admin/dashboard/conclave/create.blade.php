<?php

use App\Models\Chapter;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;

new  #[Layout('components.layouts.admin')]  class extends Component {
    use Interactions;

    public $name;

    public $data = [
        'address' => '',
        'city' => '',
        'state' => '',
        'country' => '',
        'description' => ''
    ];

    public function save()
    {
        // Validate the input
        $validatedData = $this->validate([
            'name' => 'required|unique:Chapters,id|min:3', // Assuming 'id' is the PK in Chapter
            'data.address' => 'required|min:2',
            'data.city' => 'required',
            'data.state' => 'required',
            'data.country' => 'required',
            'data.description' => 'nullable|string'
        ]);

        // Save to Chapter model
        $Chapter = Chapter::create([
            'name' => $validatedData['name'], // Using name as ID
            'data' => json_encode($validatedData['data']),
        ]);

        $this->toast()
            ->success('Done!', 'Chapter created successfully!')
            ->flash()
            ->send();

        // Optional: return or flash message
        return $this->redirect(route('super-admin.conclaves'));
    }

}; ?>

<div>
    <x-card>
        <form wire:submit.prevent='save'>
            <!-- Email Address -->
            <flux:input wire:model="name" label="Name" type="text" required autocomplete="name" placeholder="Name" />
            <div class="mt-5 space-y-3" x-data="{ showResults: false, mapReady: false }">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-gray-700">Search Location</label>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <input id="map-search" type="text"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                            placeholder="Search address, city, or landmark" />
                        <button type="button" id="map-search-btn"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Search
                        </button>
                    </div>
                    <div id="map-search-results" class="hidden max-h-56 overflow-y-auto rounded-lg border border-gray-200 bg-white text-sm shadow">
                        <!-- Results injected by JS -->
                    </div>
                    <p class="text-xs text-gray-500">Pick a location on the map or type the fields manually.</p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-blue-50/40 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Map Preview</p>
                            <p class="text-xs text-slate-500">Load the map when you’re ready to pick a location.</p>
                        </div>
                        <button type="button" id="map-load-btn"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700">
                            Load Map
                        </button>
                    </div>
                    <div wire:ignore class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white">
                        <div id="conclave-map" class="h-[320px] w-full"></div>
                    </div>
                </div>
            </div>

            <div class="grid md:grid-cols-2 mt-4  gap-2">
                <flux:input wire:model="data.address" label="Address *" type="text" required autocomplete="name"
                    placeholder="Name" />
                <flux:input label="City *" invalidate wire:model='data.city'></flux:input>
                <flux:input label="State *" invalidate wire:model='data.state'></flux:input>
                <flux:input label="Country *" invlaidate wire:model='data.country'></flux:input>
            </div>

            <div class="mt-3">
                <flux:textarea label="Dscription" wire:model='data.description'></flux:textarea>
            </div>

            <button class="bg-white text-gray-800 dark:bg-zinc-900 dark:text-white border border-gray-300 dark:border-zinc-700 px-6 py-2 mt-5 rounded hover:bg-gray-100 dark:hover:bg-zinc-800 transition-colors duration-200">
                <span wire:loading.remove>Save</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </form>
    </x-card>

    @push('styles')
        <link
            rel="stylesheet"
            href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
            crossorigin=""
        />
    @endpush

    @push('scripts')
        <script
            src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""
        ></script>
        <script>
            function initConclaveMap() {
                const mapEl = document.getElementById('conclave-map');
                if (!mapEl || mapEl.dataset.mapReady) return;
                mapEl.dataset.mapReady = 'true';

                const map = L.map('conclave-map').setView([4.95, 8.32], 12);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                const marker = L.marker([4.95, 8.32], { draggable: true }).addTo(map);

                const setFields = (data) => {
                    if (data.address) @this.set('data.address', data.address);
                    if (data.city) @this.set('data.city', data.city);
                    if (data.state) @this.set('data.state', data.state);
                    if (data.country) @this.set('data.country', data.country);
                };

                const reverseLookup = async (lat, lng) => {
                    const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`;
                    const res = await fetch(url);
                    const json = await res.json();
                    const address = json.address || {};
                    setFields({
                        address: json.display_name || '',
                        city: address.city || address.town || address.village || '',
                        state: address.state || '',
                        country: address.country || ''
                    });
                };

                marker.on('dragend', async (e) => {
                    const { lat, lng } = e.target.getLatLng();
                    await reverseLookup(lat, lng);
                });

                map.on('click', async (e) => {
                    marker.setLatLng(e.latlng);
                    await reverseLookup(e.latlng.lat, e.latlng.lng);
                });

                const searchInput = document.getElementById('map-search');
                const searchBtn = document.getElementById('map-search-btn');
                const resultsBox = document.getElementById('map-search-results');

                const clearResults = () => {
                    if (!resultsBox) return;
                    resultsBox.innerHTML = '';
                    resultsBox.classList.add('hidden');
                };

                const renderResults = (results) => {
                    if (!resultsBox) return;
                    if (!results.length) {
                        resultsBox.innerHTML = '<div class="px-3 py-2 text-xs text-gray-500">No results found.</div>';
                        resultsBox.classList.remove('hidden');
                        return;
                    }

                    resultsBox.innerHTML = results.map((item, index) => {
                        const label = item.display_name || `${item.lat}, ${item.lon}`;
                        return `<button type="button" data-index="${index}" class="block w-full px-3 py-2 text-left text-sm hover:bg-blue-50">${label}</button>`;
                    }).join('');
                    resultsBox.classList.remove('hidden');

                    resultsBox.querySelectorAll('button[data-index]').forEach(btn => {
                        btn.addEventListener('click', async () => {
                            const idx = parseInt(btn.dataset.index, 10);
                            const selected = results[idx];
                            const lat = parseFloat(selected.lat);
                            const lng = parseFloat(selected.lon);
                            map.setView([lat, lng], 15);
                            marker.setLatLng([lat, lng]);
                            await reverseLookup(lat, lng);
                            clearResults();
                        });
                    });
                };

                const doSearch = async () => {
                    const query = (searchInput.value || '').trim();
                    if (!query) return;
                    const url = `https://nominatim.openstreetmap.org/search?format=json&countrycodes=ng&q=${encodeURIComponent(query)}`;
                    const res = await fetch(url);
                    const results = await res.json();
                    renderResults(results);
                };

                searchBtn?.addEventListener('click', doSearch);
                searchInput?.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        doSearch();
                    }
                });
                searchInput?.addEventListener('input', () => {
                    if (!searchInput.value.trim()) {
                        clearResults();
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', () => {
                const loadBtn = document.getElementById('map-load-btn');
                loadBtn?.addEventListener('click', () => {
                    initConclaveMap();
                });
            }, { once: true });
        </script>
    @endpush
</div>
