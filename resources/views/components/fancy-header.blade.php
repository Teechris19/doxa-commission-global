@props([
    'title' => null,
    'subtitle' => null,
    'breadcrumbs' => [],
])

<header class="relative overflow-hidden rounded-lg border border-slate-200/70 bg-white/90 px-4 py-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/80 sm:px-6 lg:px-8">
    <div class="relative mx-auto max-w-7xl">
        @if ($breadcrumbs)
            <nav class="flex text-xs" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1">
                    @foreach ($breadcrumbs as $breadcrumb)
                        <li>
                            <div class="flex items-center">
                                @if (!$loop->first)
                                    <span class="text-gray-400">/</span>
                                @endif
                                <a href="{{ $breadcrumb['url'] ?? '#' }}"
                                   class="ml-1 text-xs font-medium {{ $loop->last ? 'text-slate-700 dark:text-slate-200 cursor-default' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }} rounded-md"
                                   aria-current="{{ $loop->last ? 'page' : false }}">
                                    @if ($loop->first)
                                        <svg class="mr-1 inline h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                        </svg>
                                    @endif
                                    {{ $breadcrumb['label'] }}
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        @if ($title)
            <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                {{ $title }}
            </h1>
        @endif

        @if ($subtitle)
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                {{ $subtitle }}
            </p>
        @endif

        {{-- Action Slot --}}
        @if (trim($slot))
            <div class="mt-3">
                {{ $slot }}
            </div>
        @endif
    </div>
</header>
