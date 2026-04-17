<div class="p-6 dark:bg-zinc-900 dark:text-gray-200">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-slate-900 dark:text-gray-100">Chapter Locations</h2>
        <p class="mt-1 text-sm text-slate-600 dark:text-gray-400">
            Set geographic coordinates for each chapter to display on the public Location page.
        </p>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800 dark:bg-green-900/30 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_1.5fr]">
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
                    Set Location
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

                    <p class="text-xs text-slate-500 dark:text-gray-400">
                        Tip: Enter negative values for southern latitudes and western longitudes.
                    </p>

                    <button
                        type="submit"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
                    >
                        <i class="fa-solid fa-location-dot me-2"></i>
                        Save Location
                    </button>
                </form>
            @else
                <div class="flex h-full items-center justify-center rounded-lg border-2 border-dashed border-slate-200 p-10 text-center dark:border-zinc-600">
                    <div>
                        <i class="fa-solid fa-map-pin text-4xl text-slate-300 dark:text-zinc-600"></i>
                        <p class="mt-4 text-sm text-slate-500 dark:text-gray-400">
                            Select a chapter from the list to set its location coordinates.
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