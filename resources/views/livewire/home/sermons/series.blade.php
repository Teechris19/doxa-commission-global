<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\SermonSeries;
use Livewire\WithPagination;
use Illuminate\Support\Str;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    use WithPagination;

    public $search = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function series()
    {
        return SermonSeries::query()
            ->when($this->search, function ($query) {
                $query->where('title', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(12);
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }
}; ?>

<div class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <section class="rounded-3xl border border-blue-100 bg-white p-6 shadow-[0_24px_60px_-40px_rgba(37,99,235,0.45)] sm:p-8">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-600">Messages</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-900">Sermon Series</h1>
                <p class="mt-2 text-sm text-slate-600">Explore curated collections of teaching.</p>
            </div>
            <div class="w-full md:w-72">
                <input
                    type="text"
                    wire:model.live="search"
                    placeholder="Search series..."
                    class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                >
            </div>
        </div>

        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @forelse($this->series as $serie)
                <article class="overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm">
                    @if($serie->image)
                        <img src="{{ asset('storage/' . $serie->image) }}" alt="{{ $serie->title }}" class="h-52 w-full object-cover">
                    @else
                        <div class="flex h-52 items-center justify-center bg-blue-50 text-4xl text-blue-300">
                            <i class="fas fa-photo-film"></i>
                        </div>
                    @endif

                    <div class="space-y-3 p-5">
                        <h2 class="text-lg font-semibold text-slate-900">{{ $serie->title }}</h2>
                        <p class="text-sm text-slate-600">{{ Str::limit($serie->description, 100) }}</p>

                        <div class="flex items-center justify-between">
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{{ $serie->sermons->count() }} Sermons</span>
                            <a href="{{ route('sermons.series-detail', ['id' => $serie->id]) }}" wire:navigate class="rounded-lg border border-blue-200 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-50">
                                View Series
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-blue-200 p-10 text-center text-sm text-slate-500 md:col-span-2 lg:col-span-3">
                    No series found. Try adjusting your search.
                </div>
            @endforelse
        </div>

        <div class="mt-8 flex justify-center">
            {{ $this->series->links() }}
        </div>
    </section>
</div>
