<?php
// TODO: Add ability to export selected prayer requests
// TODO: add export all prayer requests

use App\Models\PrayerRequest;
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
    public $prayerRequest = null;
    public ?string $isAddressed = null;

    #[Url]
    public ?string $chapter = null;

    public function mount()
    {
        $this->rows();
    }

    /**
     * Table headers
     */
    public function with(): array
    {
        
        return [
            'headers' => [['index' => 'name', 'label' => 'Name'], ['index' => 'email', 'label' => 'Email'], ['index' => 'action', 'label' => 'Action']],
            'rows' => $this->rows(),
        ];
    }

    /**
     * Query rows with filtering + pagination
     */
    public function rows()
    {
        return PrayerRequest::latest()->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))->when($this->chapter, fn($q) => $q->whereHas('chapter', fn($qq) => $qq->where('name', $this->chapter)))->when($this->isAddressed, fn($q) => $q->where('is_addressed', $this->isAddressed))->paginate($this->quantity)->withQueryString();
    }
    public function updatedisAddressed()
    {
        $this->rows();
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
    // public function applyBulkAction()
    // {
    //     if (!$this->bulkAction || empty($this->selected))
    //         return;

    //     match ($this->bulkAction) {
    //         'delete' => Team::whereIn('id', $this->selected)->delete(),
    //         'export' => $this->exportSelected($this->selected), // optional
    //         default => null,
    //     };

    //     $this->toast()->success('Done!', 'Bulk action applied successfully!')->flash()->send();
    //     $this->selected   = [];
    //     $this->bulkAction = null;
    // }

    public function delete($id)
    {
        $prayer_request = PrayerRequest::where('id', '=', $id)->firstOrFail();
        $prayer_request->delete();

        $this->toast()->success('Done!', 'Prayer Request deleted successfully!')->send();
        $this->dispatch('$refresh');
    }

    /**
     * Delete single team
     */
    public function deletePrayerRequest($id)
    {
        $this->dialog()
            ->error('Are You Sure you want to delete this Prayer Request')
            ->hook([
                'ok' => [
                    'method' => 'delete',
                    'params' => [$id],
                ],
            ])
            ->send();
    }

    public function addressed(int $id)
    {
        $prayer_request = PrayerRequest::where('id', '=', $id)->firstOrFail();
        $prayer_request->is_addressed = 'done';
        $prayer_request->save();

        $this->toast()->success('Done!', 'Congratulations!!!!! One Soul Prayed For')->send();
        $this->dispatch('$refresh');
    }

    public function loadPrayerRequest(int $id)
    {
        $this->prayerRequest = PrayerRequest::where('id', '=', $id)->firstOrFail();
    }
};
?>

<div>
    <x-fancy-header title="Prayer Request" subtitle="View and Manage All Prayer Request" :breadcrumbs="[
        ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
        ['label' => 'Prayer Request'],
    ]">
    </x-fancy-header>


    <x-modal title="View Prayer Request" size="2xl" id="request-modal">
        @if ($prayerRequest == null)
            <div class="flex justify-center">
                <x-spinner-loader color="white" size="xl"></x-spinner-loader>
            </div>
        @else
            <div class="overflow-y-scroll max-h-[70vh]">
                <p class="text-3xl">
                    {{ $prayerRequest?->name }}
                </p>
                <p class="text-lg">
                    {{ $prayerRequest?->email }}
                </p>
                <p class="mt-4 whitespace-pre-line">
                    {{ $prayerRequest?->request }}
                </p>
            </div>
        @endif
    </x-modal>

    <x-card class="relative dark:bg-dark-800">
        <x-table :$headers :$rows :filter="['quantity' => 'quantity', 'search' => 'search']" :quantity="[5, 15, 50, 100, 250]" paginate persistent selectable
            wire:model.live="selected">
            <x-slot:header>
                <p>Prayed For {{ $isAddressed }} </p>
                <x-select.native :options="[
                    ['label'=>'Select', 'value'=>null],
                    ['label' => 'Done', 'value' => 'done'],
                    ['label' => 'Pending', 'value' => 'pending'],
                ]" wire:model.live='isAddressed' class="mb-4" />
            </x-slot:header>
            @interact('column_action', $row)
                {{-- Delete Team --}}
                <x-button.circle color="red" icon="trash" wire:click="deletePrayerRequest('{{ $row->id }}')" />

                @if ($row?->is_addressed == 'done')
                    <x-button disabled aria-disabled="true" color="green">Done</x-button>
                @else
                    <x-button wire:click='addressed({{ $row->id }})'>Mark as Done</x-button>
                @endif

                <x-button.circle color="green" icon="eye"
                    x-on:click="$modalOpen('request-modal');$wire.call('loadPrayerRequest', {{ $row->id }})" />
            @endinteract
        </x-table>
    </x-card>
</div>
