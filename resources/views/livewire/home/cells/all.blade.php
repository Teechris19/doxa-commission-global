<?php

use App\Models\{CellGroup, CellMember, Chapter};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    use Interactions, WithPagination;

    public $selectedCell = null;
    public $showJoinModal = false;

    // Chapter filter
    public $selectedChapterId = null;
    public $chapters = [];

    public function mount(): void
    {
        $this->loadChapters();
    }

    public function filterByChapter(): void
    {
        $this->selectedChapterId = $this->selectedChapterId === '' ? null : (int) $this->selectedChapterId;
        $this->resetPage();
    }

    protected function loadChapters(): void
    {
        $this->chapters = Chapter::orderBy('name')->get(['id', 'name']);
    }

    public function openJoinModal($cellId)
    {
        $this->selectedCell = CellGroup::with(['primaryLeader.user'])->findOrFail($cellId);
        $this->showJoinModal = true;
    }

    public function joinCell()
    {
        if (!Auth::check()) {
            $this->toast()->error('Please Login', 'You must be logged in to join a cell group')->send();
            return;
        }

        $user = Auth::user();

        $existing = CellMember::where('cell_group_id', $this->selectedCell->id)
            ->where('account_id', $user->id)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            $this->toast()->warning('Already a Member', 'You are already a member of this cell group')->send();
            return;
        }

        CellMember::create([
            'cell_group_id' => $this->selectedCell->id,
            'account_id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone ?? '',
            'email' => $user->email,
            'joined_at' => now(),
            'status' => 'active',
        ]);

        if ($this->selectedCell->whatsapp_link) {
            $this->showJoinModal = false;
            return redirect()->away($this->selectedCell->whatsapp_link);
        }

        $this->toast()->success('Welcome!', 'You have successfully joined this cell group')->send();
        $this->showJoinModal = false;
        $this->selectedCell = null;
    }

    public function with()
    {
        return [
            'allCells' => CellGroup::with(['primaryLeader.user'])
                ->withCount('activeMembers')
                ->where('is_active', true)
                ->where('name', '!=', 'Cell Settings')
                ->when($this->selectedChapterId, fn($q) => $q->where('chapter_id', $this->selectedChapterId))
                ->orderBy('name')
                ->paginate(6)
        ];
    }
}; ?>

<div>
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-8 text-slate-900">All Cell Groups</h2>

            {{-- Chapter Filter --}}
            <div class="max-w-md mx-auto mb-10">
                <div class="flex gap-2">
                    <select wire:model="selectedChapterId" class="flex-1 rounded-xl border border-slate-200 bg-white px-4 py-3 text-lg text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="">All Chapters</option>
                        @foreach($chapters as $chapter)
                            <option value="{{ $chapter->id }}">{{ $chapter->name }}</option>
                        @endforeach
                    </select>
                    <button wire:click="filterByChapter" class="rounded-xl bg-blue-600 px-6 py-3 text-lg font-semibold text-white hover:bg-blue-700 transition">
                        Filter
                    </button>
                </div>
                <div wire:loading class="mt-2 text-center text-sm text-blue-600 dark:text-blue-400">Filtering cells...</div>
            </div>

            {{-- Cell Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @forelse($allCells as $cell)
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition">
                        @if($cell->image)
                            <img src="{{ Storage::url($cell->image) }}" alt="{{ $cell->name }}" class="w-full h-48 object-cover">
                        @else
                            <div class="w-full h-48 bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                                <i class="bi bi-people-fill text-white text-6xl"></i>
                            </div>
                        @endif

                        <div class="p-5">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $cell->name }}</h3>

                            @if($cell->description)
                                <p class="text-gray-600 text-sm mb-3">{{ Str::limit($cell->description, 100) }}</p>
                            @endif

                            @if($cell->primaryLeader)
                                <div class="bg-blue-50 rounded-lg p-3 mb-3">
                                    <div class="flex items-center text-gray-700 mb-2">
                                        <i class="bi bi-person-badge text-blue-600 mr-2 text-lg"></i>
                                        <div>
                                            <p class="text-xs text-gray-500">Cell Leader</p>
                                            <p class="font-semibold text-sm">{{ $cell->primaryLeader->name }}</p>
                                        </div>
                                    </div>
                                    @php
                                        $phone = $cell->primaryLeader->phone ?: optional($cell->primaryLeader->user)->phone;
                                    @endphp
                                    @if($phone)
                                        <a href="tel:{{ $phone }}" class="flex items-center text-xs text-gray-600 hover:text-blue-600">
                                            <i class="bi bi-telephone mr-1"></i> {{ $phone }}
                                        </a>
                                    @endif
                                </div>
                            @endif

                            @if($cell->meeting_day && $cell->meeting_time)
                                <div class="flex items-center text-gray-700 mb-3">
                                    <i class="bi bi-calendar-event text-green-600 mr-2 text-lg"></i>
                                    <div>
                                        <p class="text-xs text-gray-500">Meetings</p>
                                        <p class="font-semibold text-sm">{{ $cell->meeting_day }}s at {{ \Carbon\Carbon::parse($cell->meeting_time)->format('g:i A') }}</p>
                                    </div>
                                </div>
                            @endif

                            @if($cell->location)
                                <div class="flex items-center text-gray-700 mb-4">
                                    <i class="bi bi-geo-alt text-red-600 mr-2 text-lg"></i>
                                    <div>
                                        <p class="text-xs text-gray-500">Location</p>
                                        <p class="font-semibold text-sm">{{ $cell->location }}</p>
                                    </div>
                                </div>
                            @endif

                            <div class="flex gap-2">
                                <button @click="$event.stopPropagation(); $wire.openJoinModal({{ $cell->id }})"
                                        class="flex-1 py-2.5 px-4 rounded-lg font-semibold bg-gradient-to-r from-blue-600 to-purple-600 text-white hover:from-blue-700 hover:to-purple-700 transition text-sm">
                                    Join Cell
                                </button>
                                @if($cell->latitude && $cell->longitude)
                                    <a href="https://www.google.com/maps/dir/?api=1&destination={{ $cell->latitude }},{{ $cell->longitude }}" target="_blank"
                                       class="flex-1 py-2.5 px-4 rounded-lg font-semibold bg-white border-2 border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-blue-300 hover:text-blue-700 transition text-center text-sm">
                                        Get Directions
                                    </a>
                                @else
                                    <span class="flex-1 py-2.5 px-4 rounded-lg font-semibold bg-slate-100 text-slate-400 text-center text-sm cursor-not-allowed" title="Location not set">
                                        Get Directions
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-16">
                        <i class="bi bi-people text-8xl text-gray-400 mb-4"></i>
                        <p class="text-xl text-gray-600">
                            @if($selectedChapterId)
                                No active cell groups found in this chapter.
                            @else
                                No active cell groups available at the moment.
                            @endif
                        </p>
                    </div>
                @endforelse
            </div>

            <div class="mt-8">
                {{ $allCells->links() }}
            </div>

            <div class="mt-12 text-center">
                <a href="{{ route('cells.index') }}" class="inline-flex items-center rounded-full border border-blue-200 bg-white px-8 py-3 text-lg font-semibold text-blue-700 transition hover:bg-blue-50 hover:border-blue-300">
                    <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Cells Page
                </a>
            </div>
        </div>
    </section>

    {{-- Join Modal --}}
    @if($showJoinModal && $selectedCell)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl max-w-md w-full p-8">
                <h3 class="text-2xl font-bold mb-2">Join {{ $selectedCell->name }}?</h3>

                @if($selectedCell->description)
                    <p class="text-gray-600 mb-4 text-sm">{{ Str::limit($selectedCell->description, 150) }}</p>
                @endif

                <p class="text-gray-600 mb-4">You're about to join this cell group. After confirming, you'll be directed to the cell's WhatsApp group to connect with members.</p>

                <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                    <li>Meets regularly for fellowship</li>
                    <li>Studies the Word together</li>
                    <li>Prays and supports one another</li>
                    <li>Builds lasting friendships</li>
                </ul>

                @if($selectedCell->primaryLeader)
                    <div class="bg-blue-50 rounded-lg p-4 mb-6">
                        <p class="text-sm text-gray-600 mb-1">Your Cell Leader</p>
                        <p class="font-semibold text-gray-900">{{ $selectedCell->primaryLeader->name }}</p>
                        @php
                            $leaderPhone = $selectedCell->primaryLeader->phone ?: optional($selectedCell->primaryLeader->user)->phone;
                        @endphp
                        @if($leaderPhone)
                            <p class="text-sm text-gray-600"><i class="bi bi-telephone"></i> {{ $leaderPhone }}</p>
                        @endif
                    </div>
                @endif

                <div class="flex gap-3">
                    <button wire:click="$set('showJoinModal', false)"
                            class="flex-1 py-3 px-6 bg-gray-200 text-gray-800 rounded-lg font-semibold hover:bg-gray-300 transition">
                        Cancel
                    </button>
                    <button wire:click="joinCell"
                            class="flex-1 py-3 px-6 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-purple-700 transition">
                        Confirm & Join on WhatsApp
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
