@props(['header' => null, 'minimize' => false])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-gray-200']) }}>
    @if($header)
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-sm font-medium text-slate-500 dark:text-gray-400">{{ $header }}</h3>
            @if($minimize)
                <button type="button" class="text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                    </svg>
                </button>
            @endif
        </div>
    @endif
    <div>
        {{ $slot }}
    </div>
</div>
