<?php

use App\Models\{CellGroup, Chapter};
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public $chapter;

    public function getCells()
    {
        $user = Auth::user();
        $chapterId = $this->chapter ? Chapter::where('name', $this->chapter)->first()?->id : $user->chapter_id;

        return CellGroup::with(['chapter', 'primaryLeader', 'members'])
            ->withCount(['members', 'activeMembers'])
            ->when($chapterId, fn($q) => $q->where('chapter_id', $chapterId))
            ->latest()
            ->paginate(12);
    }

    public function toggleActive($id)
    {
        $cell = CellGroup::findOrFail($id);
        $cell->is_active = !$cell->is_active;
        $cell->save();

        $status = $cell->is_active ? 'activated' : 'deactivated';
        $this->toast()->success('Success', "Cell group {$status}")->send();
    }

    public function deleteCell($id)
    {
        CellGroup::findOrFail($id)->delete();
        $this->toast()->success('Deleted', 'Cell group deleted successfully')->send();
    }

    public function with()
    {
        return ['cells' => $this->getCells()];
    }
}; ?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">Cell Groups</h1>
            <p class="text-zinc-600 dark:text-zinc-400 mt-1">Manage cell groups and members</p>
        </div>
        <a href="{{ route('admin.dashboard.cells.create') }}" wire:navigate>
            <x-button color="primary" icon="plus" label="Create Cell Group" />
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($cells as $cell)
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg overflow-hidden transition hover:shadow-xl">
                @if($cell->image)
                    <img src="{{ Storage::url($cell->image) }}" alt="{{ $cell->name }}" class="w-full h-48 object-cover">
                @else
                    <div class="w-full h-48 bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                        <i class="bi bi-people-fill text-white text-6xl"></i>
                    </div>
                @endif

                <div class="p-6">
                    <div class="flex justify-between items-start mb-3">
                        <h3 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ $cell->name }}</h3>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $cell->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $cell->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    @if($cell->description)
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4 line-clamp-2">{{ $cell->description }}</p>
                    @endif

                    <div class="space-y-2 mb-4">
                        @if($cell->primaryLeader)
                            <div class="flex items-center text-sm text-zinc-700 dark:text-zinc-300">
                                <i class="bi bi-person-badge mr-2"></i>
                                <span>{{ $cell->primaryLeader->name }}</span>
                            </div>
                        @endif

                        @if($cell->meeting_day && $cell->meeting_time)
                            <div class="flex items-center text-sm text-zinc-700 dark:text-zinc-300">
                                <i class="bi bi-calendar-event mr-2"></i>
                                <span>{{ $cell->meeting_day }}s at {{ \Carbon\Carbon::parse($cell->meeting_time)->format('g:i A') }}</span>
                            </div>
                        @endif

                        @if($cell->location)
                            <div class="flex items-center text-sm text-zinc-700 dark:text-zinc-300">
                                <i class="bi bi-geo-alt mr-2"></i>
                                <span class="truncate">{{ $cell->location }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="flex gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <a href="{{ route('admin.dashboard.cells.view', ['id' => $cell->id]) }}" wire:navigate class="flex-1">
                            <x-button color="primary" icon="eye" label="View" class="w-full" />
                        </a>
                        <a href="{{ route('admin.dashboard.cells.edit', ['id' => $cell->id]) }}" wire:navigate class="flex-1">
                            <x-button color="yellow" icon="pencil" label="Edit" class="w-full" />
                        </a>
                        <x-button.circle color="{{ $cell->is_active ? 'yellow' : 'green' }}"
                            icon="{{ $cell->is_active ? 'pause' : 'play' }}"
                            wire:click="toggleActive({{ $cell->id }})" />
                        <x-button.circle color="red" icon="trash" wire:click="deleteCell({{ $cell->id }})" />
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-12">
                <i class="bi bi-people text-6xl text-zinc-400"></i>
                <p class="text-zinc-500 mt-4">No cell groups found</p>
                <a href="{{ route('admin.dashboard.cells.create') }}" wire:navigate class="mt-4 inline-block">
                    <x-button color="primary" label="Create First Cell Group" />
                </a>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $cells->links() }}
    </div>
</div>
