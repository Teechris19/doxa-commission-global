<div class="p-6 dark:bg-zinc-900 dark:text-gray-200">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-slate-900 dark:text-gray-100">Chapter Locations</h2>
        <p class="mt-1 text-sm text-slate-600 dark:text-gray-400">
            Set geographic coordinates and details for each chapter to display on the public Location page.
        </p>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800 dark:bg-green-900/30 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_2fr]">
        <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-gray-100">Select Chapter</h3>

            <div class="mb-4">
                <input
                    type="text"
                    wire:model.live="searchQuery"
                    placeholder="Search chapters..."
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-gray-200"
                />
            </div>

            <div class="max-h-[400px] space-y-2 overflow-y-auto">
                @forelse($chapters as $chapter)
                    <button
                        wire:click="selectChapter({{ $chapter->id }})"
                        @class([
                            'w-full rounded-lg border p-4 text-left transition',
                            'border-blue-600 bg-blue-50 dark:bg-blue-900/30' => $selectedChapterId === $chapter->id,
                            'border-slate-200 bg-white hover:border-blue-300 hover:bg-blue-50 dark:border-zinc-600 dark:bg-zinc-700 dark:hover:bg-zinc-600' => $selectedChapterId !== $chapter->id,
                        ])
                    >
                        <p class="font-medium text-slate-900 dark:text-gray-100">{{ $chapter->name }}</p>
                        @if($chapter->latitude && $chapter->longitude)
                            <p class="mt-1 text-xs text-green-600 dark:text-green-400">
                                <i class="fa-solid fa-check-circle"></i> Location set
                            </p>
                            <p class="text-xs text-slate-500 dark:text-gray-400">
                                {{ $chapter->latitude }}, {{ $chapter->longitude }}
                            </p>
                        @else
                            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                <i class="fa-solid fa-exclamation-circle"></i> No location
                            </p>
                        @endif
                    </button>
                @empty
                    <p class="py-4 text-center text-sm text-slate-500 dark:text-gray-400">
                        No chapters found.
                    </p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-gray-100">
                @if($selectedChapterId)
                    Set Location & Details
                @else
                    Location Details
                @endif
            </h3>

            @if($selectedChapterId)
                <form wire:submit.prevent="saveLocation" class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-gray-300">
                            Chapter
                        </label>
                        <div class="rounded-lg bg-slate-50 px-4 py-2.5 text-sm dark:bg-zinc-700 dark:text-gray-200">
                            {{ $chapters->firstWhere('id', $selectedChapterId)?->name }}
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="latitude" class="mb-2 block text-sm font-medium text-slate-700 dark:text-gray-300">
                                Latitude <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="latitude"
                                wire:model.defer="latitude"
                                placeholder="e.g., 6.5244"
                                class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-gray-200"
                            />
                        </div>

                        <div>
                            <label for="longitude" class="mb-2 block text-sm font-medium text-slate-700 dark:text-gray-300">
                                Longitude <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="longitude"
                                wire:model.defer="longitude"
                                placeholder="e.g., 3.3792"
                                class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-gray-200"
                            />
                        </div>
                    </div>

                    <button
                        type="button"
                        wire:click="reverseGeocode"
                        class="text-sm text-blue-600 hover:underline dark:text-blue-400"
                    >
                        <i class="fa-solid fa-magnifying-glass"></i> Auto-fill address from coordinates
                    </button>

                    <div>
                        <label for="address" class="mb-2 block text-sm font-medium text-slate-700 dark:text-gray-300">
                            Address
                        </label>
                        <textarea
                            id="address"
                            wire:model.defer="address"
                            rows="3"
                            placeholder="Enter address or auto-fill from coordinates"
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-gray-200"
                        ></textarea>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="phone" class="mb-2 block text-sm font-medium text-slate-700 dark:text-gray-300">
                                Phone
                            </label>
                            <input
                                type="text"
                                id="phone"
                                wire:model.defer="phone"
                                placeholder="e.g., +234 800 123 4567"
                                class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-gray-200"
                            />
                        </div>

                        <div>
                            <label for="email" class="mb-2 block text-sm font-medium text-slate-700 dark:text-gray-300">
                                Email
                            </label>
                            <input
                                type="email"
                                id="email"
                                wire:model.defer="email"
                                placeholder="e.g., info@chapter.com"
                                class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-gray-200"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-gray-300">
                            Service Times
                        </label>
                        
                        @if(!empty($serviceTimes))
                            <div class="mb-3 space-y-2">
                                @foreach($serviceTimes as $day => $time)
                                    <div class="flex items-center gap-2">
                                        <span class="w-24 rounded bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-900 dark:text-blue-200 capitalize">
                                            {{ $day }}
                                        </span>
                                        <input
                                            type="text"
                                            wire:model.defer="serviceTimes.{{ $day }}"
                                            class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-gray-200"
                                        />
                                        <button
                                            type="button"
                                            wire:click="removeServiceTime('{{ $day }}')"
                                            class="text-red-500 hover:text-red-700"
                                        >
                                            <i class="fa-solid fa-times"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="flex items-end gap-2">
                            <div class="w-32">
                                <select
                                    wire:model.defer="newServiceDay"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-gray-200"
                                >
                                    <option value="sunday">Sunday</option>
                                    <option value="monday">Monday</option>
                                    <option value="tuesday">Tuesday</option>
                                    <option value="wednesday">Wednesday</option>
                                    <option value="thursday">Thursday</option>
                                    <option value="friday">Friday</option>
                                    <option value="saturday">Saturday</option>
                                </select>
                            </div>
                            <div class="flex-1">
                                <input
                                    type="text"
                                    wire:model.defer="newServiceTime"
                                    placeholder="e.g., 7:00 AM"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-gray-200"
                                />
                            </div>
                            <button
                                type="button"
                                wire:click="addServiceTime"
                                class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700"
                            >
                                Add
                            </button>
                        </div>
                    </div>

                    <p class="text-xs text-slate-500 dark:text-gray-400">
                        Tip: Enter negative values for southern latitudes and western longitudes.
                    </p>

                    <button
                        type="submit"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
                    >
                        <i class="fa-solid fa-location-dot me-2"></i>
                        Save Location & Details
                    </button>
                </form>
            @else
                <div class="flex h-full items-center justify-center rounded-lg border-2 border-dashed border-slate-200 p-10 text-center dark:border-zinc-600">
                    <div>
                        <i class="fa-solid fa-map-pin text-4xl text-slate-300 dark:text-zinc-600"></i>
                        <p class="mt-4 text-sm text-slate-500 dark:text-gray-400">
                            Select a chapter from the list to set its location coordinates and details.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-slate-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
        <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-gray-100">Quick Reference</h3>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg bg-slate-50 p-4 dark:bg-zinc-700">
                <p class="text-xs text-slate-500 dark:text-gray-400">Sample Coordinates</p>
                <p class="mt-1 font-mono text-sm text-slate-900 dark:text-gray-100">Lagos: 6.5244, 3.3792</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-4 dark:bg-zinc-700">
                <p class="text-xs text-slate-500 dark:text-gray-400">Sample Coordinates</p>
                <p class="mt-1 font-mono text-sm text-slate-900 dark:text-gray-100">Abuja: 9.0765, 7.3986</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-4 dark:bg-zinc-700">
                <p class="text-xs text-slate-500 dark:text-gray-400">Sample Coordinates</p>
                <p class="mt-1 font-mono text-sm text-slate-900 dark:text-gray-100">Port Harcourt: 4.7774, 7.0134</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-4 dark:bg-zinc-700">
                <p class="text-xs text-slate-500 dark:text-gray-400">Sample Coordinates</p>
                <p class="mt-1 font-mono text-sm text-slate-900 dark:text-gray-100">Ibadan: 7.3775, 3.9470</p>
            </div>
        </div>
    </div>
</div>