<?php

use App\Models\Chapter;
use App\Models\PartnershipFormField;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions;

    public ?int $chapterId = null;
    public ?string $chapterName = null;

    // Form fields array
    public array $formFields = [];
    
    // Modal state
    public bool $showFieldModal = false;
    public $editingIndex = null;

    // Field properties
    public string $fieldType = 'text';
    public string $fieldLabel = '';
    public string $fieldName = '';
    public ?string $fieldPlaceholder = '';
    public bool $fieldRequired = false;
    public string $fieldOptions = '';
    public string $fieldDescription = '';

    // Available field types
    public array $availableFieldTypes = [
        'text' => 'Text Input',
        'textarea' => 'Text Area',
        'number' => 'Number',
        'email' => 'Email',
        'tel' => 'Phone Number',
        'date' => 'Date',
        'checkbox' => 'Checkbox',
        'select' => 'Dropdown Select',
    ];

    public function mount(): void
    {
        $user = auth()->user();
        $this->chapterId = $user->chapter_id;
        $chapter = Chapter::find($this->chapterId);
        $this->chapterName = $chapter?->name ?? 'Not set';
        
        $this->loadFormFields();
    }

    public function loadFormFields(): void
    {
        $this->formFields = PartnershipFormField::where(function($query) {
                $query->where('chapter_id', $this->chapterId)
                      ->orWhereNull('chapter_id');
            })
            ->orderBy('sort_order')
            ->get()
            ->map(function($field) {
                return [
                    'id' => $field->id,
                    'label' => $field->label,
                    'name' => $field->name,
                    'type' => $field->type,
                    'options' => $field->options_array ?? [],
                    'description' => $field->description,
                    'placeholder' => $field->placeholder,
                    'required' => $field->is_required,
                    'active' => $field->is_active,
                ];
            })
            ->toArray();
    }

    public function openFieldModal(): void
    {
        $this->resetFieldForm();
        $this->editingIndex = null;
        $this->showFieldModal = true;
    }

    public function editField($index): void
    {
        if (!isset($this->formFields[$index])) {
            $this->toast()->error('Error', 'Field not found.')->send();
            return;
        }

        $field = $this->formFields[$index];
        $this->editingIndex = $index;
        $this->fieldType = $field['type'];
        $this->fieldLabel = $field['label'];
        $this->fieldName = $field['name'];
        $this->fieldPlaceholder = $field['placeholder'] ?? '';
        $this->fieldRequired = $field['required'] ?? false;
        $this->fieldOptions = is_array($field['options']) ? implode("\n", $field['options']) : '';
        $this->fieldDescription = $field['description'] ?? '';
        $this->showFieldModal = true;
    }

    public function saveField(): void
    {
        $validated = $this->validate([
            'fieldLabel' => 'required|string|max:255',
            'fieldName' => 'required|string|max:100',
            'fieldType' => 'required|in:text,textarea,select,number,email,tel,date,checkbox',
            'fieldOptions' => 'nullable|string',
            'fieldDescription' => 'nullable|string|max:500',
            'fieldPlaceholder' => 'nullable|string|max:255',
        ]);

        // Parse options for select fields
        $optionsArray = null;
        if ($this->fieldType === 'select' && !empty($this->fieldOptions)) {
            $optionsArray = array_filter(array_map('trim', explode("\n", $this->fieldOptions)));
        }

        if ($this->editingIndex !== null) {
            // Update existing field
            $fieldId = $this->formFields[$this->editingIndex]['id'] ?? null;
            
            if ($fieldId) {
                $field = PartnershipFormField::find($fieldId);
                if ($field) {
                    $field->update([
                        'label' => $validated['fieldLabel'],
                        'name' => $validated['fieldName'],
                        'type' => $validated['fieldType'],
                        'options' => $optionsArray,
                        'description' => $validated['fieldDescription'],
                        'placeholder' => $validated['fieldPlaceholder'],
                        'is_required' => $this->fieldRequired,
                        'is_active' => true,
                    ]);
                }
            } else {
                // Update array only (no database ID yet)
                $this->formFields[$this->editingIndex] = [
                    'label' => $validated['fieldLabel'],
                    'name' => $validated['fieldName'],
                    'type' => $validated['fieldType'],
                    'options' => $optionsArray ?? [],
                    'description' => $validated['fieldDescription'],
                    'placeholder' => $validated['fieldPlaceholder'],
                    'required' => $this->fieldRequired,
                ];
            }
            
            $this->toast()->success('Updated', 'Form field updated successfully.')->send();
        } else {
            // Create new field in database
            $maxSortOrder = PartnershipFormField::where(function($query) {
                    $query->where('chapter_id', $this->chapterId)
                          ->orWhereNull('chapter_id');
                })
                ->max('sort_order') ?? 0;

            $newField = PartnershipFormField::create([
                'chapter_id' => $this->chapterId,
                'label' => $validated['fieldLabel'],
                'name' => $validated['fieldName'],
                'type' => $validated['fieldType'],
                'options' => $optionsArray,
                'description' => $validated['fieldDescription'],
                'placeholder' => $validated['fieldPlaceholder'],
                'is_required' => $this->fieldRequired,
                'is_active' => true,
                'sort_order' => $maxSortOrder + 1,
            ]);

            // Add to array
            $this->formFields[] = [
                'id' => $newField->id,
                'label' => $newField->label,
                'name' => $newField->name,
                'type' => $newField->type,
                'options' => $optionsArray ?? [],
                'description' => $newField->description,
                'placeholder' => $newField->placeholder,
                'required' => $newField->is_required,
            ];

            $this->toast()->success('Created', 'Form field created successfully.')->send();
        }

        $this->showFieldModal = false;
        $this->resetFieldForm();
    }

    public function deleteField($index): void
    {
        if (!isset($this->formFields[$index])) {
            return;
        }

        $fieldId = $this->formFields[$index]['id'] ?? null;
        
        if ($fieldId) {
            $field = PartnershipFormField::find($fieldId);
            if ($field) {
                $field->delete();
            }
        }

        array_splice($this->formFields, $index, 1);
        $this->toast()->info('Deleted', 'Form field has been deleted.')->send();
    }

    public function moveFieldUp($index): void
    {
        if ($index > 0) {
            $temp = $this->formFields[$index];
            $this->formFields[$index] = $this->formFields[$index - 1];
            $this->formFields[$index - 1] = $temp;
            
            // Update sort order in database
            $this->updateSortOrders();
        }
    }

    public function moveFieldDown($index): void
    {
        if ($index < count($this->formFields) - 1) {
            $temp = $this->formFields[$index];
            $this->formFields[$index] = $this->formFields[$index + 1];
            $this->formFields[$index + 1] = $temp;
            
            // Update sort order in database
            $this->updateSortOrders();
        }
    }

    private function updateSortOrders(): void
    {
        foreach ($this->formFields as $index => $field) {
            if (isset($field['id'])) {
                PartnershipFormField::where('id', $field['id'])->update(['sort_order' => $index + 1]);
            }
        }
    }

    public function saveFormSchema(): void
    {
        $this->toast()->success('Saved', 'Partnership form has been saved successfully.')->send();
    }

    public function resetFieldForm(): void
    {
        $this->editingIndex = null;
        $this->fieldType = 'text';
        $this->fieldLabel = '';
        $this->fieldName = '';
        $this->fieldPlaceholder = '';
        $this->fieldRequired = false;
        $this->fieldOptions = '';
        $this->fieldDescription = '';
    }

    public function closeModal(): void
    {
        $this->showFieldModal = false;
        $this->resetFieldForm();
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">Partnership Form Builder</h1>
                <p class="text-zinc-600 mt-1">
                    Chapter: <span class="font-semibold">{{ $chapterName }}</span>
                </p>
            </div>
            <div class="flex space-x-3">
                <x-button wire:click="openFieldModal" color="primary" icon="plus" label="Add Field" />
                <x-button wire:click="saveFormSchema" color="success" icon="check" label="Save Form" />
            </div>
        </div>
    </div>

    <!-- Form Preview -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Fields List -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Form Fields</h2>

                @if(empty($this->formFields))
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-zinc-500">No form fields yet. Click "Add Field" to get started.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($this->formFields as $index => $field)
                            <div class="border border-zinc-300 rounded-lg p-4 bg-zinc-50">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2">
                                            <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800">
                                                {{ $this->availableFieldTypes[$field['type']] }}
                                            </span>
                                            @if($field['required'] ?? false)
                                                <span class="px-2 py-1 text-xs font-semibold rounded bg-red-100 text-red-800">
                                                    Required
                                                </span>
                                            @endif
                                        </div>
                                        <h3 class="font-medium mt-2">{{ $field['label'] }}</h3>
                                        <p class="text-sm text-zinc-600">Field name: {{ $field['name'] }}</p>
                                        @if(!empty($field['description']))
                                            <p class="text-sm text-zinc-500 mt-1">{{ $field['description'] }}</p>
                                        @endif
                                        @if(isset($field['options']) && !empty($field['options']))
                                            <p class="text-xs text-zinc-500 mt-2">
                                                Options: {{ implode(', ', $field['options']) }}
                                            </p>
                                        @endif
                                    </div>

                                    <div class="flex space-x-2 ml-4">
                                        <!-- Move Up/Down -->
                                        <button
                                            wire:click="moveFieldUp({{ $index }})"
                                            class="p-2 text-zinc-600 hover:text-zinc-900"
                                            @if($index === 0) disabled style="opacity: 0.3;" @endif
                                        >
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                            </svg>
                                        </button>
                                        <button
                                            wire:click="moveFieldDown({{ $index }})"
                                            class="p-2 text-zinc-600 hover:text-zinc-900"
                                            @if($index === count($this->formFields) - 1) disabled style="opacity: 0.3;" @endif
                                        >
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>

                                        <!-- Edit -->
                                        <x-button.circle wire:click="editField({{ $index }})" color="primary" icon="pencil" sm />

                                        <!-- Delete -->
                                        <x-button.circle wire:click="deleteField({{ $index }})" color="red" icon="trash" sm />
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Live Preview -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-6 sticky top-6">
                <h2 class="text-lg font-semibold mb-4">Form Preview</h2>
                <div class="space-y-4 max-h-[600px] overflow-y-auto">
                    @foreach($this->formFields as $field)
                        <div>
                            <label class="block text-sm font-medium text-zinc-900">
                                {{ $field['label'] }}
                                @if($field['required'] ?? false)
                                    <span class="text-red-500">*</span>
                                @endif
                            </label>
                            @if(!empty($field['description']))
                                <p class="text-xs text-zinc-500 mb-2">{{ $field['description'] }}</p>
                            @endif

                            @switch($field['type'])
                                @case('textarea')
                                    <textarea disabled placeholder="{{ $field['placeholder'] ?? '' }}" class="w-full px-3 py-2 rounded-lg bg-zinc-100 border border-zinc-300 text-sm" rows="3"></textarea>
                                    @break

                                @case('select')
                                    <select disabled class="w-full px-3 py-2 rounded-lg bg-zinc-100 border border-zinc-300 text-sm">
                                        <option>Select...</option>
                                        @if(isset($field['options']))
                                            @foreach($field['options'] as $option)
                                                <option>{{ $option }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @break

                                @case('checkbox')
                                    <label class="flex items-center">
                                        <input type="checkbox" disabled class="mr-2">
                                        <span class="text-sm">{{ $field['placeholder'] ?? 'Check this box' }}</span>
                                    </label>
                                    @break

                                @default
                                    <input type="{{ $field['type'] }}" disabled placeholder="{{ $field['placeholder'] ?? '' }}" class="w-full px-3 py-2 rounded-lg bg-zinc-100 border border-zinc-300 text-sm" />
                            @endswitch
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Field Modal -->
    @if($showFieldModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data>
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-zinc-500 bg-opacity-75" wire:click="closeModal"></div>

                <div class="relative bg-white rounded-lg max-w-2xl w-full p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold">
                            {{ $this->editingIndex !== null ? 'Edit Field' : 'Add New Field' }}
                        </h3>
                        <button wire:click="closeModal" class="text-zinc-400 hover:text-zinc-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form wire:submit.prevent="saveField" class="space-y-4">
                        <x-select label="Field Type" wire:model.live="fieldType">
                            @foreach($this->availableFieldTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-select>

                        <div class="grid grid-cols-2 gap-4">
                            <x-input label="Field Label" wire:model="fieldLabel" placeholder="e.g., Organization Name" required />
                            <x-input label="Field Name (identifier)" wire:model="fieldName" placeholder="e.g., organization_name" required />
                        </div>

                        <x-input label="Placeholder" wire:model="fieldPlaceholder" placeholder="Hint text for the user" />

                        <x-textarea label="Description (optional)" wire:model="fieldDescription" placeholder="Additional help text" rows="2" />

                        @if(in_array($fieldType, ['select', 'checkbox']))
                            <div>
                                <x-textarea
                                    label="Options (one per line)"
                                    wire:model="fieldOptions"
                                    placeholder="Option 1&#10;Option 2&#10;Option 3"
                                    rows="5"
                                />
                                <p class="text-xs text-zinc-500 mt-1">Enter each option on a new line</p>
                            </div>
                        @endif

                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="fieldRequired" class="mr-2">
                                <span class="text-sm font-medium">Required field</span>
                            </label>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <x-button type="button" wire:click="closeModal" color="secondary" label="Cancel" />
                            <x-button type="submit" color="primary" label="Save Field" />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
