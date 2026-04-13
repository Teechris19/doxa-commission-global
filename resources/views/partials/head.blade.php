@php
    $globalSettings = \App\Models\GlobalSetting::query()
        ->orderByDesc('updated_at')
        ->first();
@endphp

<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ $title ?? ($globalSettings?->meta_title ?: $globalSettings?->church_name ?: config('app.name', 'Doxa Commission Global')) }}</title>

@if($globalSettings?->favicon)
    <link rel="icon" href="{{ asset('storage/' . $globalSettings->favicon) }}" type="image/png">
@else
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
@endif

@if($globalSettings?->logo)
    <link rel="apple-touch-icon" href="{{ asset('storage/' . $globalSettings->logo) }}">
@else
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
@endif

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700&display=swap" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

{{-- Custom Attendance Styles --}}
@if(request()->routeIs('admin.dashboard.attendance.*'))
    <link rel="stylesheet" href="/css/attendance.css">
@endif
