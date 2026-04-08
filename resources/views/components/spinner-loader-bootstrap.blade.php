@props([
    'size' => 'md',      // xs, sm, md, lg, xl, 2xl, 3xl
    'variant' => 'primary', // bootstrap color variant (primary, secondary, danger, success, etc.)
    'grow' => false,     // use "grow" animation instead of border spinner
])

@php
    // Map semantic sizes to width/height (Bootstrap doesn't have many sizes natively)
    $sizeMap = [
        'xs'  => '1rem',
        'sm'  => '1.25rem',
        'md'  => '1.5rem',
        'lg'  => '2rem',
        'xl'  => '2.5rem',
        '2xl' => '3rem',
        '3xl' => '4rem',
    ];

    $dimension = $sizeMap[$size] ?? $sizeMap['md'];
    $spinnerClass = $grow ? 'spinner-grow' : 'spinner-border';
@endphp

<div role="status" {{ $attributes->merge(['class' => 'd-inline-flex align-items-center']) }}>
    <div 
        class="{{ $spinnerClass }} text-{{ $variant }}" 
        style="width: {{ $dimension }}; height: {{ $dimension }};" 
        role="status"
    >
        <span class="visually-hidden">Loading...</span>
    </div>
</div>
