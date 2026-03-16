<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
{{-- 
TODO: Add Active route indicators
--}}
<head>
    <tallstackui:script />
    {{-- <script src="/tinymce/js/tinymce/tinymce.min.js"></script> --}}
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>

    @include('partials.head')
    @stack('styles')
</head>

@php
    $globalSettings = \App\Models\GlobalSetting::first();
    $adminLogo = $globalSettings?->logo ? asset('storage/' . $globalSettings->logo) : null;
    $adminName = $globalSettings?->church_name ?? config('app.name', 'Doxa Commission Global');
@endphp

<body class="min-h-screen bg-slate-50 font-['Poppins'] text-slate-900">
    <div class="relative min-h-screen bg-[radial-gradient(circle_at_top,rgba(59,130,246,0.06),transparent_55%)]">
        <flux:sidebar id="admin-sidebar" sticky stashable class="border-e border-slate-200 bg-white text-slate-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('dashboard') }}" class="me-5 flex items-center gap-3 rtl:space-x-reverse" wire:navigate>
            <span class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-2xl bg-blue-50 ring-1 ring-blue-100">
                @if ($adminLogo)
                    <img src="{{ $adminLogo }}" alt="{{ $adminName }}" class="h-full w-full object-contain">
                @else
                    <x-app-logo-icon class="size-7 fill-current text-blue-700" />
                @endif
            </span>
            <div class="leading-tight">
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Admin</p>
                <p class="text-sm font-semibold text-slate-900">{{ $adminName }}</p>
            </div>
        </a>

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
            <flux:navlist.group expandable heading="Members"
                :expanded="request()->routeIs('admin.dashboard.members.*') ? 'true' : 'false'">
                <flux:navlist.item icon="users" :href="route('admin.dashboard.members', ['chapter' => request()->get('chapter')])"
                    wire:navigate :active=" request()->routeIs('admin.dashboard.members') ? 'true' : 'false' ">
                    All Members
                </flux:navlist.item>

                <flux:navlist.item icon="user-plus"
                    :href="route('admin.dashboard.members.create', ['chapter' => request()->get('chapter')])"
                    wire:navigate :active=" request()->routeIs('admin.dashboard.members.create') ? 'true' : 'false' ">
                    Create New Member
                </flux:navlist.item>
                @role('team-lead')
                <flux:navlist.item icon="user-group" :href="route('admin.members.add-to-team', ['chapter' => request()->get('chapter')])"
                    wire:navigate :active=" request()->routeIs('admin.members.add-to-team') ? 'true' : 'false' ">
                    Add Member To Team
                </flux:navlist.item>
                @endrole
            </flux:navlist.group>

            {{-- Report Group --}}
            
            @endrole
             @role(['admin', 'super-admin'])
            {{-- Teams Group --}}
            <flux:navlist.group expandable heading="Teams"
                :expanded=" request()->routeIs('admin.dashboard.teams.*') ? 'true' : 'false' ">
                <flux:navlist.item icon="users" :href="route('admin.dashboard.teams', ['chapter' => request()->get('chapter')])" wire:navigate
                    :active="request()->routeIs('admin.dashboard.teams') ? 'true' : 'false' ">
                    All Teams
                </flux:navlist.item>

                <flux:navlist.item icon="plus-circle" :href="route('admin.dashboard.teams.create', ['chapter' => request()->get('chapter')])" wire:navigate
                    :active="request()->routeIs('admin.dashboard.teams.create') ? 'true' : 'false'">
                    Create New Team
                </flux:navlist.item>

                <flux:navlist.item icon="user-circle" :href="route('admin.dashboard.teams.edit-leader', ['chapter' => request()->get('chapter')])"
                    :active="request()->routeIs('admin.dashboard.teams.leader') ? 'true' : 'false'" wire:navigate>
                    Team Leader
                </flux:navlist.item>
            </flux:navlist.group>

            @endrole
            @role('super-admin')
            <flux:navlist.group expandable heading="Conclaves"
                :expanded="request()->routeIs('super-admin.conclaves*') ? 'true' : 'false'">
                <flux:navlist.item icon="building-office" :href="route('super-admin.conclaves', request()->query())" wire:navigate
                    :active="request()->routeIs('super-admin.conclaves') ? 'true' : 'false'">
                    All Conclaves
                </flux:navlist.item>
                <flux:navlist.item icon="plus-circle" :href="route('super-admin.conclaves.create', request()->query())" wire:navigate
                    :active="request()->routeIs('super-admin.conclaves.create') ? 'true' : 'false'">
                    Create Conclave
                </flux:navlist.item>
                <flux:navlist.item icon="user-plus" :href="route('super-admin.conclaves.add-admin', request()->query())" wire:navigate
                    :active="request()->routeIs('super-admin.conclaves.add-admin') ? 'true' : 'false'">
                    Assign Conclave Admin
                </flux:navlist.item>
            </flux:navlist.group>
            @endrole
            @include('partials.team-based-menu')

            
        </flux:navlist>
    
       
        <flux:spacer />

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
    <flux:header sticky class="border-b border-slate-200/80 bg-white/95 backdrop-blur">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

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
    <flux:main class="min-h-screen bg-slate-50">
        @role('super-admin')
            <livewire:admin.components.chapter-switcher />
        @endrole
        <x-toast />
        <x-dialog />
        {{ $slot }}
    </flux:main>
    </div>


    @fluxScripts
    @stack('scripts')
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
</body>

</html>
