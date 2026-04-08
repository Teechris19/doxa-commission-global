<?php

use App\Models\EventForm;
use App\Models\Events;
use App\Notifications\EventRegistered;
use App\Services\NotificationRecipients;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.tailwind-layout')] class extends Component
{
    use Interactions, WithFileUploads;

    #[Url]
    public $event_id;

    public $event;

    public $formData = [];

    public $uploadedFiles = [];

    public function mount()
    {
        if (! $this->event_id) {
            abort(404, 'Event not found');
        }

        $this->event = Events::findOrFail($this->event_id);

        if (! $this->event->registration_required) {
            abort(403, 'This event does not require registration');
        }

        if (! $this->event->isRegistrationOpen()) {
            if ($this->event->status !== 'published') {
                abort(403, 'This event is not currently published');
            }

            if ($this->event->hasStarted()) {
                abort(403, 'Registration for this event has closed. The event has already started.');
            }
        }

        if ($this->event->isAtCapacity()) {
            abort(403, 'This event has reached maximum capacity. No more registrations can be accepted.');
        }
    }

    public function submit()
    {
        $rules = [];
        $messages = [];

        if ($this->event->form_schema) {
            foreach ($this->event->form_schema as $field) {
                $fieldRules = [];

                if ($field['required'] ?? false) {
                    $fieldRules[] = 'required';
                } else {
                    $fieldRules[] = 'nullable';
                }

                switch ($field['type']) {
                    case 'email':
                        $fieldRules[] = 'email';
                        break;
                    case 'number':
                        $fieldRules[] = 'numeric';
                        break;
                    case 'date':
                        $fieldRules[] = 'date';
                        break;
                    case 'file':
                        $fieldRules[] = 'file';
                        $fieldRules[] = 'max:10240';
                        break;
                    case 'phone':
                        $fieldRules[] = 'string';
                        $fieldRules[] = 'max:20';
                        break;
                    default:
                        $fieldRules[] = 'string';
                        $fieldRules[] = 'max:500';
                }

                $rules['formData.'.$field['name']] = implode('|', $fieldRules);
                $messages['formData.'.$field['name'].'.required'] = 'The '.strtolower($field['label']).' field is required.';
            }
        }

        $this->validate($rules, $messages);

        try {
            $processedData = [];
            foreach ($this->formData as $key => $value) {
                if ($value instanceof \Illuminate\Http\UploadedFile) {
                    $path = $value->store('event-registrations', 'public');
                    $processedData[$key] = $path;
                } else {
                    $processedData[$key] = $value;
                }
            }

            $name = $processedData['name'] ?? $processedData['full_name'] ?? 'Unknown';
            $email = $processedData['email'] ?? null;
            $phone = $processedData['phone'] ?? null;

            EventForm::create([
                'event_id' => $this->event_id,
                'chapter_id' => $this->event->chapter_id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'guests' => 0,
                'form' => $this->event->form_schema,
                'answers' => $processedData,
                'status' => 'confirmed',
            ]);

            $user = auth()->user();
            if ($user && $user->account) {
                $existingRegistration = \App\Models\AccountEvent::where('account_id', $user->account->id)
                    ->where('event_id', $this->event_id)
                    ->first();

                if (!$existingRegistration) {
                    \App\Models\AccountEvent::create([
                        'account_id' => $user->account->id,
                        'event_id' => $this->event_id,
                        'registered_at' => now(),
                        'status' => 'registered',
                    ]);
                }

                $recipients = (new NotificationRecipients())
                    ->forFunctionAndChapter('events', $this->event->chapter_id);

                foreach ($recipients as $recipient) {
                    $recipient->notify(new EventRegistered($this->event, $user));
                }
            }

            session()->flash('success', 'Registration successful! We look forward to seeing you at the event.');

            $this->reset(['formData', 'uploadedFiles']);

            $this->toast()
                ->success('Registration Successful!', 'You have been registered for '.$this->event->title)
                ->send();

            return redirect()->route('home');

        } catch (\Exception $e) {
            \Log::error('Event registration failed', [
                'event_id' => $this->event_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->toast()
                ->error('Registration Failed', $e->getMessage())
                ->send();
        }
    }
};

?>

<div class="bg-white py-10 sm:py-14">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <article class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-sm">
            @if($event->banner)
                <img src="{{ Storage::url($event->banner) }}" alt="{{ $event->title }}" class="h-60 w-full object-cover sm:h-72">
            @endif

            <div class="space-y-5 p-6 sm:p-8">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">Event Registration</p>
                <h1 class="text-3xl font-semibold text-slate-900">{{ $event->title }}</h1>

                @if($event->description)
                    <p class="text-sm leading-7 text-slate-600">{{ $event->description }}</p>
                @endif

                <div class="grid gap-3 text-sm text-slate-600 sm:grid-cols-2">
                    <p><span class="font-semibold text-slate-800">Date:</span> {{ $event->start_at->format('F j, Y') }}</p>
                    <p>
                        <span class="font-semibold text-slate-800">Time:</span>
                        {{ $event->start_at->format('g:i A') }}
                        @if($event->end_at)
                            - {{ $event->end_at->format('g:i A') }}
                        @endif
                        @if($event->timezone)
                            ({{ $event->timezone }})
                        @endif
                    </p>
                    @if($event->location)
                        <p><span class="font-semibold text-slate-800">Location:</span> {{ $event->location }}</p>
                    @endif
                    @if($event->chapter)
                        <p><span class="font-semibold text-slate-800">Chapter:</span> {{ $event->chapter->name }}</p>
                    @endif
                    @if($event->is_online)
                        <p class="sm:col-span-2"><span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">Online Event</span></p>
                    @endif
                </div>
            </div>
        </article>

        <section class="mt-6 rounded-3xl border border-blue-100 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="text-2xl font-semibold text-slate-900">Complete Your Registration</h2>

            @if(session('success'))
                <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            <form wire:submit.prevent="submit" class="mt-6 space-y-5">
                @if($event->form_schema)
                    @foreach($event->form_schema as $field)
                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                {{ $field['label'] }}
                                @if($field['required'] ?? false)
                                    <span class="text-rose-600">*</span>
                                @endif
                            </label>

                            @if($field['description'] ?? false)
                                <p class="mt-1 text-xs text-slate-500">{{ $field['description'] }}</p>
                            @endif

                            @switch($field['type'])
                                @case('textarea')
                                    <textarea
                                        wire:model="formData.{{ $field['name'] }}"
                                        placeholder="{{ $field['placeholder'] ?? '' }}"
                                        rows="4"
                                        class="mt-2 w-full rounded-2xl border border-blue-100 px-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                    ></textarea>
                                    @break

                                @case('select')
                                    <select
                                        wire:model="formData.{{ $field['name'] }}"
                                        class="mt-2 w-full rounded-2xl border border-blue-100 px-4 py-3 text-sm text-slate-700 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                    >
                                        <option value="">Select an option...</option>
                                        @if(isset($field['options']))
                                            @foreach($field['options'] as $option)
                                                <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @break

                                @case('radio')
                                    <div class="mt-3 space-y-2">
                                        @if(isset($field['options']))
                                            @foreach($field['options'] as $option)
                                                <label for="radio_{{ $field['name'] }}_{{ $loop->index }}" class="flex items-center gap-2 text-sm text-slate-700">
                                                    <input
                                                        id="radio_{{ $field['name'] }}_{{ $loop->index }}"
                                                        type="radio"
                                                        wire:model="formData.{{ $field['name'] }}"
                                                        value="{{ $option }}"
                                                        class="h-4 w-4 border-blue-200 text-blue-600 focus:ring-blue-200"
                                                    >
                                                    <span>{{ $option }}</span>
                                                </label>
                                            @endforeach
                                        @endif
                                    </div>
                                    @break

                                @case('checkbox')
                                    <div class="mt-3 space-y-2">
                                        @if(isset($field['options']))
                                            @foreach($field['options'] as $option)
                                                <label for="checkbox_{{ $field['name'] }}_{{ $loop->index }}" class="flex items-center gap-2 text-sm text-slate-700">
                                                    <input
                                                        id="checkbox_{{ $field['name'] }}_{{ $loop->index }}"
                                                        type="checkbox"
                                                        wire:model="formData.{{ $field['name'] }}.{{ $loop->index }}"
                                                        value="{{ $option }}"
                                                        class="h-4 w-4 rounded border-blue-200 text-blue-600 focus:ring-blue-200"
                                                    >
                                                    <span>{{ $option }}</span>
                                                </label>
                                            @endforeach
                                        @endif
                                    </div>
                                    @break

                                @case('file')
                                    <input
                                        type="file"
                                        wire:model="formData.{{ $field['name'] }}"
                                        class="mt-2 block w-full rounded-2xl border border-blue-100 px-4 py-2.5 text-sm text-slate-700 file:mr-4 file:rounded-full file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                    >
                                    <p class="mt-1 text-xs text-slate-500">Maximum file size: 10MB</p>
                                    @break

                                @default
                                    <input
                                        type="{{ $field['type'] }}"
                                        wire:model="formData.{{ $field['name'] }}"
                                        placeholder="{{ $field['placeholder'] ?? '' }}"
                                        class="mt-2 w-full rounded-2xl border border-blue-100 px-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                    >
                            @endswitch

                            @error('formData.' . $field['name'])
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                @else
                    <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                        No registration form has been configured for this event.
                    </div>
                @endif

                @if($event->form_schema)
                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700"
                    >
                        Complete Registration
                    </button>
                @endif
            </form>

            @if($event->hasStarted())
                <div class="mt-6 rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                    Event underway: photos may be available in the event gallery.
                </div>
            @endif

            <div class="mt-6 text-center">
                <a href="{{ route('home') }}" wire:navigate class="text-sm font-medium text-blue-700 transition hover:text-blue-800">Back to Home</a>
            </div>
        </section>
    </div>
</div>
