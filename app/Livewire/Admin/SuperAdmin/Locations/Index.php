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
    public $address = '';
    public $phone = '';
    public $email = '';
    public $serviceTimes = [];
    public $newServiceDay = 'sunday';
    public $newServiceTime = '';
    public $searchQuery = '';

    public function addServiceTime()
    {
        if (!$this->newServiceDay || !$this->newServiceTime) {
            return;
        }

        $this->serviceTimes[$this->newServiceDay] = $this->newServiceTime;
        $this->newServiceTime = '';
    }

    public function removeServiceTime($day)
    {
        unset($this->serviceTimes[$day]);
    }

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
        
        $data = $chapter && $chapter->data ? json_decode($chapter->data, true) : [];
        $this->address = $data['address'] ?? ($data['location'] ?? '');
        $this->phone = $data['phone'] ?? '';
        $this->email = $data['email'] ?? '';
        
        if (isset($data['service_times']) && is_array($data['service_times'])) {
            $this->serviceTimes = $data['service_times'];
        } else {
            $this->serviceTimes = [];
        }
    }

    public function reverseGeocode()
    {
        if (!$this->latitude || !$this->longitude) {
            return;
        }

        try {
            $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$this->latitude}&lon={$this->longitude}";
            $response = file_get_contents($url);
            $data = json_decode($response, true);
            
            if ($data && isset($data['display_name'])) {
                $this->address = $data['display_name'];
            }
        } catch (\Exception $e) {
            // Handle error silently
        }
    }

    public function saveLocation()
    {
        $this->validate([
            'selectedChapterId' => 'required',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $chapter = Chapter::find($this->selectedChapterId);
        $existingData = $chapter && $chapter->data ? json_decode($chapter->data, true) : [];
        
        $newData = array_merge($existingData, [
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'service_times' => $this->serviceTimes,
            'location' => $this->address,
        ]);

        DB::table('chapters')
            ->where('id', $this->selectedChapterId)
            ->update([
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'data' => json_encode($newData),
            ]);

        $this->loadChapters();
        session()->flash('success', 'Location and details saved successfully.');
    }

    public function render()
    {
        return view('livewire.admin.superadmin.locations.index');
    }
}