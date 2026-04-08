<?php

use App\Models\Chapter;
use App\Models\Team;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithFileUploads, Interactions;

    public Team $team;

    public $name;
    public $short;
    public $banner;      // TemporaryUploadedFile for new uploads
    public $bannerPath;  // Existing banner string
    public $has_team_lead = true;

    #[Url]
    public ?string $chapter = null; // automatically populated from ?chapter=...

    public function mount(Team $team)
    {
        $this->team = $team;

        $this->name          = $this->team->name;
        $this->short         = $this->team->short;
        $this->has_team_lead = ($this->team->has_team_lead == 1) ? true : false;
        // $this->chapter = $this->team->chapter_id;

        $this->bannerPath = $this->team->banner; // existing banner
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'short' => 'nullable|string|max:100',
            'banner' => 'nullable|file|image|max:2048',
        ]);

        // If a new file is uploaded, store it and delete old banner
        if ($this->banner) {
            // Delete old banner if it exists
            if ($this->team->banner && file_exists(storage_path('app/public/' . $this->team->banner))) {
                unlink(storage_path('app/public/' . $this->team->banner));
            }

            // Store new banner
            $path             = $this->banner->store('banners', 'public');
            $this->bannerPath = $path;
        }

        // Update team
        $this->team->update([
            'name' => $this->name,
            'short' => $this->short,
            'chapter_id' => Chapter::where('name', $this->chapter)->firstOrFail()->id,
            'banner' => $this->bannerPath,
            'has_team_lead' => ($this->has_team_lead == true) ? 1 : 0,
        ]);

        $this->toast()->success('Team Updated', 'The team was successfully updated!')->send();

        return $this->redirectRoute('admin.dashboard.teams', ['chapter' => $this->chapter], navigate: true);
    }


    // Delete uploaded banner
    public function deleteUpload()
    {
        if ($this->bannerPath && file_exists(storage_path('app/public/' . $this->bannerPath))) {
            unlink(storage_path('app/public/' . $this->bannerPath));
        }
        $this->banner     = null;
        $this->bannerPath = null;
        $this->toast()->success('Deleted', 'Banner image removed')->send();
    }
};

?>
<div>
    <x-fancy-header title="Edit Team" subtitle="Update team details" :breadcrumbs="[
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'Teams', 'url' => route('admin.dashboard.teams', ['chapter' => request()->query('chapter')])],
        ['label' => 'Edit Team']
    ]" />

    <x-card class="dark:bg-dark-800">
        <form wire:submit.prevent="save" class="space-y-4">
            {{-- Team Name --}}
            <x-input label="Team Name" wire:model.defer="name" required />

            {{-- Short Name --}}
            <x-input label="Short Name" wire:model.defer="short" />

            {{-- Has Team Lead --}}
            <x-checkbox  label="Has Team Lead" wire:model="has_team_lead" />

            {{-- Banner Upload --}}
            <x-upload label="Team Banner" wire:model="banner" :preview="$bannerPath" delete
                delete-method="deleteUpload" />

            <x-button type="submit" class="mt-4">Save Changes</x-button>
        </form>
    </x-card>
</div>