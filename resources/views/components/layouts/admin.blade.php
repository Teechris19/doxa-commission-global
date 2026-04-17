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

        <a href="{{ route('dashboard') }}" class="me-5 flex items-center gap-3 rtl:space-x-reverse" wire:navigate>
            <span class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-2xl bg-blue-50 ring-1 ring-blue-100 dark:bg-blue-900/30 dark:ring-blue-800">
                @if ($adminLogo)
                    <img src="{{ $adminLogo }}" alt="{{ $adminName }}" class="h-full w-full object-contain">
                @else
                    <x-app-logo-icon class="size-7 fill-current text-blue-700 dark:text-blue-400" />
                @endif
            </span>
            <div class="leading-tight">
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400 dark:text-gray-500">{{ $sidebarLabel }}</p>
                <p class="text-sm font-semibold text-slate-900 dark:text-gray-200">{{ $adminName }}</p>
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
                @if($isSuperAdmin || $isAdmin || $isAttendanceTeamLead)
                <flux:navlist.item icon="users" :href="route('admin.dashboard.members', ['chapter' => request()->get('chapter')])"
                    wire:navigate :active=" request()->routeIs('admin.dashboard.members') ? 'true' : 'false' ">
                    All Members
                </flux:navlist.item>
                @endif
                @role(['team-lead', 'lead-assist', 'lead_assist'])
                @if(!$isAttendanceTeamLead)
                <flux:navlist.item icon="users"
                    :href="route('admin.dashboard.members', ['chapter' => request()->get('chapter')])"
                    wire:navigate :active=" request()->routeIs('admin.dashboard.members') ? 'true' : 'false' ">
                    View Members
                </flux:navlist.item>
                @endif
                @endrole
                @if($isSuperAdmin || $isAdmin || $isAttendanceTeamLead)
                <flux:navlist.item icon="user-plus"
                    :href="route('admin.dashboard.members.create', ['chapter' => request()->get('chapter')])"
                    wire:navigate :active=" request()->routeIs('admin.dashboard.members.create') ? 'true' : 'false' ">
                    Create New Member
                </flux:navlist.item>
                @endif
                @if($isSuperAdmin || $isAdmin || $isAttendanceTeamLead)
                <flux:navlist.item icon="user-group" :href="route('admin.members.add-to-team', ['chapter' => request()->get('chapter')])"
                    wire:navigate :active=" request()->routeIs('admin.members.add-to-team') ? 'true' : 'false' ">
                    Add Member To Team
                </flux:navlist.item>
                @endif
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
            @if($isAttendanceTeamLead)
            <flux:navlist.group expandable heading="Teams"
                :expanded=" request()->routeIs('admin.dashboard.teams.*') ? 'true' : 'false' ">
                <flux:navlist.item icon="users" :href="route('admin.dashboard.teams', ['chapter' => request()->get('chapter')])" wire:navigate
                    :active="request()->routeIs('admin.dashboard.teams') ? 'true' : 'false' ">
                    All Teams
                </flux:navlist.item>
            </flux:navlist.group>
            @endif
            @role('super-admin')
            <flux:navlist.group expandable heading="Chapters"
                :expanded="request()->routeIs('super-admin.conclaves*') ? 'true' : 'false'">
                <flux:navlist.item icon="building-office" :href="route('super-admin.conclaves', request()->query())" wire:navigate
                    :active="request()->routeIs('super-admin.conclaves') ? 'true' : 'false'">
                    All Chapters
                </flux:navlist.item>
                <flux:navlist.item icon="plus-circle" :href="route('super-admin.conclaves.create', request()->query())" wire:navigate
                    :active="request()->routeIs('super-admin.conclaves.create') ? 'true' : 'false'">
                    Create Chapter
                </flux:navlist.item>
                <flux:navlist.item icon="user-plus" :href="route('super-admin.conclaves.add-admin', request()->query())" wire:navigate
                    :active="request()->routeIs('super-admin.conclaves.add-admin') ? 'true' : 'false'">
                    Assign Chapter Admin
                </flux:navlist.item>
            </flux:navlist.group>

            <flux:navlist.group expandable heading="Locations"
                :expanded="request()->routeIs('super-admin.locations*') ? 'true' : 'false'">
                <flux:navlist.item icon="map-pin" :href="route('super-admin.locations', request()->query())" wire:navigate
                    :active="request()->routeIs('super-admin.locations') ? 'true' : 'false'">
                    Chapter Locations
                </flux:navlist.item>
            </flux:navlist.group>
            
            {{-- Conclaves Management (Separate from Chapters) --}}
            <flux:navlist.group expandable heading="Conclaves"
                :expanded="request()->routeIs('admin.dashboard.conclaves*') ? 'true' : 'false'">
                <flux:navlist.item icon="map" :href="route('admin.dashboard.conclaves', request()->query())" wire:navigate
                    :active="request()->routeIs('admin.dashboard.conclaves') ? 'true' : 'false'">
                    All Conclaves
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
    <flux:header sticky class="border-b border-slate-200/80 bg-white/95 backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95">
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
    <flux:main class="min-h-screen bg-slate-50 dark:bg-zinc-950">
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

        // Global Modal Auto-Close After Form Submission
        (() => {
            // Function to close all open modals
            function closeAllModals() {
                // Close Alpine.js modals ($modalClose)
                if (window.$modalClose) {
                    document.querySelectorAll('[id$="-modal"], [id*="modal"]').forEach(modal => {
                        if (modal.id) {
                            try {
                                window.$modalClose(modal.id);
                            } catch (e) {}
                        }
                    });
                }

                // Close Flux modals by dispatching close event
                document.querySelectorAll('[x-data*="modal"], [x-show]').forEach(el => {
                    if (el._x_dataStack) {
                        el._x_dataStack.forEach(data => {
                            if (data.open !== undefined && typeof data.open === 'boolean') {
                                data.open = false;
                            }
                        });
                    }
                });

                // Close modals by removing open classes
                document.querySelectorAll('.modal.show, [x-show="true"], [x-show="open"]').forEach(modal => {
                    modal.classList.remove('show');
                    modal.style.display = 'none';
                    // Remove backdrop
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) backdrop.remove();
                    // Enable body scroll
                    document.body.classList.remove('overflow-hidden');
                });

                // Dispatch Livewire modal close events
                window.dispatchEvent(new CustomEvent('modal-closed'));
                window.dispatchEvent(new CustomEvent('close-all-modals'));
            }

            // Listen for Livewire events and close modals after successful actions
            document.addEventListener('livewire:init', () => {
                // Close modals after any component action completes
                Livewire.hook('commit', ({ component, succeed, fail }) => {
                    succeed(({ snapshot, stopPropagation }) => {
                        // Small delay to ensure action completed
                        setTimeout(() => {
                            closeAllModals();
                        }, 100);
                    });
                });
            });

            // Also listen for form submissions
            document.addEventListener('submit', (e) => {
                const form = e.target;
                // Check if form has wire:submit
                if (form.hasAttribute('wire:submit') || form.hasAttribute('wire:submit.prevent')) {
                    // Wait a bit for Livewire to process
                    setTimeout(() => {
                        closeAllModals();
                    }, 200);
                }
            });

            // Listen for Livewire responding (action completed)
            document.addEventListener('livewire:respond', () => {
                setTimeout(() => {
                    closeAllModals();
                }, 150);
            });
        })();
    </script>
</body>

</html>
