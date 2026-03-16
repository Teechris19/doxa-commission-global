<?php

use App\Models\Testimony;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions, WithPagination;

    public ?int $quantity = 10;
    public ?string $search = null;
    public array $selected = [];
    public ?string $status = null;
    public $testimony = null;

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
            'headers' => [
                ['index' => 'name', 'label' => 'Name'],
                ['index' => 'email', 'label' => 'Email'],
                ['index' => 'status', 'label' => 'Status'],
                ['index' => 'action', 'label' => 'Action']
            ],
            'rows' => $this->rows(),
        ];
    }

    /**
     * Query rows with filtering + pagination
     */
    public function rows()
    {
        return Testimony::latest()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->paginate($this->quantity)
            ->withQueryString();
    }

    public function updatedStatus()
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
     * Delete testimony
     */
    public function delete($id)
    {
        $testimony = Testimony::findOrFail($id);
        $testimony->delete();

        $this->toast()->success('Done!', 'Testimony deleted successfully!')->send();
        $this->dispatch('$refresh');
    }

    public function deleteTestimony($id)
    {
        $this->dialog()
            ->error('Are You Sure you want to delete this Testimony?')
            ->hook([
                'ok' => [
                    'method' => 'delete',
                    'params' => [$id],
                ],
            ])
            ->send();
    }

    /**
     * Approve testimony
     */
    public function approve(int $id)
    {
        $testimony = Testimony::findOrFail($id);
        $testimony->status = 'approved';
        $testimony->save();

        $this->toast()->success('Done!', 'Testimony approved successfully!')->send();
        $this->dispatch('$refresh');
    }

    /**
     * Reject testimony
     */
    public function reject(int $id)
    {
        $testimony = Testimony::findOrFail($id);
        $testimony->status = 'rejected';
        $testimony->save();

        $this->toast()->info('Done!', 'Testimony rejected')->send();
        $this->dispatch('$refresh');
    }

    /**
     * Load testimony details
     */
    public function loadTestimony(int $id)
    {
        $this->testimony = Testimony::findOrFail($id);
    }
};
?>

<div>
    <x-fancy-header title="Testimonies" subtitle="View and Manage All Testimonies" :breadcrumbs="[
        ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
        ['label' => 'Testimonies'],
    ]">
    </x-fancy-header>

    <x-modal title="View Testimony" size="2xl" id="testimony-modal">
        @if ($testimony == null)
            <div class="flex justify-center">
                <x-spinner-loader color="white" size="xl"></x-spinner-loader>
            </div>
        @else
            <div class="overflow-y-scroll max-h-[70vh]">
                <p class="text-3xl font-bold mb-2">
                    {{ $testimony?->name ?? 'Anonymous' }}
                </p>
                <p class="text-lg text-gray-600 dark:text-gray-400 mb-4">
                    {{ $testimony?->email }}
                </p>
                <p class="text-sm text-gray-500 mb-4">
                    Submitted: {{ $testimony?->created_at?->format('M d, Y h:i A') }}
                </p>
                @if ($testimony?->image)
                    <div class="mb-4">
                        <img src="{{ asset('storage/' . $testimony->image) }}" alt="Testimony Image" class="max-w-full h-auto rounded-lg">
                    </div>
                @endif
                <p class="mt-4 whitespace-pre-line text-gray-800 dark:text-gray-200">
                    {{ $testimony?->testimony }}
                </p>
                <div class="mt-6 flex gap-2">
                    <span class="px-3 py-1 rounded-full text-sm font-semibold
                        {{ $testimony?->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $testimony?->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                        {{ $testimony?->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                        {{ ucfirst($testimony?->status) }}
                    </span>
                </div>
            </div>
        @endif
    </x-modal>

    <x-card class="relative dark:bg-dark-800">
        <x-table :$headers :$rows :filter="['quantity' => 'quantity', 'search' => 'search']" :quantity="[5, 15, 50, 100, 250]" paginate persistent selectable
            wire:model.live="selected">
            <x-slot:header>
                <div class="flex items-center gap-4">
                    <p class="font-semibold">Filter by Status:</p>
                    <x-select.native :options="[
                        ['label'=>'All', 'value'=>null],
                        ['label' => 'Pending', 'value' => 'pending'],
                        ['label' => 'Approved', 'value' => 'approved'],
                        ['label' => 'Rejected', 'value' => 'rejected'],
                    ]" wire:model.live='status' class="w-48" />
                </div>
            </x-slot:header>

            @interact('column_status', $row)
                <span class="px-2 py-1 rounded-full text-xs font-semibold
                    {{ $row->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                    {{ $row->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                    {{ $row->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                    {{ ucfirst($row->status) }}
                </span>
            @endinteract

            @interact('column_action', $row)
                {{-- View Testimony --}}
                <x-button.circle color="green" icon="eye"
                    x-on:click="$modalOpen('testimony-modal');$wire.call('loadTestimony', {{ $row->id }})" />

                {{-- Approve/Reject Actions --}}
                @if ($row->status === 'pending' || $row->status === 'rejected')
                    <x-button.circle color="green" icon="check" wire:click='approve({{ $row->id }})'
                        title="Approve" />
                @endif

                @if ($row->status === 'pending' || $row->status === 'approved')
                    <x-button.circle color="orange" icon="x" wire:click='reject({{ $row->id }})'
                        title="Reject" />
                @endif

                {{-- Delete Testimony --}}
                <x-button.circle color="red" icon="trash" wire:click="deleteTestimony('{{ $row->id }}')" />
            @endinteract
        </x-table>
    </x-card>
</div>
