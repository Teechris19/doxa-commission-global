<?php
/**
 TODO: Fix the bug on the members page not showing for admin in the index and create page
 */
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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
    public $selectedTeams = [];
    public $teams = [];
    public $password;
    public $selected_id;
    public $leaderTeam;

    // For editing user
    public $edit_name;
    public $edit_email;
    public $edit_status;

    // This binds ?chapter=name into Livewire property
    #[Url]
    public ?string $chapter = null;


    public function mount()
    {
        // Teams list for modal
        $this->teams = \App\Models\Team::all()->map(fn($team) => [
            'value' => $team->id,
            'label' => $team->name,
        ])->toArray();
    }

    /**
     * Table headers
     */
    public function with(): array
    {
        return [
            'headers' => [
                ['index' => 'name', 'label' => 'Name'],
                ['index' => 'email', 'label' => 'Email'],
                ['index' => 'action', 'label'=>'Action'],
            ],
            'rows' => $this->rows(),
        ];
    }

    /**
     * Query rows with filtering + pagination
     */
    public function rows()
    {
        $user  = Auth::user();
        $query = User::query()
            ->with('chapter')
            ->when(
                $this->search,
                fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
            )
            ->when(
                $this->chapter,
                fn($q) =>
                $q->whereHas(
                    'chapter',
                    fn($qq) =>
                    $qq->where('name', $this->chapter)
                )
            );
        if ($user->hasRole(['team-lead', 'lead-assist', 'lead_assist'])) {
            $teamIds = $user->teams
                ->filter(fn($team) => in_array($team->pivot->role_in_team, ['team-lead', 'lead-assist', 'lead_assist']))
                ->pluck('id');
            $this->leaderTeam = $user->teams
                ->firstWhere(fn($team) => in_array($team->pivot->role_in_team, ['team-lead', 'lead-assist', 'lead_assist']))
                ?->name ?? '';
            return
                $query->whereHas('teams', fn($q) => $q->whereIn('teams.id', $teamIds))->paginate($this->quantity);
        }
        return $query->paginate($this->quantity);
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
        if (!$this->bulkAction || empty($this->selected)) {
            return;
        }

        match ($this->bulkAction) {
            'delete' => User::whereIn('id', $this->selected)->delete(),
            'export' => $this->exportSelected($this->selected), // implement if needed
            'active' => User::whereIn('id', $this->selected)
                ->each(fn($user) => $user->update(['status' => 'active'])),
            default => null,
        };

        $this->toast()
            ->success('Done!', 'Bulk action applied successfully!')
            ->flash()
            ->send();

        $this->selected   = [];
        $this->bulkAction = null;
    }

    /**
     * Delete selected user (requires password)
     */
    public function delete($id)
    {
        $authUser = Auth::user();


        $user = User::where('id', '=', $id)->first();

        if (!$user) {
            abort(403, 'WRONG USER');
        }
        $user->delete();

        $this->toast()
            ->success('Done!', 'User deleted successfully!')
            ->send();
        $this->dispatch('$refresh');

        // return $this->redirectRoute('super-admin.conclaves', navigate: true);
    }

    public function deleteUser($id)
    {
        $this->dialog()
            ->error('Are You Sure you want to delete the user')
            ->hook([
                // When using `success()`, `error()`, `warning()`, `info()` and pressing the OK button.
                'ok' => [
                    'method' => 'delete',
                    // The parameters can be anything you want: arrays, strings, int.
                    'params' => [$id]
                ],

            ])
            ->send();
    }

};
?>

<div>
    @if($leaderTeam != '')
        @php
            $subtitle =" Manage registered members in $leaderTeam Team";
        @endphp
        @else
        @php
        $subtitle =" Manage registered members in Chaper";
        @endphp
        
    @endif
    <x-fancy-header title="Members" subtitle="{{ $subtitle }}" :breadcrumbs="[
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'Members']
    ]"><x-button
            href="{{ route('admin.dashboard.teams.create', request()->query()) }}" wire:navigate>Add New</x-button>
    </x-fancy-header>
    <x-card class="relative dark:bg-dark-800">
        <x-table :$headers :$rows :$headers :$rows :filter="['quantity' => 'quantity', 'search' => 'search']"
            :quantity="[5, 15, 50, 100, 250]" paginate persistent selectable {{-- loading --}}
            wire:model.live="selected">
            @interact('column_action', $row)
            {{-- Delete --}}
            <x-button.circle color="red" icon="trash" wire:click="deleteUser('{{ $row->id }}')" loading />
            {{-- Edit User Link --}}
            <x-link :href="route('admin.dashboard.members.edit', ['member' => $row->id, 'chapter' => request()->query('chapter')])"
                class="inline-block px-3 py-1.5 text-sm font-medium text-white bg-green-600 rounded shadow-md hover:bg-green-700 active:shadow-inner transition duration-150 ease-in-out"
                wire:navigate>
                Edit User
            </x-link>

            {{-- Edit Team Link --}}
            <x-link :href="route('admin.dashboard.edit-team', ['member' => $row->id, 'chapter' => request()->query('chapter')])"
                class="inline-block px-3 py-1.5 text-sm font-medium text-white bg-amber-500 rounded shadow-md hover:bg-amber-600 active:shadow-inner transition duration-150 ease-in-out"
                wire:navigate>
                Edit Team
            </x-link>


            @endinteract

        </x-table>

    </x-card>

</div>
