<?php

namespace App\Livewire\Admin\SuperAdmin\Locations;

use App\Models\Chapter;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

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
        $chapter = DB::table('chapters')->where('id', $chapterId)->first();
        $this->selectedChapterId = $chapterId;
        $this->latitude = $chapter && $chapter->latitude ? (string) $chapter->latitude : '';
        $this->longitude = $chapter && $chapter->longitude ? (string) $chapter->longitude : '';
    }

    public function saveLocation()
    {
        $this->validate([
            'selectedChapterId' => 'required',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        DB::table('chapters')
            ->where('id', $this->selectedChapterId)
            ->update([
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ]);

        $this->loadChapters();
        session()->flash('success', 'Location saved successfully.');
    }

    public function render()
    {
        return view('livewire.admin.superadmin.locations.index');
    }
}