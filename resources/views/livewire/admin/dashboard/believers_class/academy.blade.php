<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Url};
use App\Models\{BeliversAcademy, Chapter, AcademyBatch};
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions, WithFileUploads;

    #[Url(keep: true)]
    public $chapter;
    public $chapterId;
    public $academy = [
        'status' => '',
        'start_at' => '',
        'chapter_id' => '',
        'certificate_template' => '',
    ];

    public $certificateTemplateFile;

    public $batches = [];
    public $newBatch = ['name' => '', 'start_date' => '', 'max_students' => '', 'status' => 'open'];
    public $editingBatch = null;

    public function mount()
    {
        $academy = BeliversAcademy::with('chapter')->whereHas('chapter', fn($chapter) => $chapter->where('name', '=', $this->chapter))->first();
        $this->academy =
            $academy != null
                ? $academy->toArray()
                : [
                    'status' => '',
                    'start_at' => '',
                    'chapter_id' => '',
                    'certificate_template' => '',
                ];

        $this->loadBatches();
        $this->chapterId = Chapter::where('name', e($this->chapter))->first()->id;
    }

    public function save()
    {
        $this->validate([
            'academy.status' => 'required|in:open,closed',
            'academy.start_at' => 'required|date',
        ]);

        $academy = BeliversAcademy::first();
        if (!$academy) {
            $academy = BeliversAcademy::create([
                'status' => $this->academy['status'],
                'start_at' => $this->academy['start_at'],
                'chapter_id' => $this->chapterId,
            ]);
        } else {
            $academy->status = $this->academy['status'];
            $academy->start_at = $this->academy['start_at'];
            $academy->chapter_id = $this->chapterId;
        }

        if ($this->certificateTemplateFile) {
            $path = $this->certificateTemplateFile->store('certificate_templates', 'public');
            $academy->certificate_template = $path;
        }

        $academy->save();
        $this->dispatch('$refresh');
        $this->toast()->success('Success', 'Class status Changed')->send();
    }

    public function loadBatches()
    {
        $academy = BeliversAcademy::find($this->academy['id'] ?? null);
        if ($academy) {
            $this->batches = $academy->batches()->with('classes')->get()->toArray();
        }
    }

    public function createBatch()
    {
        $this->validate([
            'newBatch.name' => 'required|string',
            'newBatch.start_date' => 'required|date',
            'newBatch.max_students' => 'nullable|integer|min:1',
        ]);

        $academy = BeliversAcademy::find($this->academy['id'] ?? null);
        if (!$academy) {
            $this->toast()->error('Error', 'Academy not found. Please save the academy settings first.')->send();
            return;
        }

        if ($academy) {
            // Close all existing batches for this academy
            $academy->batches()->update(['status' => 'closed']);

            // Create the new batch as open
            $batch = $academy->batches()->create([
                'name' => $this->newBatch['name'],
                'start_date' => $this->newBatch['start_date'],
                'max_students' => empty($this->newBatch['max_students']) ? null : (int) $this->newBatch['max_students'],
                'status' => 'open', // Always create as open, regardless of form input
            ]);

            $this->newBatch = ['name' => '', 'start_date' => '', 'max_students' => '', 'status' => 'open'];
            $this->loadBatches();
            $this->toast()->success('Batch created successfully. Previous batches have been closed.');
        }
    }

    public function editBatch($batchId)
    {
        $this->editingBatch = collect($this->batches)->firstWhere('id', $batchId);
    }

    public function updateBatch()
    {
        $this->validate([
            'editingBatch.name' => 'required|string',
            'editingBatch.start_date' => 'required|date',
            'editingBatch.max_students' => 'nullable|integer|min:1',
            'editingBatch.status' => 'required|in:open,closed',
        ]);

        $batch = AcademyBatch::find($this->editingBatch['id']);
        if ($batch) {
            if ($this->editingBatch['status'] === 'open') {
                // If setting to open, close all other batches
                $batch->academy->batches()->where('id', '!=', $batch->id)->update(['status' => 'closed']);
            }
            $batch->update([
                'name' => $this->editingBatch['name'],
                'start_date' => $this->editingBatch['start_date'],
                'max_students' => empty($this->editingBatch['max_students']) ? null : (int) $this->editingBatch['max_students'],
                'status' => $this->editingBatch['status'],
            ]);
            $this->editingBatch = null;
            $this->loadBatches();
            $this->toast()->success('Batch updated successfully');
        }
    }

    public function deleteBatch($batchId)
    {
        $batch = AcademyBatch::find($batchId);
        if ($batch) {
            $batch->delete();
            $this->loadBatches();
            $this->toast()->success('Batch deleted successfully');
        }
    }
}; ?>

<div class="space-y-6">
    <!-- Header Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Academy Settings</h1>
                <p class="text-gray-600 mt-1">Configure academy details and manage batches</p>
            </div>
            <x-link class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors" :href="route('admin.dashboard.believers_class.index', request()->query())">
                <i class="fas fa-plus mr-2"></i>Add Classes
            </x-link>
        </div>

        <!-- Academy Configuration Card -->
        <div class="bg-gray-50 rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-cog mr-2 text-blue-600"></i>Academy Configuration
            </h2>
            <form wire:submit.prevent='save' class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Academy Status</label>
                    <select wire:model.live='academy.status' class="form-input">
                        <option value="">Select Status</option>
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                    </select>
                    @error('academy.status')
                        <span class="text-sm text-red-600 mt-1 block">{{ $message }}</span>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">Current: <span class="font-medium {{ $academy['status'] == 'open' ? 'text-green-600' : 'text-red-600' }}">{{ ucfirst($academy['status'] ?? 'Not Set') }}</span></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input type="date" wire:model='academy.start_at' class="form-input">
                    @error('academy.start_at')
                        <span class="text-sm text-red-600 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Certificate Template</label>
                    <input type="file" wire:model='certificateTemplateFile' accept=".pdf" class="form-input">
                    @error('certificateTemplateFile')
                        <span class="text-sm text-red-600 mt-1 block">{{ $message }}</span>
                    @enderror
                    @if($academy['certificate_template'])
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-file-pdf mr-1 text-red-500"></i>Current: {{ basename($academy['certificate_template']) }}
                        </p>
                    @else
                        <p class="text-xs text-gray-500 mt-1">No template uploaded</p>
                    @endif
                </div>

                <div class="md:col-span-3">
                    <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <span wire:loading.remove>
                            <i class="fas fa-save mr-2"></i>Save Changes
                        </span>
                        <span wire:loading wire:target="save" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saving...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Batches Management Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-users mr-2 text-green-600"></i>Batch Management
                </h2>
                <p class="text-gray-600 mt-1">Create and manage student batches for this academy</p>
            </div>
            <div class="text-sm text-gray-500">
                Total Batches: {{ count($batches) }}
            </div>
        </div>

        <!-- Create Batch Form -->
        <div class="bg-green-50 rounded-lg p-6 mb-6 border border-green-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-plus-circle mr-2 text-green-600"></i>Create New Batch
            </h3>
            <form wire:submit.prevent="createBatch" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Batch Name</label>
                    <input type="text" wire:model="newBatch.name" class="form-input" placeholder="e.g., Batch A">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input type="date" wire:model="newBatch.start_date" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Max Students</label>
                    <input type="number" wire:model="newBatch.max_students" class="form-input" placeholder="Leave empty for unlimited">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select wire:model="newBatch.status" class="form-input">
                        <option value="open">Open for Registration</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="md:col-span-4">
                    <button type="submit" class="inline-flex items-center px-6 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>Create Batch
                    </button>
                </div>
            </form>
        </div>

        <!-- Batches List -->
        @if(count($batches) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($batches as $batch)
                    <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                        @if($editingBatch && $editingBatch['id'] == $batch['id'])
                            <div class="mb-4">
                                <h4 class="text-lg font-semibold text-gray-900 mb-4">Edit Batch</h4>
                                <form wire:submit.prevent="updateBatch" class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <input type="text" wire:model="editingBatch.name" class="form-input" placeholder="Batch Name">
                                        <input type="date" wire:model="editingBatch.start_date" class="form-input">
                                        <input type="number" wire:model="editingBatch.max_students" class="form-input" placeholder="Max Students">
                                        <select wire:model="editingBatch.status" class="form-input">
                                            <option value="open">Open</option>
                                            <option value="closed">Closed</option>
                                        </select>
                                    </div>
                                    <div class="flex space-x-3">
                                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                            <i class="fas fa-save mr-2"></i>Save Changes
                                        </button>
                                        <button wire:click="$set('editingBatch', null)" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @else
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center mb-3">
                                        <div class="w-10 h-10 {{ $batch['status'] == 'open' ? 'bg-green-100' : 'bg-red-100' }} rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-users {{ $batch['status'] == 'open' ? 'text-green-600' : 'text-red-600' }}"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-lg font-semibold text-gray-900">{{ $batch['name'] }}</h4>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $batch['status'] == 'open' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ ucfirst($batch['status']) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="space-y-2 text-sm text-gray-600">
                                        <div class="flex items-center">
                                            <i class="fas fa-calendar mr-2 text-blue-500"></i>
                                            Start Date: {{ \Carbon\Carbon::parse($batch['start_date'])->format('M d, Y') }}
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-user-friends mr-2 text-purple-500"></i>
                                            Students: {{ count($batch['students'] ?? []) }}{{ $batch['max_students'] ? ' / ' . $batch['max_students'] : '' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col space-y-2 ml-4">
                                    <button wire:click="editBatch({{ $batch['id'] }})" class="inline-flex items-center px-3 py-2 bg-yellow-500 text-white text-sm font-medium rounded-lg hover:bg-yellow-600 transition-colors">
                                        <i class="fas fa-edit mr-1"></i>Edit
                                    </button>
                                    <button wire:click="deleteBatch({{ $batch['id'] }})" class="inline-flex items-center px-3 py-2 bg-red-500 text-white text-sm font-medium rounded-lg hover:bg-red-600 transition-colors">
                                        <i class="fas fa-trash mr-1"></i>Delete
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                <i class="fas fa-users text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Batches Yet</h3>
                <p class="text-gray-500">Create your first batch to start organizing students.</p>
            </div>
        @endif
    </div>
</div>
