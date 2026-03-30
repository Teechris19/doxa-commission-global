<?php

use App\Models\Conclave;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    public $conclaves;
    public $selectedConclave = null;

    public function mount()
    {
        $this->conclaves = Conclave::where('is_active', true)
            ->orderBy('location')
            ->get();
    }

    public function selectConclave($id)
    {
        $this->selectedConclave = Conclave::find($id);
    }
}; ?>

<div class="bg-white pb-10">
    {{-- Hero Section --}}
    <section class="border-b border-blue-100 bg-gradient-to-b from-blue-50 to-white">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-600">Our Locations</p>
            <h1 class="mt-3 text-3xl font-semibold text-slate-900 sm:text-4xl">Doxa Conclaves</h1>
            <p class="mt-4 max-w-3xl text-base text-slate-600">
                Connect with fellow Doxites across different locations. Find a conclave near you and join our growing community.
            </p>
        </div>
    </section>

    {{-- Conclaves Grid --}}
    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
        @if($conclaves->count() > 0)
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($conclaves as $conclave)
                    <article class="overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm transition hover:shadow-lg">
                        @if($conclave->image)
                            <img src="{{ Storage::url($conclave->image) }}" alt="{{ $conclave->name }}" class="h-48 w-full object-cover">
                        @else
                            <div class="flex h-48 items-center justify-center bg-gradient-to-br from-blue-100 to-blue-200">
                                <svg class="h-16 w-16 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                        @endif
                        <div class="p-5">
                            <h3 class="text-lg font-semibold text-slate-900">{{ $conclave->name }}</h3>
                            <p class="mt-1 text-sm text-slate-600">
                                <svg class="mr-1 inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ $conclave->location }}
                            </p>
                            @if($conclave->description)
                                <p class="mt-3 text-sm leading-7 text-slate-600">{{ Str::limit($conclave->description, 100) }}</p>
                            @endif
                            <button
                                type="button"
                                wire:click="selectConclave({{ $conclave->id }})"
                                class="mt-4 inline-flex w-full items-center justify-center rounded-full bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-white transition hover:bg-blue-700"
                            >
                                View Details
                            </button>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-24 w-24 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-700">No Conclaves Yet</h3>
                <p class="mt-2 text-sm text-gray-500">Check back soon for updates.</p>
            </div>
        @endif
    </section>

    {{-- Conclave Details Modal --}}
    @if($selectedConclave)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" wire:click="$set('selectedConclave', null)">
            <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-3xl border border-blue-100 bg-white p-6 shadow-2xl" wire:click.stop>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-2xl font-semibold text-slate-900">{{ $selectedConclave->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $selectedConclave->location }}</p>
                    </div>
                    <button type="button" wire:click="$set('selectedConclave', null)" class="rounded-full border border-blue-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 transition hover:text-slate-700">Close</button>
                </div>

                @if($selectedConclave->image)
                    <img src="{{ Storage::url($selectedConclave->image) }}" alt="{{ $selectedConclave->name }}" class="mt-5 h-64 w-full rounded-2xl object-cover">
                @endif

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    @if($selectedConclave->description)
                        <div class="sm:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">About</p>
                            <p class="mt-2 text-sm leading-7 text-slate-600">{{ $selectedConclave->description }}</p>
                        </div>
                    @endif

                    @if($selectedConclave->address)
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Address</p>
                            <p class="mt-2 text-sm text-slate-600">{{ $selectedConclave->address }}</p>
                        </div>
                    @endif

                    @if($selectedConclave->phone)
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Phone</p>
                            <a href="tel:{{ $selectedConclave->phone }}" class="mt-2 inline-block text-sm text-slate-600 hover:text-blue-700">{{ $selectedConclave->phone }}</a>
                        </div>
                    @endif

                    @if($selectedConclave->email)
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Email</p>
                            <a href="mailto:{{ $selectedConclave->email }}" class="mt-2 inline-block text-sm text-slate-600 hover:text-blue-700">{{ $selectedConclave->email }}</a>
                        </div>
                    @endif

                    @if($selectedConclave->whatsapp_link)
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">WhatsApp</p>
                            <a href="{{ $selectedConclave->whatsapp_link }}" target="_blank" class="mt-2 inline-flex items-center gap-1 text-sm text-green-600 hover:text-green-700">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                Join WhatsApp Group
                            </a>
                        </div>
                    @endif
                </div>

                @if($selectedConclave->latitude && $selectedConclave->longitude)
                    <div class="mt-6 overflow-hidden rounded-2xl border border-blue-100">
                        <iframe
                            width="100%"
                            height="280"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            src="https://maps.google.com/maps?q={{ $selectedConclave->latitude }},{{ $selectedConclave->longitude }}&output=embed"
                        ></iframe>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
