<?php

use App\Models\Chapter;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;

new  #[Layout('components.layouts.admin')]  class extends Component {
    use Interactions;

    public $id;

    public $name;

    public $data = [
        'address' => '',
        'city' => '',
        'state' => '',
        'country' => '',
        'description' => ''
    ];

    public function mount(string $conclave)
    {
        $conclave = Chapter::where('name', '=', $conclave)->firstOrFail();

        
        $this->id = $conclave->id;
        $this->name = $conclave->name;
        $this->data =$conclave->data;

    }

    public function save()
    {
        // Validate the input
        $validatedData = $this->validate([
            'name' => 'required|min:3', // Assuming 'id' is the PK in Chapter
            'data.address' => 'required|min:2',
            'data.city' => 'required',
            'data.state' => 'required',
            'data.country' => 'required',
            'data.description' => 'nullable|string'
        ]);

        $conclave = Chapter::where('id', '=', $this->id)->firstOrFail();
        $conclave->fill($validatedData);
        $conclave->save();

        $this->toast()
            ->success('Done!', 'Chapter created successfully!')
            ->flash()
            ->send();

        // Optional: return or flash message
        return $this->redirect(route('super-admin.conclaves'));
    }

}; ?>

<div>
    <x-card>
        <form wire:submit.prevent='save'>
            <input type="hidden" wire:model='id' value="{{ $id }}">
            <!-- Email Address -->
            <flux:input wire:model="name" label="Name" type="text" required autocomplete="name" placeholder="Name" />
            <div class="grid md:grid-cols-2 mt-4  gap-2">
                <flux:input wire:model="data.address" label="Address *" type="text" required autocomplete="name"
                    placeholder="Name" />
                <flux:input label="City *" invalidate wire:model='data.city'></flux:input>
                <flux:input label="State *" invalidate wire:model='data.state'></flux:input>
                <flux:input label="Country *" invlaidate wire:model='data.country'></flux:input>
            </div>

            <div class="mt-3">
                <flux:textarea label="Dscription" wire:model='data.description'></flux:textarea>
            </div>

            <button class="bg-white text-gray-800 dark:bg-zinc-900 dark:text-white border border-gray-300 dark:border-zinc-700 px-6 py-2 mt-5 rounded hover:bg-gray-100 dark:hover:bg-zinc-800 transition-colors duration-200">
                <span wire:loading.remove>Save</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </form>
    </x-card>
</div>
