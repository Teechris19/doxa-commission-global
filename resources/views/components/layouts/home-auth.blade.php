<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $title ?? config('app.name') }}</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        @livewireStyles
        @vite(['resources/js/app.js'])
    </head>
    <body class="bg-[#f6f1e7] text-slate-900 antialiased">
        <div class="min-h-screen">
            <header class="relative z-10">
                <div class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6">
                    <a href="{{ route('home') }}" class="flex items-center gap-3" wire:navigate>
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 shadow-inner">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 3v18M6 9h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Doxa Commission Global</p>
                            <p class="text-xs text-slate-500">Faith. Family. Mission.</p>
                        </div>
                    </a>
                    <div class="hidden items-center gap-3 text-sm text-slate-600 sm:flex">
                        <a href="{{ route('sermons.index') }}" class="transition hover:text-slate-900" wire:navigate>Messages</a>
                        <span class="text-slate-300">|</span>
                        <a href="{{ route('events.index') }}" class="transition hover:text-slate-900" wire:navigate>Events</a>
                        <span class="text-slate-300">|</span>
                        <a href="{{ route('believers.academy') }}" class="transition hover:text-slate-900" wire:navigate>Believers Academy</a>
                    </div>
                </div>
            </header>

            <main class="relative z-10">
                {{ $slot }}
            </main>

            <footer class="relative z-10">
                <div class="mx-auto w-full max-w-6xl px-6 pb-10 pt-6 text-xs text-slate-500">
                    <div class="flex flex-col items-center justify-between gap-2 sm:flex-row">
                        <p>&copy; {{ now()->year }} Doxa Commission Global. All rights reserved.</p>
                        <p>Need help? Contact your chapter admin.</p>
                    </div>
                </div>
            </footer>
        </div>

        @livewireScripts
        @fluxScripts
    </body>
</html>
