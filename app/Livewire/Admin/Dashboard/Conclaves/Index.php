<?php

namespace App\Livewire\Admin\Dashboard\Conclaves;

use App\Models\Conclave;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

class Index extends Component
{
    use Interactions, WithPagination, WithFileUploads;

    public $search = '';
    public $perPage = 10;
    public $showModal = false;

    // Form fields
    public $conclaveId = null;
    public $name = '';
    public $location = '';
    public $description = '';
    public $address = '';
    public $phone = '';
    public $email = '';
    public $whatsapp_link = '';
    public $latitude = '';
    public $longitude = '';
    public $image = null;
    public $existingImage = null;
    public $isActive = true;

    public function mount(): void
    {
        if (!auth()->user()->hasRole(['admin', 'super-admin'])) {
            abort(403, 'Unauthorized access.');
        }
    }

    public function render()
    {
        $conclaves = Conclave::query()
            ->when($this->search, fn($q) => 
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('location', 'like', '%' . $this->search . '%')
            )
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        return view('livewire.admin.dashboard.conclaves.index', [
            'conclaves' => $conclaves
        ])->layout('components.layouts.admin');
    }

    public function create(): void
    {
        $this->resetForm();
        $this->conclaveId = null;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $conclave = Conclave::findOrFail($id);
        
        $this->conclaveId = $conclave->id;
        $this->name = $conclave->name;
        $this->location = $conclave->location;
        $this->description = $conclave->description;
        $this->address = $conclave->address;
        $this->phone = $conclave->phone;
        $this->email = $conclave->email;
        $this->whatsapp_link = $conclave->whatsapp_link;
        $this->latitude = $conclave->latitude;
        $this->longitude = $conclave->longitude;
        $this->existingImage = $conclave->image;
        $this->isActive = $conclave->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        try {
            $validated = $this->validate([
                'name' => 'required|string|max:255',
                'location' => 'required|string|max:255',
                'description' => 'nullable|string',
                'address' => 'nullable|string|max:500',
                'phone' => 'nullable|string|max:50',
                'email' => 'nullable|email|max:255',
                'whatsapp_link' => 'nullable|url|max:500',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'image' => 'nullable|image|max:5120',
            ]);

            $data = [
                'name' => $validated['name'],
                'location' => $validated['location'],
                'description' => $validated['description'] ?? null,
                'address' => $validated['address'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'whatsapp_link' => $validated['whatsapp_link'] ?? null,
                'latitude' => !empty($validated['latitude']) ? $validated['latitude'] : null,
                'longitude' => !empty($validated['longitude']) ? $validated['longitude'] : null,
                'is_active' => $this->isActive,
            ];

            if ($this->image instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                if ($this->existingImage) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($this->existingImage);
                }
                $data['image'] = $this->image->store('conclaves', 'public');
            }

            if ($this->conclaveId) {
                Conclave::where('id', $this->conclaveId)->update($data);
                $this->toast()->success('Updated', 'Conclave updated successfully.')->send();
            } else {
                Conclave::create($data);
                $this->toast()->success('Created', 'Conclave created successfully.')->send();
            }

            $this->showModal = false;
            $this->resetForm();
        } catch (\Exception $e) {
            $this->toast()->error('Error', 'Failed to save: ' . $e->getMessage())->send();
        }
    }

    public function delete(int $id): void
    {
        try {
            $conclave = Conclave::findOrFail($id);
            if ($conclave->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($conclave->image);
            }
            $conclave->delete();
            $this->toast()->success('Deleted', 'Conclave deleted successfully.')->send();
        } catch (\Exception $e) {
            $this->toast()->error('Error', 'Failed to delete: ' . $e->getMessage())->send();
        }
    }

    private function resetForm(): void
    {
        $this->conclaveId = null;
        $this->name = '';
        $this->location = '';
        $this->description = '';
        $this->address = '';
        $this->phone = '';
        $this->email = '';
        $this->whatsapp_link = '';
        $this->latitude = '';
        $this->longitude = '';
        $this->image = null;
        $this->existingImage = null;
        $this->isActive = true;
    }
}
