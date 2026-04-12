<?php

use App\Models\{CellGroup, CellMember, CellPageSetting};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    use Interactions;

    public $selectedCell = null;
    public $showJoinModal = false;

    public $pageSettings = null;
    public $displayCells = [];
    public $hasMoreCells = false;

    public function mount(): void
    {
        $this->loadPageSettings();
        $this->loadDisplayCells();
    }

    protected function loadPageSettings(): void
    {
        $this->pageSettings = CellPageSetting::whereNull('chapter_id')->first();
    }

    protected function loadDisplayCells(): void
    {
        $limit = $this->pageSettings?->cells_to_display ?? 3;

        $allCells = CellGroup::with(['primaryLeader.user'])
            ->withCount('activeMembers')
            ->where('is_active', true)
            ->where('name', '!=', 'Cell Settings')
            ->orderBy('name')
            ->get();

        $this->hasMoreCells = $allCells->count() > $limit;
        $this->displayCells = $allCells->take($limit)->all();
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
            'faqs' => $this->pageSettings?->faqs ?? [],
        ];
    }
}; ?>

<div>
    {{-- Hero Section --}}
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden">
        @if($pageSettings?->hero_image)
            <div class="absolute inset-0">
                <img src="{{ Storage::url($pageSettings->hero_image) }}" alt="Hero" class="w-full h-full object-cover" />
                <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/50 to-black/70"></div>
            </div>
        @else
            <div class="absolute inset-0 bg-gradient-to-br from-blue-600 via-blue-700 to-purple-700"></div>
        @endif

        <div class="relative z-10 container mx-auto px-4 text-center text-white">
            <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold mb-6">{{ $pageSettings?->hero_title ?? 'Join a Cell Group' }}</h1>
            <p class="text-xl md:text-2xl mb-4 max-w-3xl mx-auto">{{ $pageSettings?->hero_subtitle ?? 'Connect, grow, and fellowship in small groups' }}</p>
            @if($pageSettings?->hero_description)
                <p class="text-lg mb-10 max-w-2xl mx-auto text-white/90">{{ $pageSettings->hero_description }}</p>
            @endif
            <a href="#cells-section" class="inline-flex items-center rounded-full bg-white px-8 py-4 text-lg font-semibold text-blue-700 transition hover:bg-blue-50">
                {{ $pageSettings?->hero_button_text ?? 'Join a Cell' }}
                <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
            </a>
        </div>
    </section>

    {{-- Left/Right Text Section --}}
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                <div>
                    <h2 class="text-3xl md:text-4xl font-bold text-slate-900">{{ $pageSettings?->left_heading ?? 'HOME CLOSE TO YOU' }}</h2>
                </div>
                <div>
                    <p class="text-lg leading-relaxed text-slate-600">{{ $pageSettings?->right_description ?? 'Think about Doxa Cell as a small gathering of disciples who meet regularly to study the Word, pray for one another, and build lasting relationships in a close-knit community.' }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Center Image --}}
    @if($pageSettings?->center_image)
    <section class="py-12 bg-white">
        <div class="container mx-auto px-4 flex justify-center">
            <img src="{{ Storage::url($pageSettings->center_image) }}" alt="Cell Community" class="w-[80%] h-[60vh] object-cover rounded-2xl shadow-xl" />
        </div>
    </section>
    @endif

    {{-- Cells Section --}}
    <section id="cells-section" class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12 text-slate-900">Find Your Cell Group</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @forelse($displayCells as $cell)
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition">
                        @if($cell->image)
                            <img src="{{ Storage::url($cell->image) }}" alt="{{ $cell->name }}" class="w-full h-56 object-cover">
                        @else
                            <div class="w-full h-56 bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                                <i class="bi bi-people-fill text-white text-7xl"></i>
                            </div>
                        @endif

                        <div class="p-6">
                            <h3 class="text-2xl font-bold text-gray-900 mb-3">{{ $cell->name }}</h3>

                            @if($cell->description)
                                <p class="text-gray-600 mb-4">{{ Str::limit($cell->description, 120) }}</p>
                            @endif

                            @if($cell->primaryLeader)
                                <div class="bg-blue-50 rounded-lg p-4 mb-4">
                                    <div class="flex items-center text-gray-700 mb-2">
                                        <i class="bi bi-person-badge text-blue-600 mr-3 text-xl"></i>
                                        <div>
                                            <p class="text-sm text-gray-500">Cell Leader</p>
                                            <p class="font-semibold">{{ $cell->primaryLeader->name }}</p>
                                        </div>
                                    </div>
                                    @php
                                        $phone = $cell->primaryLeader->phone ?: optional($cell->primaryLeader->user)->phone;
                                    @endphp
                                    @if($phone)
                                        <a href="tel:{{ $phone }}" class="flex items-center text-sm text-gray-600 hover:text-blue-600">
                                            <i class="bi bi-telephone mr-1.5"></i> {{ $phone }}
                                        </a>
                                    @endif
                                </div>
                            @endif

                            @if($cell->meeting_day && $cell->meeting_time)
                                <div class="flex items-center text-gray-700 mb-3">
                                    <i class="bi bi-calendar-event text-green-600 mr-3 text-xl"></i>
                                    <div>
                                        <p class="text-sm text-gray-500">Meetings</p>
                                        <p class="font-semibold">{{ $cell->meeting_day }}s at {{ \Carbon\Carbon::parse($cell->meeting_time)->format('g:i A') }}</p>
                                    </div>
                                </div>
                            @endif

                            @if($cell->location)
                                <div class="flex items-center text-gray-700 mb-6">
                                    <i class="bi bi-geo-alt text-red-600 mr-3 text-xl"></i>
                                    <div>
                                        <p class="text-sm text-gray-500">Location</p>
                                        <p class="font-semibold">{{ $cell->location }}</p>
                                    </div>
                                </div>
                            @endif

                            <div class="flex gap-2">
                                <button wire:click="openJoinModal({{ $cell->id }})"
                                        class="flex-1 py-3 px-6 rounded-lg font-semibold bg-gradient-to-r from-blue-600 to-purple-600 text-white hover:from-blue-700 hover:to-purple-700 transition text-sm">
                                    Join Cell
                                </button>
                                @if($cell->latitude && $cell->longitude)
                                    <a href="https://www.google.com/maps/dir/?api=1&destination={{ $cell->latitude }},{{ $cell->longitude }}" target="_blank"
                                       class="flex-1 py-3 px-6 rounded-lg font-semibold bg-white border-2 border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-blue-300 hover:text-blue-700 transition text-center text-sm">
                                        Get Directions
                                    </a>
                                @else
                                    <span class="flex-1 py-3 px-6 rounded-lg font-semibold bg-slate-100 text-slate-400 text-center text-sm cursor-not-allowed" title="Location not set">
                                        Get Directions
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-16">
                        <i class="bi bi-people text-8xl text-gray-400 mb-4"></i>
                        <p class="text-xl text-gray-600">No active cell groups available at the moment</p>
                    </div>
                @endforelse
            </div>

            @if($hasMoreCells)
                <div class="mt-12 text-center">
                    <a href="{{ route('cells.all') }}" class="inline-flex items-center rounded-full border border-blue-200 bg-white px-8 py-3 text-lg font-semibold text-blue-700 transition hover:bg-blue-50 hover:border-blue-300">
                        View All Cells
                        <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                </div>
            @else
                @if(count($displayCells) > 0)
                    <div class="mt-12 text-center">
                        <a href="{{ route('cells.all') }}" class="inline-flex items-center rounded-full border border-blue-200 bg-white px-8 py-3 text-lg font-semibold text-blue-700 transition hover:bg-blue-50 hover:border-blue-300">
                            View All Cells
                            <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                    </div>
                @endif
            @endif
        </div>
    </section>

    {{-- FAQs Section --}}
    @if(!empty($faqs))
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4 max-w-4xl">
            <h2 class="text-3xl font-bold text-center mb-12 text-slate-900">Frequently Asked Questions</h2>

            <div class="space-y-4">
                @foreach($faqs as $index => $faq)
                    <div x-data="{ open: false }" class="rounded-xl border border-slate-200 overflow-hidden">
                        <button @click="open = !open" class="w-full flex items-center justify-between px-6 py-4 text-left bg-white hover:bg-slate-50 transition">
                            <span class="text-lg font-semibold text-slate-900">{{ $faq['question'] ?? '' }}</span>
                            <svg class="h-5 w-5 text-slate-500 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-collapse class="px-6 pb-4 pt-2 bg-slate-50">
                            <p class="text-slate-600 leading-relaxed">{{ $faq['answer'] ?? '' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

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
