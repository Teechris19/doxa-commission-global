<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1877f2">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    @php
        $globalSettings = \App\Models\GlobalSetting::query()
            ->orderByDesc('updated_at')
            ->first();
    @endphp

    <meta name="apple-mobile-web-app-title" content="{{ $globalSettings?->church_name ?? 'Doxa Church' }}">

    <title>{{ isset($title) ? $title.' - ' : '' }}{{ $globalSettings?->meta_title ?: $globalSettings?->church_name ?: config('app.name', 'Doxa Commission Global') }}</title>

    @if($globalSettings?->meta_description)
        <meta name="description" content="{{ $globalSettings->meta_description }}">
    @endif

    @if($globalSettings?->meta_keywords)
        <meta name="keywords" content="{{ $globalSettings->meta_keywords }}">
    @endif

    @if($globalSettings?->favicon)
        <link rel="icon" type="image/png" href="{{ asset('storage/' . $globalSettings->favicon) }}">
        <link rel="shortcut icon" type="image/png" href="{{ asset('storage/' . $globalSettings->favicon) }}">
    @endif

    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ $globalSettings?->logo ? asset('storage/' . $globalSettings->logo) : asset('apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="site-shell font-['Poppins']">
    <x-flash />

    @php
        $globalSettings = \App\Models\GlobalSetting::query()
            ->orderByDesc('updated_at')
            ->first();
        $footerDescription = $globalSettings?->footer_description
            ?: ($globalSettings?->tagline ?? "Bringing nations into God's glory worldwide.");
        $footerSocial = json_decode($globalSettings?->social_links ?? '{}', true) ?? [];

        $primaryNav = [
            ['label' => 'Home', 'route' => 'home', 'active' => request()->routeIs('home'), 'icon' => 'fa-house'],
            ['label' => 'About', 'route' => 'about', 'active' => request()->routeIs('about'), 'icon' => 'fa-circle-info'],
            ['label' => 'Conclaves', 'route' => 'conclaves.index', 'active' => request()->routeIs('conclaves.*'), 'icon' => 'fa-map-location-dot'],
            ['label' => 'Events', 'route' => 'events.index', 'active' => request()->routeIs('events.*'), 'icon' => 'fa-calendar-days'],
            ['label' => 'Messages', 'route' => 'sermons.index', 'active' => request()->routeIs('sermons.*'), 'icon' => 'fa-circle-play'],
            ['label' => 'Cells', 'route' => 'cells.index', 'active' => request()->routeIs('cells.*'), 'icon' => 'fa-people-group'],
            ['label' => 'Location', 'route' => 'location.index', 'active' => request()->routeIs('location.*'), 'icon' => 'fa-location-dot'],
        ];

        $secondaryNav = [
            ['label' => 'Believers Academy', 'route' => 'believers.academy', 'active' => request()->routeIs('believers.*')],
            ['label' => 'Partnership', 'route' => 'home.partnership.index', 'active' => request()->routeIs('home.partnership.*')],
            ['label' => 'Need a Ride', 'route' => 'transport', 'active' => request()->routeIs('transport')],
        ];

        $ongoingService = null;
        $ongoingServiceEndIso = null;

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('landing_page_settings')) {
                $hasChapterColumn = \Illuminate\Support\Facades\Schema::hasColumn('landing_page_settings', 'chapter_id');
                $chapterId = null;

                $requestedChapter = request()->query('chapter');
                if ($requestedChapter) {
                    $chapterId = \App\Models\Chapter::where('name', $requestedChapter)->value('id');
                }

                if (!$chapterId && auth()->check()) {
                    $chapterId = auth()->user()->chapter_id;
                }

                $settings = null;
                if ($hasChapterColumn) {
                    if ($chapterId) {
                        $settings = \App\Models\LandingPageSetting::where('chapter_id', $chapterId)->first();
                    }

                    if (!$settings) {
                        $settings = \App\Models\LandingPageSetting::whereNull('chapter_id')->first() ?? \App\Models\LandingPageSetting::first();
                    }
                } else {
                    $settings = \App\Models\LandingPageSetting::first();
                }

                $services = collect($settings?->services ?? [])->filter(function ($service) {
                    return !empty($service['name']) &&
                        !empty($service['day_of_week']) &&
                        !empty($service['start_time']) &&
                        !empty($service['end_time']);
                });

                $dayMap = [
                    'sunday' => 0,
                    'monday' => 1,
                    'tuesday' => 2,
                    'wednesday' => 3,
                    'thursday' => 4,
                    'friday' => 5,
                    'saturday' => 6,
                ];

                $now = \Carbon\Carbon::now(config('app.timezone', 'UTC'));
                $activeService = null;
                $activeServiceEnd = null;

                foreach ($services as $service) {
                    $dayName = strtolower(trim((string) $service['day_of_week']));
                    if (!isset($dayMap[$dayName])) {
                        continue;
                    }

                    [$startHour, $startMinute] = array_pad(array_map('intval', explode(':', (string) $service['start_time'])), 2, 0);
                    [$endHour, $endMinute] = array_pad(array_map('intval', explode(':', (string) $service['end_time'])), 2, 0);

                    $start = $now->copy()->startOfDay();
                    $offset = ($dayMap[$dayName] - $now->dayOfWeek + 7) % 7;
                    $start->addDays($offset)->setTime($startHour, $startMinute, 0);

                    $end = $start->copy()->setTime($endHour, $endMinute, 0);
                    if ($end->lessThanOrEqualTo($start)) {
                        $end->addDay();
                    }

                    if ($now->greaterThan($end)) {
                        $start->addWeek();
                        $end->addWeek();
                    }

                    if ($now->greaterThanOrEqualTo($start) && $now->lessThan($end)) {
                        if (!$activeServiceEnd || $end->lessThan($activeServiceEnd)) {
                            $activeService = $service;
                            $activeServiceEnd = $end->copy();
                        }
                    }
                }

                if ($activeService && $activeServiceEnd) {
                    $ongoingService = $activeService;
                    $ongoingServiceEndIso = $activeServiceEnd->toIso8601String();
                }
            }
        } catch (\Throwable $e) {
            $ongoingService = null;
            $ongoingServiceEndIso = null;
        }
    @endphp

    @if($ongoingService && $ongoingServiceEndIso)
        <div class="border-b border-blue-500 bg-blue-700 text-white">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-1 px-4 py-2.5 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-white/20 px-2.5 py-1 text-[0.6rem] font-semibold uppercase tracking-[0.12em]">Ongoing Service</span>
                    <p class="text-xs font-medium sm:text-sm">
                        {{ $ongoingService['name'] }}
                        @if(!empty($ongoingService['location']))
                            • {{ $ongoingService['location'] }}
                        @endif
                    </p>
                </div>
                <p id="ongoing-service-countdown" data-service-end="{{ $ongoingServiceEndIso }}" class="text-xs font-semibold text-blue-100 sm:text-sm">
                    Updating timer...
                </p>
            </div>
        </div>
    @endif

    <header class="sticky top-0 z-50 border-b border-blue-100/80 bg-blue-50/80 shadow-sm backdrop-blur supports-[backdrop-filter]:bg-blue-50/70">
        <nav class="mx-auto flex min-h-[3.5rem] w-full max-w-7xl items-center justify-between px-3 py-2 sm:px-5 lg:px-8">
            <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-3">
                <img src="{{ $globalSettings?->logo ? asset('storage/' . $globalSettings->logo) : asset('Img/doxa.PNG') }}" 
                     alt="{{ $globalSettings?->church_name ?? 'Doxa Commission Global' }}" 
                     class="h-8 w-8 rounded-full object-cover ring-1 ring-blue-100 sm:h-9 sm:w-9">
                <span class="hidden text-[0.7rem] font-semibold tracking-wide text-blue-800 sm:block">{{ $globalSettings?->church_name ?? 'Doxa Commission Global' }}</span>
            </a>

            <div class="hidden items-center gap-1.5 lg:flex">
                @foreach($primaryNav as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        wire:navigate
                        @class([
                            'rounded-full px-2.5 py-1 text-[0.7rem] font-medium transition-colors',
                            'bg-blue-600 text-white' => $item['active'],
                            'text-slate-700 hover:bg-blue-50 hover:text-blue-700' => !$item['active'],
                        ])
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach

                @foreach($secondaryNav as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        wire:navigate
                        @class([
                            'rounded-full px-2.5 py-1 text-[0.7rem] font-medium transition-colors',
                            'bg-blue-600 text-white' => $item['active'],
                            'text-slate-600 hover:bg-blue-50 hover:text-blue-700' => !$item['active'],
                        ])
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>

            <div class="hidden items-center gap-2 lg:flex">
                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-full border border-blue-200 px-2.5 py-1 text-[0.7rem] font-medium text-blue-700 transition hover:border-blue-300 hover:text-blue-800">
                            Logout
                        </button>
                    </form>
                @else
                    <a href="{{ route('home.login') }}" wire:navigate class="rounded-full bg-blue-600 px-3 py-1 text-[0.7rem] font-semibold text-white transition hover:bg-blue-700">
                        Connect With Us
                    </a>
                @endauth
            </div>

            <div class="flex items-center gap-2 lg:hidden">
                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-full border border-blue-200 px-2.5 py-1 text-[0.7rem] font-medium text-blue-700">Logout</button>
                    </form>
                @else
                    <a href="{{ route('home.login') }}" wire:navigate class="rounded-full bg-blue-600 px-2.5 py-1 text-[0.7rem] font-semibold text-white">Login</a>
                @endauth
                
                {{-- Hamburger Menu Button --}}
                <button id="hamburger-menu-btn" class="hamburger-btn" aria-label="Toggle menu">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>
            </div>
        </nav>
    </header>

    <main class="min-h-screen">
        {{ $slot }}
    </main>

    <x-announcement-popup />

    <footer class="mt-14 border-t border-blue-100 bg-blue-700 text-blue-50">
        <div class="mx-auto grid w-full max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-3 lg:px-8">
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <img src="{{ $globalSettings?->logo ? asset('storage/' . $globalSettings->logo) : asset('Img/doxa.PNG') }}" alt="Logo" class="h-16 w-16 rounded-lg object-contain">
                    <h3 class="text-xl font-semibold text-white">{{ $globalSettings?->church_name ?? 'Doxa Commission Global' }}</h3>
                </div>
                <p class="text-sm text-blue-100">{{ $footerDescription }}</p>
            </div>

            <div class="space-y-3">
                <h3 class="text-base font-semibold text-white">Quick Links</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('home') }}" wire:navigate class="text-blue-100 transition hover:text-white">Home</a></li>
                    <li><a href="{{ route('about') }}" wire:navigate class="text-blue-100 transition hover:text-white">About Us</a></li>
                    <li><a href="{{ route('conclaves.index') }}" wire:navigate class="text-blue-100 transition hover:text-white">Conclaves</a></li>
                    <li><a href="{{ route('events.index') }}" wire:navigate class="text-blue-100 transition hover:text-white">Events</a></li>
                    <li><a href="{{ route('sermons.index') }}" wire:navigate class="text-blue-100 transition hover:text-white">Messages</a></li>
                    <li><a href="{{ route('cells.index') }}" wire:navigate class="text-blue-100 transition hover:text-white">Cells</a></li>
                    <li><a href="{{ route('location.index') }}" wire:navigate class="text-blue-100 transition hover:text-white">Location</a></li>
                </ul>
            </div>

            <div class="space-y-4">
                <h3 class="text-base font-semibold text-white">Live On Social</h3>
                <div class="flex flex-wrap gap-2 text-sm">
                    @if (!empty($footerSocial['facebook']))
                        <a href="{{ $footerSocial['facebook'] }}" target="_blank" rel="noopener noreferrer" class="rounded-full border border-blue-300/60 px-3 py-1.5 text-blue-50 transition hover:border-white hover:text-white">Facebook Live</a>
                    @endif
                    @if (!empty($footerSocial['twitter']))
                        <a href="{{ $footerSocial['twitter'] }}" target="_blank" rel="noopener noreferrer" class="rounded-full border border-blue-300/60 px-3 py-1.5 text-blue-50 transition hover:border-white hover:text-white">X / Twitter</a>
                    @endif
                    @if (!empty($footerSocial['youtube']))
                        <a href="{{ $footerSocial['youtube'] }}" target="_blank" rel="noopener noreferrer" class="rounded-full border border-blue-300/60 px-3 py-1.5 text-blue-50 transition hover:border-white hover:text-white">YouTube</a>
                    @endif
                </div>
                <div class="flex items-center gap-3 text-lg">
                    @if (!empty($footerSocial['facebook']))
                        <a href="{{ $footerSocial['facebook'] }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-900/50 text-blue-200 transition-all duration-300 hover:bg-white hover:text-blue-600">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                    @endif
                    @if (!empty($footerSocial['instagram']))
                        <a href="{{ $footerSocial['instagram'] }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-900/50 text-blue-200 transition-all duration-300 hover:bg-white hover:text-pink-500">
                            <i class="fab fa-instagram"></i>
                        </a>
                    @endif
                    @if (!empty($footerSocial['youtube']))
                        <a href="{{ $footerSocial['youtube'] }}" target="_blank" rel="noopener noreferrer" aria-label="YouTube" class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-900/50 text-blue-200 transition-all duration-300 hover:bg-white hover:text-red-600">
                            <i class="fab fa-youtube"></i>
                        </a>
                    @endif
                    @if (!empty($footerSocial['tiktok']))
                        <a href="{{ $footerSocial['tiktok'] }}" target="_blank" rel="noopener noreferrer" aria-label="TikTok" class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-900/50 text-blue-200 transition-all duration-300 hover:bg-white hover:text-black">
                            <i class="fab fa-tiktok"></i>
                        </a>
                    @endif
                    @if (!empty($footerSocial['telegram']))
                        <a href="{{ $footerSocial['telegram'] }}" target="_blank" rel="noopener noreferrer" aria-label="Telegram" class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-900/50 text-blue-200 transition-all duration-300 hover:bg-white hover:text-blue-500">
                            <i class="fab fa-telegram-plane"></i>
                        </a>
                    @endif
                    @if (!empty($footerSocial['spotify']))
                        <a href="{{ $footerSocial['spotify'] }}" target="_blank" rel="noopener noreferrer" aria-label="Spotify" class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-900/50 text-blue-200 transition-all duration-300 hover:bg-white hover:text-green-500">
                            <i class="fab fa-spotify"></i>
                        </a>
                    @endif
                    @if (!empty($footerSocial['whatsapp']))
                        <a href="{{ $footerSocial['whatsapp'] }}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp" class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-900/50 text-blue-200 transition-all duration-300 hover:bg-white hover:text-green-500">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="border-t border-blue-600 px-4 py-5 text-center text-xs text-blue-100">
            &copy; <span id="current-year"></span> {{ $globalSettings?->church_name ?? 'Doxa Commission Global' }}. All Rights Reserved.
        </div>
    </footer>

    {{-- Old Mobile Floating Nav (Hidden - Replaced by Hamburger Menu) --}}
    <nav class="mobile-floating-nav hidden lg:hidden" aria-label="Primary">
        @foreach($primaryNav as $item)
            <a
                href="{{ route($item['route']) }}"
                wire:navigate
                @class([
                    'mobile-nav-item',
                    'is-active' => $item['active'],
                ])
            >
                <i class="fas {{ $item['icon'] }} text-[0.9rem]" aria-hidden="true"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    {{-- Mobile Drawer Menu --}}
    <div id="mobile-drawer" class="mobile-drawer" aria-hidden="true">
        <div id="mobile-drawer-overlay" class="mobile-drawer-overlay"></div>
        <div class="mobile-drawer-panel">
            <div class="border-b border-blue-100 px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <img src="{{ $globalSettings?->logo ? asset('storage/' . $globalSettings->logo) : asset('Img/doxa.PNG') }}" 
                             alt="{{ $globalSettings?->church_name ?? 'Doxa Commission Global' }}" 
                             class="h-8 w-8 rounded-full object-cover ring-1 ring-blue-100">
                        <span class="text-sm font-semibold text-blue-800">{{ $globalSettings?->church_name ?? 'Doxa Commission Global' }}</span>
                    </div>
                    <button id="close-drawer-btn" class="rounded-full p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div class="py-2">
                @foreach($primaryNav as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        wire:navigate
                        @class([
                            'mobile-drawer-item',
                            'is-active' => $item['active'],
                        ])
                    >
                        <i class="fas {{ $item['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>

            <div class="border-t border-blue-100 px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">More</p>
            </div>

            <div class="py-2">
                @foreach($secondaryNav as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        wire:navigate
                        @class([
                            'mobile-drawer-item',
                            'is-active' => $item['active'],
                        ])
                    >
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>

            @auth
                <div class="border-t border-blue-100 p-4">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full rounded-full bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                            Logout
                        </button>
                    </form>
                </div>
            @else
                <div class="border-t border-blue-100 p-4">
                    <a href="{{ route('home.login') }}" wire:navigate class="block w-full rounded-full bg-blue-600 px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-blue-700">
                        Connect With Us
                    </a>
                </div>
            @endauth
        </div>
    </div>

    <script>
        (() => {
            const yearElement = document.getElementById('current-year');
            if (yearElement) {
                yearElement.textContent = new Date().getFullYear();
            }

            const ongoingCountdown = document.getElementById('ongoing-service-countdown');
            if (ongoingCountdown) {
                const endIso = ongoingCountdown.dataset.serviceEnd;

                const formatRemaining = (ms) => {
                    if (ms <= 0) return 'Service ending now';
                    const totalMinutes = Math.floor(ms / 60000);
                    const hours = Math.floor(totalMinutes / 60);
                    const minutes = totalMinutes % 60;
                    if (hours > 0) {
                        return `${hours}h ${minutes}m left`;
                    }
                    return `${Math.max(1, minutes)}m left`;
                };

                const tick = () => {
                    const end = new Date(endIso);
                    const now = new Date();
                    const diff = end.getTime() - now.getTime();

                    if (diff <= 0) {
                        ongoingCountdown.textContent = 'Service ending now';
                        return;
                    }

                    ongoingCountdown.textContent = `Ends in ${formatRemaining(diff)}`;
                };

                tick();
                if (!ongoingCountdown.dataset.intervalId) {
                    const intervalId = window.setInterval(tick, 30000);
                    ongoingCountdown.dataset.intervalId = String(intervalId);
                }
            }

            // Mobile Drawer Functionality
            const hamburgerBtn = document.getElementById('hamburger-menu-btn');
            const mobileDrawer = document.getElementById('mobile-drawer');
            const mobileDrawerOverlay = document.getElementById('mobile-drawer-overlay');
            const closeDrawerBtn = document.getElementById('close-drawer-btn');

            function openDrawer() {
                if (!mobileDrawer) return;
                mobileDrawer.classList.add('open');
                mobileDrawer.setAttribute('aria-hidden', 'false');
                if (hamburgerBtn) {
                    hamburgerBtn.classList.add('active');
                }
                document.body.style.overflow = 'hidden';
            }

            function closeDrawer() {
                if (!mobileDrawer) return;
                mobileDrawer.classList.remove('open');
                mobileDrawer.setAttribute('aria-hidden', 'true');
                if (hamburgerBtn) {
                    hamburgerBtn.classList.remove('active');
                }
                document.body.style.overflow = '';
            }

            if (hamburgerBtn) {
                hamburgerBtn.addEventListener('click', function() {
                    const isOpen = mobileDrawer.classList.contains('open');
                    if (isOpen) {
                        closeDrawer();
                    } else {
                        openDrawer();
                    }
                });
            }

            if (mobileDrawerOverlay) {
                mobileDrawerOverlay.addEventListener('click', closeDrawer);
            }

            if (closeDrawerBtn) {
                closeDrawerBtn.addEventListener('click', closeDrawer);
            }

            // Close drawer on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeDrawer();
                }
            });

            // Close drawer when clicking nav links
            document.addEventListener('livewire:navigated', function() {
                closeDrawer();
            });

            // Close drawer on window resize to desktop
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    if (window.innerWidth >= 1024) {
                        closeDrawer();
                    }
                }, 100);
            });
        })();
    </script>

    @livewireScripts
    @fluxScripts
</body>
</html>
