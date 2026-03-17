<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.admin')] class extends Component {
    public string $theme = 'system';

    public function mount()
    {
        $this->theme = session('theme', 'system');
    }

    public function setTheme(string $theme)
    {
        $this->theme = $theme;
        session(['theme' => $theme]);
        
        // Dispatch event to update theme across all components
        $this->dispatch('theme-updated', theme: $theme);
    }

    public function toggleTheme()
    {
        $themes = ['light', 'dark', 'system'];
        $currentIndex = array_search($this->theme, $themes);
        $nextTheme = $themes[($currentIndex + 1) % count($themes)];
        $this->setTheme($nextTheme);
    }
}; ?>

<div class="space-y-6">
    <div>
        <h2 class="text-lg font-medium text-slate-900 dark:text-gray-100">Appearance Settings</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">Customize how the application looks on your device.</p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <h3 class="text-base font-medium text-slate-900 dark:text-gray-100">Theme Preference</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">Choose your preferred theme for the application.</p>

        <div class="mt-6 grid grid-cols-3 gap-4">
            <!-- Light Theme -->
            <button
                wire:click="setTheme('light')"
                class="group relative flex flex-col items-center gap-3 rounded-lg border-2 p-4 transition-all {{ $this->theme === 'light' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-slate-200 hover:border-slate-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}"
            >
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <span class="text-sm font-medium text-slate-700 dark:text-gray-300">Light</span>
                @if($this->theme === 'light')
                    <div class="absolute right-2 top-2 flex h-5 w-5 items-center justify-center rounded-full bg-blue-500 text-white">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                @endif
            </button>

            <!-- Dark Theme -->
            <button
                wire:click="setTheme('dark')"
                class="group relative flex flex-col items-center gap-3 rounded-lg border-2 p-4 transition-all {{ $this->theme === 'dark' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-slate-200 hover:border-slate-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}"
            >
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </div>
                <span class="text-sm font-medium text-slate-700 dark:text-gray-300">Dark</span>
                @if($this->theme === 'dark')
                    <div class="absolute right-2 top-2 flex h-5 w-5 items-center justify-center rounded-full bg-blue-500 text-white">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                @endif
            </button>

            <!-- System Theme -->
            <button
                wire:click="setTheme('system')"
                class="group relative flex flex-col items-center gap-3 rounded-lg border-2 p-4 transition-all {{ $this->theme === 'system' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-slate-200 hover:border-slate-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}"
            >
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <span class="text-sm font-medium text-slate-700 dark:text-gray-300">System</span>
                @if($this->theme === 'system')
                    <div class="absolute right-2 top-2 flex h-5 w-5 items-center justify-center rounded-full bg-blue-500 text-white">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                @endif
            </button>
        </div>

        <div class="mt-6 rounded-lg bg-slate-50 p-4 dark:bg-zinc-800">
            <div class="flex items-start gap-3">
                <svg class="h-5 w-5 text-blue-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm text-slate-600 dark:text-gray-400">
                    <p class="font-medium text-slate-900 dark:text-gray-200">Current selection: <span class="capitalize">{{ $this->theme }}</span></p>
                    @if($this->theme === 'system')
                        <p class="mt-1">The app will automatically match your device's theme settings.</p>
                    @elseif($this->theme === 'dark')
                        <p class="mt-1">Dark mode is enabled. The app will use dark colors.</p>
                    @else
                        <p class="mt-1">Light mode is enabled. The app will use light colors.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('settings.profile') }}" wire:navigate class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-zinc-700 dark:text-gray-300 dark:hover:bg-zinc-800">
            Back to Profile
        </a>
    </div>
</div>

@script
<script>
    // Apply theme on load
    const applyTheme = (theme) => {
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
    };

    // Apply initial theme
    applyTheme('{{ $this->theme }}');

    // Listen for theme changes
    $wire.on('theme-updated', (event) => {
        applyTheme(event.theme);
    });

    // Listen for system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        if ('{{ $this->theme }}' === 'system') {
            if (e.matches) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    });
</script>
@endscript
