<?php

namespace App\Livewire\Admin\SuperAdmin\Locations;

use App\Models\Chapter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    public $chapters = [];
    public $selectedChapterId = '';
    public $latitude = '';
    public $longitude = '';
    public $searchQuery = '';

    public function mount()
    {
        $this->loadChapters();
    }

    public function loadChapters()
    {
        $this->chapters = Chapter::orderBy('name')->get();
    }

    public function selectChapter($chapterId)
    {
        $chapter = Chapter::findOrFail($chapterId);
        $this->selectedChapterId = $chapter->id;
        $this->latitude = (string) $chapter->latitude ?? '';
        $this->longitude = (string) $chapter->longitude ?? '';
    }

    public function saveLocation()
    {
        $this->validate([
            'selectedChapterId' => 'required|exists:chapters,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $chapter = Chapter::findOrFail($this->selectedChapterId);
        $chapter->update([
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ]);

        $this->loadChapters();
        session()->flash('success', 'Location saved successfully for ' . $chapter->name);
    }

    public function getFilteredChaptersProperty()
    {
        if (empty($this->searchQuery)) {
            return $this->chapters;
        }

        return $this->chapters->filter(fn($chapter) => stripos($chapter->name, $this->searchQuery) !== false);
    }

    public function render()
    {
        return view('livewire.admin.superadmin.locations.index');
    }
}