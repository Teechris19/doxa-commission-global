<?php

use App\Models\{Team, Chapter};
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithFileUploads, Interactions;

    public $name;
    public $short;
    public $banner;
    public $has_team_lead = true;

    #[Url]
    public ?string $chapter; // automatically populated from ?chapter=...
    public $chapterId;

    public function mount()
    {
        $this->chapterId = $this->chapter != null ? Chapter::where('name', $this->chapter)->firstOrFail()->id : null;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'short' => 'nullable|string|max:100',
            'banner' => 'nullable|string',
        ]);


        Team::create([
            'name' => $this->name,
            'short' => $this->short,
            'chapter_id' => $this->chapterId, // assign chapter from URL
            'banner' => $this->banner,
            'has_team_lead' => $this->has_team_lead,
        ]);

        $this->toast()->success('Team Created', 'The team was successfully created!')->send();

        return $this->redirectRoute('admin.dashboard.teams', ['chapter' => $this->chapter], navigate: true);
    }

    // Delete uploaded banner
    public function deleteUpload()
    {
        if ($this->banner && file_exists(storage_path('app/public/' . $this->banner))) {
            unlink(storage_path('app/public/' . $this->banner));
        }
        $this->banner = null;
        $this->toast()->success('Deleted', 'Banner image removed')->send();
    }
};

?>
<div>
    <x-fancy-header title="Create Team" subtitle="Add a new team" :breadcrumbs="[
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'Teams', 'url' => route('admin.dashboard.teams', ['chapter' => request()->query('chapter')])],
        ['label' => 'Create Team'],
    ]" />

    <x-card class="dark:bg-dark-800">
        <form wire:submit.prevent="save" class="space-y-4">
            {{-- Team Name --}}
            <x-input label="Team Name" wire:model.defer="name" required />

            {{-- Short Name --}}
            <x-input label="Short Name" wire:model.defer="short" />

            {{-- Has Team Lead --}}
            <x-toggle label="Has Team Lead" wire:model.defer="has_team_lead" />

            {{-- Banner Upload --}}
            <x-upload label="Team Banner" wire:model="banner" preview delete delete-method="deleteUpload" />

            <x-button type="submit" class="mt-4">Create Team</x-button>
        </form>
    </x-card>
</div>
