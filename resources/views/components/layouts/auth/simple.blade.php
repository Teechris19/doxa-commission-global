<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-50 antialiased">
        <div class="relative min-h-svh overflow-hidden">
            <div class="absolute inset-0">
                <div class="absolute -left-40 top-[-10rem] h-[28rem] w-[28rem] rounded-full bg-gradient-to-br from-sky-400/10 via-blue-300/5 to-transparent blur-3xl"></div>
                <div class="absolute right-[-12rem] top-[6rem] h-[26rem] w-[26rem] rounded-full bg-gradient-to-tr from-blue-300/10 via-cyan-200/5 to-transparent blur-3xl"></div>
                <div class="absolute left-1/2 top-1/3 h-[22rem] w-[22rem] -translate-x-1/2 rounded-full bg-gradient-to-b from-sky-200/10 to-transparent blur-[100px]"></div>
            </div>

            <div class="relative flex min-h-svh items-center justify-center px-6 py-12 md:px-10">
                <div class="w-full max-w-lg">
                    <div class="mb-10 flex items-center justify-center">
                        <a href="{{ route('home') }}" class="group flex flex-col items-center gap-4" wire:navigate>
                            <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition group-hover:ring-slate-300">
                                <x-app-logo-icon class="size-10 fill-current text-sky-600" />
                            </span>
                            <div class="text-center">
                                <p class="text-xs font-bold uppercase tracking-[0.4em] text-slate-500">{{ config('app.name', 'Laravel') }}</p>
                            </div>
                        </a>
                    </div>

                    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/50">
                        <div class="p-8 md:p-10">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
