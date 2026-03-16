<?php

use App\Models\Team;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions, WithPagination;

    public ?int $quantity = 10;
    public ?string $search = null;
    public array $selected = [];
    public ?string $bulkAction = null;

    #[Url(keep:true)]
    public ?string $chapter;

    /**
     * Table headers
     */
    public function with(): array
    {
        return [
            'headers' => [
                ['index' => 'name', 'label' => 'Team Name'],
                ['index' => 'users_count', 'label' => 'Number of Members'],
                ['index' => 'action'],
            ],
            'rows' => $this->rows(),
        ];
    }

    /**
     * Query rows with filtering + pagination
     */
    public function rows()
    {
        return Team::withCount('users') // Count members
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when(
                $this->chapter,
                fn($q) =>
                $q->whereHas(
                    'chapter',
                    fn($qq) =>
                    $qq->where('name', $this->chapter)
                )
            )
            ->paginate($this->quantity);
    }

    /**
     * Get all row IDs for Select All
     */
    public function ids(): array
    {
        return $this->rows()->pluck('id')->toArray();
    }

    /**
     * Select all rows
     */
    public function selectAll()
    {
        $this->selected = $this->ids();
    }

    /**
     * Apply bulk action
     */
    public function applyBulkAction()
    {
        if (!$this->bulkAction || empty($this->selected))
            return;

        match ($this->bulkAction) {
            'delete' => Team::whereIn('id', $this->selected)->delete(),
            'export' => $this->exportSelected($this->selected), // optional
            default => null,
        };

        $this->toast()->success('Done!', 'Bulk action applied successfully!')->flash()->send();
        $this->selected   = [];
        $this->bulkAction = null;
    }

    public function delete($id)
    {
        $team = Team::where('id', '=', $id)->first();

        if (!$team) {
            abort(404);
        }

        $team->delete();

        $this->toast()
            ->success('Done!', 'Team deleted successfully!')
            ->send();
        $this->dispatch('$refresh');
    }

    /**
     * Delete single team
     */
    public function deleteTeam($id)
    {
        $this->dialog()
            ->error('Are You Sure you want to delete this team?')
            ->hook([
                'ok' => [
                    'method' => 'delete',
                    'params' => [$id]
                ],
            ])
            ->send();
    }
};
?>

<div>
    <x-fancy-header title="Teams" subtitle="Manage Teams" :breadcrumbs="[
        ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
        ['label' => 'Teams']
    ]">
        <x-button href="{{ route('admin.dashboard.teams.create', request()->query()) }}" wire:navigate>
            Add New
        </x-button>
    </x-fancy-header>

    <x-card class="relative dark:bg-dark-800">
        <x-table :$headers :$rows :filter="['quantity' => 'quantity', 'search' => 'search']" :quantity="[5, 15, 50, 100, 250]" paginate persistent selectable wire:model.live="selected">

            @interact('column_action', $row)
            {{-- Delete Team --}}
            <x-button.circle color="red" icon="trash" wire:click="deleteTeam('{{ $row->id }}')" />

            {{-- Edit Team --}}
            <x-link :href="route('admin.dashboard.teams.edit', ['team' => $row->id, 'chapter' => request()->query('chapter')])"
                class="inline-block px-3 py-1.5 text-sm font-medium text-white bg-amber-500 rounded shadow-md hover:bg-amber-600 active:shadow-inner transition duration-150 ease-in-out"
                wire:navigate>
                Edit Team
            </x-link>
            @endinteract
        </x-table>
    </x-card>
</div>