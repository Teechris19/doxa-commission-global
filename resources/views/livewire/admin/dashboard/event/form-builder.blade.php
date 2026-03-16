<?php

use App\Models\Events;
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.admin')] class extends Component
{
    use Interactions;

    #[Url]
    public $event_id;

    public $event;
    public $formFields = [];
    public $showFieldModal = false;
    public $editingIndex = null;

    // Field being edited/created
    public $fieldType = 'text';
    public $fieldLabel = '';
    public $fieldName = '';
    public $fieldPlaceholder = '';
    public $fieldRequired = false;
    public $fieldOptions = ''; // for select, radio, checkbox
    public $fieldDescription = '';

    // Available field types
    public $availableFieldTypes = [
        'text' => 'Short Text',
        'textarea' => 'Long Text',
        'email' => 'Email',
        'phone' => 'Phone Number',
        'number' => 'Number',
        'date' => 'Date',
        'time' => 'Time',
        'select' => 'Dropdown',
        'radio' => 'Multiple Choice',
        'checkbox' => 'Checkboxes',
        'file' => 'File Upload',
    ];

    public function mount()
    {
        if (!$this->event_id) {
            abort(404, 'Event not found');
        }

        $this->event = Events::findOrFail($this->event_id);

        // Check permissions
        if (!Auth::user()->hasRole(['admin', 'super-admin'])) {
            abort(403, 'Unauthorized');
        }

        // Load existing form schema
        if ($this->event->form_schema) {
            $this->formFields = $this->event->form_schema;
        } else {
            // Default fields
            $this->formFields = [
                [
                    'type' => 'text',
                    'label' => 'Full Name',
                    'name' => 'name',
                    'placeholder' => 'Enter your full name',
                    'required' => true,
                    'description' => ''
                ],
                [
                    'type' => 'email',
                    'label' => 'Email Address',
                    'name' => 'email',
                    'placeholder' => 'Enter your email',
                    'required' => true,
                    'description' => ''
                ],
                [
                    'type' => 'phone',
                    'label' => 'Phone Number',
                    'name' => 'phone',
                    'placeholder' => 'Enter your phone number',
                    'required' => false,
                    'description' => ''
                ]
            ];
        }
    }

    public function openFieldModal()
    {
        $this->resetFieldForm();
        $this->showFieldModal = true;
    }

    public function editField($index)
    {
        $field = $this->formFields[$index];
        $this->editingIndex = $index;
        $this->fieldType = $field['type'];
        $this->fieldLabel = $field['label'];
        $this->fieldName = $field['name'];
        $this->fieldPlaceholder = $field['placeholder'] ?? '';
        $this->fieldRequired = $field['required'] ?? false;
        $this->fieldDescription = $field['description'] ?? '';

        if (isset($field['options']) && is_array($field['options'])) {
            $this->fieldOptions = implode("\n", $field['options']);
        }

        $this->showFieldModal = true;
    }

    public function saveField()
    {
        $this->validate([
            'fieldLabel' => 'required|string|max:255',
            'fieldName' => 'required|string|max:255',
        ]);

        $field = [
            'type' => $this->fieldType,
            'label' => $this->fieldLabel,
            'name' => $this->fieldName,
            'placeholder' => $this->fieldPlaceholder,
            'required' => $this->fieldRequired,
            'description' => $this->fieldDescription,
        ];

        // Add options for select, radio, checkbox
        if (in_array($this->fieldType, ['select', 'radio', 'checkbox'])) {
            $options = array_filter(array_map('trim', explode("\n", $this->fieldOptions)));
            $field['options'] = $options;
        }

        if ($this->editingIndex !== null) {
            $this->formFields[$this->editingIndex] = $field;
        } else {
            $this->formFields[] = $field;
        }

        $this->showFieldModal = false;
        $this->resetFieldForm();

        $this->toast()->success('Field saved', 'Form field has been updated')->send();
    }

    public function deleteField($index)
    {
        array_splice($this->formFields, $index, 1);
        $this->toast()->info('Field removed', 'Form field has been deleted')->send();
    }

    public function moveFieldUp($index)
    {
        if ($index > 0) {
            $temp = $this->formFields[$index];
            $this->formFields[$index] = $this->formFields[$index - 1];
            $this->formFields[$index - 1] = $temp;
        }
    }

    public function moveFieldDown($index)
    {
        if ($index < count($this->formFields) - 1) {
            $temp = $this->formFields[$index];
            $this->formFields[$index] = $this->formFields[$index + 1];
            $this->formFields[$index + 1] = $temp;
        }
    }

    public function saveFormSchema()
    {
        $this->event->update([
            'form_schema' => $this->formFields
        ]);

        $this->toast()->success('Form saved!', 'Registration form has been saved successfully')->send();
    }

    public function resetFieldForm()
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

    public function closeModal()
    {
        $this->showFieldModal = false;
        $this->resetFieldForm();
    }
};

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">Event Registration Form Builder</h1>
                <p class="text-zinc-600 dark:text-zinc-400 mt-1">
                    Event: <span class="font-semibold">{{ $event->title }}</span>
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
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Form Fields</h2>

                @if(empty($formFields))
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-zinc-500">No form fields yet. Click "Add Field" to get started.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($formFields as $index => $field)
                            <div class="border border-zinc-300 dark:border-zinc-600 rounded-lg p-4 bg-zinc-50 dark:bg-zinc-700">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2">
                                            <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                {{ $availableFieldTypes[$field['type']] }}
                                            </span>
                                            @if($field['required'] ?? false)
                                                <span class="px-2 py-1 text-xs font-semibold rounded bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                                    Required
                                                </span>
                                            @endif
                                        </div>
                                        <h3 class="font-medium text-zinc-900 dark:text-zinc-100 mt-2">{{ $field['label'] }}</h3>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Field name: {{ $field['name'] }}</p>
                                        @if(!empty($field['description']))
                                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ $field['description'] }}</p>
                                        @endif
                                        @if(isset($field['options']) && !empty($field['options']))
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">
                                                Options: {{ implode(', ', $field['options']) }}
                                            </p>
                                        @endif
                                    </div>

                                    <div class="flex space-x-2 ml-4">
                                        <!-- Move Up/Down -->
                                        <button
                                            wire:click="moveFieldUp({{ $index }})"
                                            class="p-2 text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                                            @if($index === 0) disabled @endif
                                        >
                                            ↑
                                        </button>
                                        <button
                                            wire:click="moveFieldDown({{ $index }})"
                                            class="p-2 text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                                            @if($index === count($formFields) - 1) disabled @endif
                                        >
                                            ↓
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
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6 sticky top-6">
                <h2 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Form Preview</h2>
                <div class="space-y-4 max-h-[600px] overflow-y-auto">
                    @foreach($formFields as $field)
                        <div>
                            <label class="block text-sm font-medium mb-1 text-zinc-900 dark:text-zinc-100">
                                {{ $field['label'] }}
                                @if($field['required'] ?? false)
                                    <span class="text-red-500">*</span>
                                @endif
                            </label>
                            @if($field['description'])
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">{{ $field['description'] }}</p>
                            @endif

                            @switch($field['type'])
                                @case('textarea')
                                    <textarea
                                        disabled
                                        placeholder="{{ $field['placeholder'] ?? '' }}"
                                        class="w-full px-3 py-2 rounded-lg bg-zinc-100 dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 text-sm"
                                        rows="3"
                                    ></textarea>
                                    @break

                                @case('select')
                                    <select disabled class="w-full px-3 py-2 rounded-lg bg-zinc-100 dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 text-sm">
                                        <option>Select an option...</option>
                                        @if(isset($field['options']))
                                            @foreach($field['options'] as $option)
                                                <option>{{ $option }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @break

                                @case('radio')
                                    <div class="space-y-2">
                                        @if(isset($field['options']))
                                            @foreach($field['options'] as $option)
                                                <label class="flex items-center">
                                                    <input type="radio" disabled class="mr-2">
                                                    <span class="text-sm">{{ $option }}</span>
                                                </label>
                                            @endforeach
                                        @endif
                                    </div>
                                    @break

                                @case('checkbox')
                                    <div class="space-y-2">
                                        @if(isset($field['options']))
                                            @foreach($field['options'] as $option)
                                                <label class="flex items-center">
                                                    <input type="checkbox" disabled class="mr-2">
                                                    <span class="text-sm">{{ $option }}</span>
                                                </label>
                                            @endforeach
                                        @endif
                                    </div>
                                    @break

                                @case('file')
                                    <input
                                        type="file"
                                        disabled
                                        class="w-full px-3 py-2 rounded-lg bg-zinc-100 dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 text-sm"
                                    />
                                    @break

                                @default
                                    <input
                                        type="{{ $field['type'] }}"
                                        disabled
                                        placeholder="{{ $field['placeholder'] ?? '' }}"
                                        class="w-full px-3 py-2 rounded-lg bg-zinc-100 dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 text-sm"
                                    />
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

                <div class="relative bg-white dark:bg-zinc-800 rounded-lg max-w-2xl w-full p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $editingIndex !== null ? 'Edit Field' : 'Add New Field' }}
                        </h3>
                        <button wire:click="closeModal" class="text-zinc-400 hover:text-zinc-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form wire:submit.prevent="saveField" class="space-y-4">
                        <x-select label="Field Type" wire:model.live="fieldType">
                            @foreach($availableFieldTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-select>

                        <x-input label="Field Label" wire:model="fieldLabel" placeholder="e.g., Full Name" required />

                        <x-input label="Field Name (identifier)" wire:model="fieldName" placeholder="e.g., full_name" required />

                        <x-input label="Placeholder" wire:model="fieldPlaceholder" placeholder="Hint text for the user" />

                        <x-textarea label="Description (optional)" wire:model="fieldDescription" placeholder="Additional help text" rows="2" />

                        @if(in_array($fieldType, ['select', 'radio', 'checkbox']))
                            <div>
                                <x-textarea
                                    label="Options (one per line)"
                                    wire:model="fieldOptions"
                                    placeholder="Option 1&#10;Option 2&#10;Option 3"
                                    rows="5"
                                />
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Enter each option on a new line</p>
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
