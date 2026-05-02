<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
{{--
TODO: Add Active route indicators
--}}
<head>
    <tallstackui:script />
    {{-- <script src="/tinymce/js/tinymce/tinymce.min.js"></script> --}}
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>

    <style>
        /* Global Fix for Toasts and Dialogs appearing behind Modals */
        [id^="tallstackui_toast"], [id^="tallstackui_dialog"] {
            z-index: 9999 !important;
        }

        /* Hide labels when Flux sidebar is stashed */
        [data-flux-sidebar][data-flux-stashed] .stash-hide {
            display: none !important;
        }
        
        /* Adjust logo container when stashed */
        [data-flux-sidebar][data-flux-stashed] .flex.h-16 {
            padding: 0.5rem !important;
            justify-content: center !important;
        }
    </style>

    @livewireStyles
    @include('partials.head')
    @stack('styles')
    @fluxScripts

    @php
        $isSuperAdmin = auth()->check() && auth()->user()->hasRole('super-admin');
        $manifestUrl = $isSuperAdmin ? route('pwa.super-admin-manifest') : route('pwa.admin-manifest');
    @endphp
    <link rel="manifest" href="{{ $manifestUrl }}">
    <meta name="theme-color" content="#2563eb">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <script>
        // Apply theme from session on page load
        (function() {
            const theme = '{{ session('theme', 'system') }}';
            const html = document.documentElement;
            
            if (theme === 'dark') {
                html.classList.add('dark');
            } else if (theme === 'light') {
                html.classList.remove('dark');
            } else {
                // System preference
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    html.classList.add('dark');
                } else {
                    html.classList.remove('dark');
                }
            }
        })();
    </script>
</head>

@php
    $globalSettings = \App\Models\GlobalSetting::first();
    $adminLogo = $globalSettings?->logo ? asset('storage/' . $globalSettings->logo) : null;
    $adminName = $globalSettings?->church_name ?? config('app.name', 'Doxa Commission Global');
    $user = auth()->user();
    $isSuperAdmin = $user && $user->hasRole('super-admin');
    $isAdmin = $user && $user->hasRole('admin');
    $isTeamLeader = $user && $user->hasRole(['team-lead', 'lead-assist', 'lead_assist']);
    $sidebarLabel = $isSuperAdmin ? 'Super Admin' : ($isTeamLeader ? 'Team Lead' : 'Admin');

    // Check if user is an attendance team lead
    $isAttendanceTeamLead = false;
    $leadersTeam = null;
    if ($isTeamLeader) {
        $leadersTeam = $user->teams->firstWhere(fn($team) => in_array($team->pivot->role_in_team, ['team-lead', 'lead-assist', 'lead_assist']));
        if ($leadersTeam) {
            $attendanceTeams = \App\Models\AttendanceTeams::where('chapter_id', $leadersTeam->chapter_id)->pluck('team_id')->all();
            $isAttendanceTeamLead = in_array($leadersTeam->id, $attendanceTeams);
        }
    }
@endphp

<body class="min-h-screen bg-slate-50 font-['Poppins'] text-slate-900 dark:bg-zinc-900 dark:text-gray-200">
    <div class="relative min-h-screen bg-[radial-gradient(circle_at_top,rgba(59,130,246,0.06),transparent_55%)] dark:bg-none">
        <flux:sidebar id="admin-sidebar" sticky stashable class="border-e border-slate-200 bg-white text-slate-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-gray-200">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <div class="flex h-16 items-center px-4 mb-4">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rtl:space-x-reverse" wire:navigate>
                    <span class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-2xl bg-blue-50 ring-1 ring-blue-100 dark:bg-blue-900/30 dark:ring-blue-800">
                        @if ($adminLogo)
                            <img src="{{ $adminLogo }}" alt="{{ $adminName }}" class="h-full w-full object-contain">
                        @else
                            <x-app-logo-icon class="size-7 fill-current text-blue-700 dark:text-blue-400" />
                        @endif
                    </span>
                    <div class="leading-tight stash-hide">
                        <p class="text-xs uppercase tracking-[0.3em] text-slate-400 dark:text-gray-500">{{ $sidebarLabel }}</p>
                        <p class="text-sm font-semibold text-slate-900 dark:text-gray-200">{{ $adminName }}</p>
                    </div>
                </a>
            </div>

        <flux:navlist variant="outline" class="text-sm">
            <flux:navlist.group :heading="__('Platform')" class="grid text-slate-400">
                <flux:navlist.item icon="home" :href="route('admin.dashboard', request()->query())"
                    :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                @role(['admin', 'super-admin'])
                <flux:navlist.item icon="megaphone" :href="route('admin.dashboard.announcements.index', request()->query())"
                    :current="request()->routeIs('admin.dashboard.announcements.*')" wire:navigate>
                    {{ __('Announcements') }}
                </flux:navlist.item>
                @endrole
                @role(['admin', 'super-admin', 'team-lead', 'lead-assist', 'lead_assist'])
                <flux:navlist.item icon="cog-6-tooth" :href="route('settings.profile')" wire:navigate>
                    {{ __('Profile Settings') }}
                </flux:navlist.item>
                @endrole
            </flux:navlist.group>
        </flux:navlist>
        <flux:navlist variant="outline" class="text-sm">

             @role(['admin', 'team-lead', 'lead-assist', 'lead_assist', 'super-admin'])
            <flux:navlist.group expandable heading="Members">
                @if($isSuperAdmin || $isAdmin)
                <flux:navlist.item icon="users" :href="route('admin.dashboard.members', ['chapter' => request()->get('chapter')])"
                    wire:navigate :active="request()->routeIs('admin.dashboard.members')">
                    All Members
                </flux:navlist.item>
                @endif
                
                @role(['team-lead', 'lead-assist', 'lead_assist'])
                @if(!$isAttendanceTeamLead)
                <flux:navlist.item icon="users"
                    :href="route('admin.dashboard.members', ['chapter' => request()->get('chapter')])"
                    wire:navigate :active="request()->routeIs('admin.dashboard.members')">
                    View Members
                </flux:navlist.item>
                <flux:navlist.item icon="user-plus" :href="route('admin.members.add-to-team', ['chapter' => request()->get('chapter')])"
                    wire:navigate :active="request()->routeIs('admin.members.add-to-team')">
                    Add Member
                </flux:navlist.item>
                @endif
                @endrole

                @if($isSuperAdmin || $isAdmin)
                <flux:navlist.item icon="user-plus"
                    :href="route('admin.dashboard.members.create', ['chapter' => request()->get('chapter')])"
                    wire:navigate :active="request()->routeIs('admin.dashboard.members.create')">
                    Create New Member
                </flux:navlist.item>
                @endif

                @if($isSuperAdmin || $isAdmin)
                <flux:navlist.item icon="user-group" :href="route('admin.members.add-to-team', ['chapter' => request()->get('chapter')])"
                    wire:navigate :active="request()->routeIs('admin.members.add-to-team')">
                    Add Member To Team
                </flux:navlist.item>
                @endif
            </flux:navlist.group>

            {{-- Report Group --}}

            @endrole
             @role(['admin', 'super-admin'])
            {{-- Teams Group --}}
            <flux:navlist.group expandable heading="Teams">
                <flux:navlist.item icon="users" :href="route('admin.dashboard.teams', ['chapter' => request()->get('chapter')])" wire:navigate
                    :active="request()->routeIs('admin.dashboard.teams')">
                    All Teams
                </flux:navlist.item>

                <flux:navlist.item icon="plus-circle" :href="route('admin.dashboard.teams.create', ['chapter' => request()->get('chapter')])" wire:navigate
                    :active="request()->routeIs('admin.dashboard.teams.create')">
                    Create New Team
                </flux:navlist.item>

                <flux:navlist.item icon="user-circle" :href="route('admin.dashboard.teams.edit-leader', ['chapter' => request()->get('chapter')])"
                    :active="request()->routeIs('admin.dashboard.teams.leader')" wire:navigate>
                    Team Leader
                </flux:navlist.item>
            </flux:navlist.group>

            @endrole
            @role('super-admin')
            <flux:navlist.group expandable heading="Chapters">
                <flux:navlist.item icon="building-office" :href="route('super-admin.conclaves', request()->query())" wire:navigate
                    :active="request()->routeIs('super-admin.conclaves')">
                    All Chapters
                </flux:navlist.item>
                <flux:navlist.item icon="plus-circle" :href="route('super-admin.conclaves.create', request()->query())" wire:navigate
                    :active="request()->routeIs('super-admin.conclaves.create')">
                    Create Chapter
                </flux:navlist.item>
                <flux:navlist.item icon="user-plus" :href="route('super-admin.conclaves.add-admin', request()->query())" wire:navigate
                    :active="request()->routeIs('super-admin.conclaves.add-admin')">
                    Assign Chapter Admin
                </flux:navlist.item>
            </flux:navlist.group>

            <flux:navlist.group expandable heading="Locations">
                <flux:navlist.item icon="map-pin" :href="route('super-admin.locations', request()->query())" wire:navigate
                    :active="request()->routeIs('super-admin.locations')">
                    Chapter Locations
                </flux:navlist.item>
            </flux:navlist.group>
            
            {{-- Conclaves Management (Separate from Chapters) --}}
            <flux:navlist.group expandable heading="Conclaves">
                <flux:navlist.item icon="map" :href="route('admin.dashboard.conclaves', request()->query())" wire:navigate
                    :active="request()->routeIs('admin.dashboard.conclaves')">
                    All Conclaves
                </flux:navlist.item>
            </flux:navlist.group>
            @endrole
            @include('partials.team-based-menu')
        </flux:navlist>
    
       
        <flux:spacer />

        <flux:navlist variant="outline" class="text-sm">
            <flux:navlist.item icon="chat-bubble-left-right" :href="route('admin.dashboard.settings.request-message', request()->query())" wire:navigate
                :active="request()->routeIs('admin.dashboard.settings.request-message')">
                Request Message
            </flux:navlist.item>
        </flux:navlist>

        <div class="flex items-center justify-center py-2">
            <livewire:admin.components.notifications />
        </div>

        

        <!-- Desktop User Menu -->
        <flux:dropdown class="hidden lg:block" position="bottom" align="start">
            <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()"
                icon:trailing="chevrons-up-down" />

            <flux:menu class="w-[220px]">
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header sticky class="border-b border-slate-200/80 bg-white/95 backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        <flux:sidebar.toggle class="hidden lg:inline-flex" icon="bars-2" inset="left" />

        <flux:spacer />

        <livewire:admin.components.notifications />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>
    <flux:main class="min-h-screen bg-slate-50 dark:bg-zinc-950">
        @role('super-admin')
            <livewire:admin.components.chapter-switcher />
        @endrole
        {{ $slot }}
    </flux:main>

    {{-- PWA Install Prompt UI --}}
    <div id="pwa-install-prompt" class="fixed bottom-4 left-4 right-4 z-[100] hidden animate-slide-up sm:left-auto sm:right-4 sm:max-w-md">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 ring-1 ring-blue-100 dark:bg-blue-900/30 dark:ring-blue-800">
                        @if ($adminLogo)
                            <img src="{{ $adminLogo }}" alt="{{ $adminName }}" class="h-8 w-8 object-contain">
                        @else
                            <x-app-logo-icon class="size-8 fill-current text-blue-700 dark:text-blue-400" />
                        @endif
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-gray-100">Add to Home Screen</h3>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">Install the {{ $adminName }} app for a better experience and quick access.</p>
                    </div>
                </div>
                <button id="pwa-install-close" class="rounded-full p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-zinc-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mt-6 flex items-center gap-3">
                <button id="pwa-install-btn" class="flex-1 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-700 transition-colors">
                    Install App
                </button>
                <button id="pwa-dismiss-btn" class="flex-1 rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-200 dark:bg-zinc-800 dark:text-gray-300 dark:hover:bg-zinc-700 transition-colors">
                    Maybe later
                </button>
            </div>
        </div>
    </div>
    </div>

    {{-- PWA Scripts --}}
    <script>
        // Register Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/admin-sw.js', { scope: '/admin' })
                    .then(async (reg) => {
                        console.log('Admin SW registered');
                        try {
                            let sub = await reg.pushManager.getSubscription();
                            if (!sub && Notification.permission === 'granted') {
                                const vapidPublicKey = '{{ config('services.webpush.public_key', '') }}';
                                if (vapidPublicKey) {
                                    sub = await reg.pushManager.subscribe({
                                        userVisibleOnly: true,
                                        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
                                    });
                                    await fetch('/pwa/subscribe', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                        },
                                        body: JSON.stringify({ subscription: sub }),
                                    });
                                }
                            }
                        } catch (err) {
                            console.log('Push subscription failed:', err);
                        }
                    })
                    .catch(err => console.log('Admin SW registration failed:', err));
            });
        }

        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }

        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    </script>

    {{-- PWA Install Prompt --}}
    <script>
        (() => {
            let deferredPrompt = null;
            const promptEl = document.getElementById('pwa-install-prompt');
            const installBtn = document.getElementById('pwa-install-btn');
            const closeBtn = document.getElementById('pwa-install-close');
            const dismissBtn = document.getElementById('pwa-dismiss-btn');

            if (!promptEl) return;

            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
                const dismissed = localStorage.getItem('pwa_install_dismissed');
                const dismissedAt = dismissed ? parseInt(dismissed) : 0;
                const showAfter = Date.now() - 7 * 24 * 60 * 60 * 1000;
                if (!dismissed || dismissedAt < showAfter) {
                    promptEl.style.display = 'block';
                }
            });

            installBtn.addEventListener('click', async () => {
                if (!deferredPrompt) return;
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    promptEl.style.display = 'none';
                }
                deferredPrompt = null;
            });

            const hidePrompt = () => {
                promptEl.style.display = 'none';
                localStorage.setItem('pwa_install_dismissed', String(Date.now()));
            };

            closeBtn.addEventListener('click', hidePrompt);
            dismissBtn.addEventListener('click', hidePrompt);

            window.addEventListener('appinstalled', () => {
                promptEl.style.display = 'none';
                deferredPrompt = null;
            });
        })();
    </script>

    <script>
        (() => {
            const key = 'admin_sidebar_scroll';
            const getSidebar = () => document.getElementById('admin-sidebar');

            const restore = () => {
                const sidebar = getSidebar();
                if (!sidebar) return;
                const saved = sessionStorage.getItem(key);
                if (saved !== null) sidebar.scrollTop = Number(saved);
            };

            const bind = () => {
                const sidebar = getSidebar();
                if (!sidebar) return;
                sidebar.addEventListener('scroll', () => {
                    sessionStorage.setItem(key, String(sidebar.scrollTop));
                });
            };

            document.addEventListener('DOMContentLoaded', () => {
                bind();
                restore();
            });

            document.addEventListener('livewire:navigated', () => {
                restore();
            });

            document.addEventListener('livewire:navigating', () => {
                const sidebar = getSidebar();
                if (!sidebar) return;
                sessionStorage.setItem(key, String(sidebar.scrollTop));
            });
        })();
    </script>
    @livewireScripts
</body>

</html>
