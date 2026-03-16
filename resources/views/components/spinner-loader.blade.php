@props([
    'size' => 'md',       // sm, md, lg, xl, 2xl, 3xl...
    'color' => 'gray-600' // Tailwind color e.g. gray-600, blue-500, red-600
])

@php
    // Map semantic sizes to Tailwind width/height classes
    $sizeClasses = [
        'xs' => 'w-3 h-3',
        'sm'  => 'w-4 h-4',
        'md'  => 'w-6 h-6',
        'lg'  => 'w-8 h-8',
        'xl'  => 'w-10 h-10',
        '2xl' => 'w-12 h-12',
        '3xl' => 'w-16 h-16',
    ];

    $spinnerSize = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<div role="status" {{ $attributes->merge(['class' => 'inline-flex items-center']) }}>
    <svg 
        class="{{ $spinnerSize }} text-{{ $color }} animate-spin"
        viewBox="0 0 24 24" fill="none" aria-hidden="true"
    >
        <circle 
            class="opacity-25" 
            cx="12" cy="12" r="10" 
            stroke="currentColor" stroke-width="4"
        ></circle>
        <path 
            class="opacity-75" 
            fill="currentColor" 
            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
        ></path>
    </svg>
    <span class="sr-only">Loading...</span>
</div>
