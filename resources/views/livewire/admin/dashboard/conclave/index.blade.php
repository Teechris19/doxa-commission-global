<?php

use App\Models\Chapter;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

new  #[Layout('components.layouts.admin')]  class extends Component {
    use Interactions, WithPagination;

    public ?int $quantity = 10;

    public ?string $search = null;

    public array $selected = [];

    public $password;

    public $selected_id;

    public ?string $bulkAction = null;

    // Computed property for rows
    public function getRowsProperty()
    {
        return Chapter::query()
            ->when($this->search, fn(Builder $query) => $query->where('name', 'like', "%{$this->search}%"))
            ->paginate($this->quantity)
            ->withQueryString();
    }

    // Get all row IDs for Select All
    public function ids(): array
    {
        return $this->rows->pluck('id')->toArray();
    }

    // Select all rows
    public function selectAll()
    {
        $this->selected = $this->ids();
    }

    public $showDeleteModal = false;

    // Apply the bulk action
    public function applyBulkAction()
    {
        if (!$this->bulkAction || empty($this->selected)) {
            return;
        }

        if ($this->bulkAction == 'delete') {
            $this->showDeleteModal = true;
            return;
        }


        match ($this->bulkAction) {
            'delete' => Chapter::whereIn('id', $this->selected)->delete(),
            'export' => $this->exportSelected($this->selected), // define exportSelected()
            'active' => Chapter::whereIn('id', $this->selected)->each(function ($chapter) {
                    $data           = $chapter->data;
                    $data['status'] = 'active';
                    $chapter->update(['data' => $data]);
                }),
        };

        // Clear selection after applying
        $this->selected   = [];
        $this->bulkAction = null;
    }

    public function delete()
    {
        $user = Auth::user();

        if (!$this->password || $this->password == null) {
            abort(403, 'INPUT YOUR PASSWORD');
        }

        if (!Hash::check($this->password, $user->password)) {
            abort(403, 'WRONG PASSWORD');
        }

        $chapter = Chapter::where('id', '=', $this->selected_id)->first();

        if (!$chapter) {
            abort(403, 'WRONG CHAPTER');
        }

        if ($user->hasRole('super-admin')) {
            $id = $chapter->id;
            if ($chapter->delete()) {
                User::where('chapter_id', '=', $id)->delete();
            }
        }

        $this->toast()
            ->success('Done!', 'Chapter Deleted Successfully!')
            ->flash()
            ->send();

        return $this->redirectRoute('super-admin.conclaves');

    }


    public function with(): array
    {
        return [
            'headers' => [
                ['index' => 'name', 'label' => 'Name'],
                ['index' => 'action', 'label'=>'Action'],

            ],
            'rows' => $this->rows
        ];
    }
}; ?>

<div>
    <x-card class="relative">
        <flux:link href="{{ route('super-admin.conclaves.create') }}" icon="plus"
            class=" px-3 py-1 bg-zinc-900 text-white rounded cursor-pointer  float-right mb-4">
            Add New</flux:link>
        <x-table :$headers :$rows :filter="['quantity' => 'quantity', 'search' => 'search']" :quantity="[5, 15, 50, 100, 250]" paginate persistent selectable wire:model.live="selected">
            <x-slot:header>
                @if(count($selected) > 0)
                    <div class="space-y-2">
                        <!-- Selected count + Select All -->
                        <div>
                            You have selected {{ count($selected) }}
                            @if(count($selected) < $rows->total())
                                <button type="button" class="underline cursor-pointer text-blue-600 ml-2"
                                    wire:click="selectAll">
                                    Select All
                                </button>
                            @endif
                        </div>

                        <div class="flex space-x-2 mb-4">
                            <flux:select wire:model='bulkAction'>
                                <flux:select.option selected class="option" value="delete">Delete</flux:select.option>
                                <flux:select.option class="option" value="Export">Export</flux:select.option>
                                <flux:select.option class="option" value="active">Mark As Active</flux:select.option>
                            </flux:select>

                            <button type="button" class="px-3 py-1 bg-blue-600 text-white rounded cursor-pointer"
                                wire:click="applyBulkAction" @disabled(empty($bulkAction))>
                                Apply
                            </button>

                        </div>
                    </div>
                @endif
            </x-slot:header>

            @interact('column_action', $row)
            <x-button.circle color="red" icon="trash"
                x-on:click="$wire.set('selected_id', {{ $row?->id }}); $wire.set('showDeleteModal', true);" />
            <x-link href="{{ route('super-admin.conclaves.edit', ['conclave' => $row->name]) }}"
                class="ml-2 bg-green-700 text-white p-2 rounded-full" icon="pencil" wire:navigate></x-link>
            @endinteract
        </x-table>
    </x-card>

    <x-modal center id="confirm-delete" wire:model="showDeleteModal" blur>
        <div class="flex flex-col items-center space-y-4">
            <!-- Warning Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" class="text-red-600 size-36" viewBox="0 0 24 24"
                stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>

            <p class="text-red-600">Deleting a chapter will delete all its data and users alike</p>
            <!-- Modal Text -->
            <p class="text-center text-gray-700 dark:text-white text-lg font-medium">
                Please enter your password to proceed
            </p>

            <!-- Password Input -->
            <x-input label="Password" type="password" wire:model.defer="password" required clearable="true"
                class="w-full"></x-input>

            <!-- Submit Button -->
            <button
                class="bg-red-700 hover:bg-red-600 dark:bg-red-700 dark:hover:bg-red-700 text-white font-bold py-2 px-6 rounded mt-2"
                wire:click="delete">
                Confirm
            </button>
        </div>
    </x-modal>

</div>
