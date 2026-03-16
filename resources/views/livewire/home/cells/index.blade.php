<?php

use App\Models\{CellGroup, CellMember};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    use Interactions;

    public $selectedCell = null;
    public $showJoinModal = false;

    public function getCells()
    {
        $user = Auth::user();
        $chapterId = $user?->chapter_id;

        return CellGroup::with(['primaryLeader', 'members'])
            ->withCount('activeMembers')
            ->where('is_active', true)
            ->when($chapterId, fn($q) => $q->where('chapter_id', $chapterId))
            ->orderBy('name')
            ->get();
    }

    public function openJoinModal($cellId)
    {
        $this->selectedCell = CellGroup::with('primaryLeader')->findOrFail($cellId);
        $this->showJoinModal = true;
    }

    public function joinCell()
    {
        if (!Auth::check()) {
            $this->toast()->error('Please Login', 'You must be logged in to join a cell group')->send();
            return;
        }

        $user = Auth::user();

        // Check if already a member
        $existing = CellMember::where('cell_group_id', $this->selectedCell->id)
            ->where('account_id', $user->id)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            $this->toast()->warning('Already a Member', 'You are already a member of this cell group')->send();
            return;
        }

        // Check if cell is full
        if ($this->selectedCell->isFull()) {
            $this->toast()->error('Cell Full', 'This cell group is currently full')->send();
            return;
        }

        // Join the cell
        CellMember::create([
            'cell_group_id' => $this->selectedCell->id,
            'account_id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone ?? '',
            'email' => $user->email,
            'joined_at' => now(),
            'status' => 'active',
        ]);

        $this->toast()->success('Welcome!', 'You have successfully joined this cell group')->send();
        $this->showJoinModal = false;
        $this->selectedCell = null;
    }

    public function with()
    {
        return ['cells' => $this->getCells()];
    }
}; ?>

<div>
    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-20">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-5xl font-bold mb-4">Join a Cell Group</h1>
            <p class="text-xl mb-8">Connect, grow, and fellowship in small groups</p>
            <div class="max-w-2xl mx-auto">
                <p class="text-lg">Cell groups are small gatherings where believers connect on a personal level, study the Word together, pray for one another, and build meaningful relationships.</p>
            </div>
        </div>
    </section>

    <!-- Cell Groups Grid -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">Find Your Cell Group</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @forelse($cells as $cell)
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

                            <div class="space-y-3 mb-6">
                                @if($cell->primaryLeader)
                                    <div class="flex items-center text-gray-700">
                                        <i class="bi bi-person-badge text-blue-600 mr-3 text-xl"></i>
                                        <div>
                                            <p class="text-sm text-gray-500">Cell Leader</p>
                                            <p class="font-semibold">{{ $cell->primaryLeader->name }}</p>
                                        </div>
                                    </div>
                                @endif

                                @if($cell->meeting_day && $cell->meeting_time)
                                    <div class="flex items-center text-gray-700">
                                        <i class="bi bi-calendar-event text-green-600 mr-3 text-xl"></i>
                                        <div>
                                            <p class="text-sm text-gray-500">Meetings</p>
                                            <p class="font-semibold">{{ $cell->meeting_day }}s at {{ \Carbon\Carbon::parse($cell->meeting_time)->format('g:i A') }}</p>
                                        </div>
                                    </div>
                                @endif

                                @if($cell->location)
                                    <div class="flex items-center text-gray-700">
                                        <i class="bi bi-geo-alt text-red-600 mr-3 text-xl"></i>
                                        <div>
                                            <p class="text-sm text-gray-500">Location</p>
                                            <p class="font-semibold">{{ $cell->location }}</p>
                                        </div>
                                    </div>
                                @endif

                                <div class="flex items-center text-gray-700">
                                    <i class="bi bi-people text-purple-600 mr-3 text-xl"></i>
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-500">Members</p>
                                        <p class="font-semibold">{{ $cell->active_members_count }} / {{ $cell->max_members }}
                                            @if($cell->isFull())
                                                <span class="text-xs text-red-600">(Full)</span>
                                            @else
                                                <span class="text-xs text-green-600">({{ $cell->availableSpots() }} spots left)</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-gradient-to-r from-blue-600 to-purple-600 h-2.5 rounded-full"
                                         style="width: {{ ($cell->active_members_count / $cell->max_members) * 100 }}%"></div>
                                </div>
                            </div>

                            <button wire:click="openJoinModal({{ $cell->id }})"
                                    class="w-full py-3 px-6 rounded-lg font-semibold transition
                                           {{ $cell->isFull() ? 'bg-gray-300 text-gray-600 cursor-not-allowed' : 'bg-gradient-to-r from-blue-600 to-purple-600 text-white hover:from-blue-700 hover:to-purple-700' }}"
                                    @if($cell->isFull()) disabled @endif>
                                @if($cell->isFull())
                                    Cell Group Full
                                @else
                                    Join This Cell
                                @endif
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-16">
                        <i class="bi bi-people text-8xl text-gray-400 mb-4"></i>
                        <p class="text-xl text-gray-600">No active cell groups available at the moment</p>
                        <p class="text-gray-500 mt-2">Please check back later or contact us for more information</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Join Modal -->
    @if($showJoinModal && $selectedCell)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl max-w-md w-full p-8">
                <h3 class="text-2xl font-bold mb-4">Join {{ $selectedCell->name }}?</h3>

                <div class="mb-6">
                    <p class="text-gray-600 mb-4">You're about to join this cell group. You'll be part of a community that:</p>
                    <ul class="list-disc list-inside space-y-2 text-gray-700">
                        <li>Meets regularly for fellowship</li>
                        <li>Studies the Word together</li>
                        <li>Prays and supports one another</li>
                        <li>Builds lasting friendships</li>
                    </ul>
                </div>

                @if($selectedCell->primaryLeader)
                    <div class="bg-blue-50 rounded-lg p-4 mb-6">
                        <p class="text-sm text-gray-600 mb-1">Your Cell Leader</p>
                        <p class="font-semibold text-gray-900">{{ $selectedCell->primaryLeader->name }}</p>
                        @if($selectedCell->primaryLeader->phone)
                            <p class="text-sm text-gray-600">{{ $selectedCell->primaryLeader->phone }}</p>
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
                        Confirm & Join
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
