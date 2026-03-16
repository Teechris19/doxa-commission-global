<?php

use App\Models\EventForm;
use App\Models\Events;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component
{
    use Interactions, WithPagination;

    #[Url]
    public $event_id;

    public $event;

    public $selectedRegistration = null;

    public $showDetailsModal = false;

    public $searchTerm = '';

    public $filterStatus = '';

    public function mount()
    {
        if (! $this->event_id) {
            abort(404, 'Event not found');
        }

        $this->event = Events::with('chapter')->findOrFail($this->event_id);

        // Check permissions
        if (! Auth::user()->hasRole(['admin', 'super-admin'])) {
            abort(403, 'Unauthorized');
        }
    }

    public function getRegistrations()
    {
        $query = EventForm::where('event_id', $this->event_id)
            ->with('chapter');

        // Search
        if ($this->searchTerm) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->searchTerm.'%')
                    ->orWhere('email', 'like', '%'.$this->searchTerm.'%')
                    ->orWhere('phone', 'like', '%'.$this->searchTerm.'%');
            });
        }

        // Filter by status
        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        return $query->latest()->paginate(20);
    }

    public function viewRegistration($id)
    {
        $this->selectedRegistration = EventForm::findOrFail($id);
        $this->showDetailsModal = true;
    }

    public function closeModal()
    {
        $this->showDetailsModal = false;
        $this->selectedRegistration = null;
    }

    public function updateStatus($id, $status)
    {
        $registration = EventForm::findOrFail($id);
        $registration->update(['status' => $status]);

        $this->toast()
            ->success('Status Updated', 'Registration status has been updated')
            ->send();
    }

    public function deleteRegistration($id)
    {
        $registration = EventForm::findOrFail($id);
        $registration->delete();

        $this->toast()
            ->success('Deleted', 'Registration has been deleted')
            ->send();
    }

    public function exportToCsv()
    {
        $registrations = EventForm::where('event_id', $this->event_id)->get();

        if ($registrations->isEmpty()) {
            $this->toast()->warning('No Data', 'No registrations to export')->send();

            return;
        }

        $filename = 'event-'.$this->event_id.'-registrations-'.now()->format('Y-m-d').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($registrations) {
            $file = fopen('php://output', 'w');

            // Headers
            $headers = ['ID', 'Name', 'Email', 'Phone', 'Status', 'Registered At'];

            // Add dynamic field headers
            if ($registrations->first()->answers) {
                foreach (array_keys($registrations->first()->answers) as $key) {
                    $headers[] = ucfirst(str_replace('_', ' ', $key));
                }
            }

            fputcsv($file, $headers);

            // Data
            foreach ($registrations as $registration) {
                $row = [
                    $registration->id,
                    $registration->name,
                    $registration->email,
                    $registration->phone,
                    $registration->status,
                    $registration->created_at->format('Y-m-d H:i:s'),
                ];

                // Add dynamic field values
                if ($registration->answers) {
                    foreach ($registration->answers as $value) {
                        if (is_array($value)) {
                            $row[] = implode(', ', $value);
                        } else {
                            $row[] = $value;
                        }
                    }
                }

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function with()
    {
        return [
            'registrations' => $this->getRegistrations(),
            'totalRegistrations' => EventForm::where('event_id', $this->event_id)->count(),
            'confirmedCount' => EventForm::where('event_id', $this->event_id)->where('status', 'confirmed')->count(),
            'pendingCount' => EventForm::where('event_id', $this->event_id)->where('status', 'pending')->count(),
        ];
    }
};

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
        <div class="flex justify-between items-start">
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">Event Registrations</h1>
                <p class="text-zinc-600 dark:text-zinc-400 mt-1">
                    Event: <span class="font-semibold">{{ $event->title }}</span>
                </p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $event->start_at->format('F j, Y \a\t g:i A') }}
                </p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('admin.dashboard.event.form-builder', ['event_id' => $event->id]) }}">
                    <x-button color="secondary" icon="pencil" label="Edit Form" />
                </a>
                <x-button wire:click="exportToCsv" color="primary" label="Export CSV" />
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $totalRegistrations }}</div>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">Total Registrations</div>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $confirmedCount }}</div>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">Confirmed</div>
            </div>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $pendingCount }}</div>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">Pending</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input
                label="Search"
                wire:model.live="searchTerm"
                placeholder="Search by name, email, or phone..."
            />

            <x-select label="Filter by Status" wire:model.live="filterStatus">
                <option value="">All Status</option>
                <option value="confirmed">Confirmed</option>
                <option value="pending">Pending</option>
                <option value="cancelled">Cancelled</option>
            </x-select>
        </div>
    </div>

    <!-- Registrations Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow">
        @if($registrations->isEmpty())
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">No Registrations</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    No one has registered for this event yet.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Registrant
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Contact
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Registered On
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($registrations as $registration)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $registration->name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                        {{ $registration->email }}
                                    </div>
                                    @if($registration->phone)
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $registration->phone }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'confirmed' => 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
                                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100',
                                            'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$registration->status] ?? 'bg-zinc-100 text-zinc-800' }}">
                                        {{ ucfirst($registration->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $registration->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <x-button
                                        wire:click="viewRegistration({{ $registration->id }})"
                                        color="primary"
                                        label="View"
                                        sm
                                    />
                                    <x-button
                                        wire:click="deleteRegistration({{ $registration->id }})"
                                        color="red"
                                        label="Delete"
                                        sm
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $registrations->links() }}
            </div>
        @endif
    </div>

    <!-- Registration Details Modal -->
    @if($showDetailsModal && $selectedRegistration)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-zinc-500 bg-opacity-75" wire:click="closeModal"></div>

                <div class="relative bg-white dark:bg-zinc-800 rounded-lg max-w-3xl w-full p-6">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">
                            Registration Details
                        </h3>
                        <button wire:click="closeModal" class="text-zinc-400 hover:text-zinc-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <!-- Basic Info -->
                        <div class="bg-zinc-50 dark:bg-zinc-900 rounded-lg p-4">
                            <h4 class="font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Basic Information</h4>
                            <dl class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">Name</dt>
                                    <dd class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $selectedRegistration->name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">Email</dt>
                                    <dd class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $selectedRegistration->email }}</dd>
                                </div>
                                @if($selectedRegistration->phone)
                                    <div>
                                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Phone</dt>
                                        <dd class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $selectedRegistration->phone }}</dd>
                                    </div>
                                @endif
                                <div>
                                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">Status</dt>
                                    <dd class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ ucfirst($selectedRegistration->status) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">Registered On</dt>
                                    <dd class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $selectedRegistration->created_at->format('M d, Y @ g:i A') }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Form Responses -->
                        @if($selectedRegistration->answers)
                            <div class="bg-zinc-50 dark:bg-zinc-900 rounded-lg p-4">
                                <h4 class="font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Form Responses</h4>
                                <dl class="space-y-3">
                                    @foreach($selectedRegistration->answers as $key => $value)
                                        <div>
                                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ ucfirst(str_replace('_', ' ', $key)) }}</dt>
                                            <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                                                @if(is_array($value))
                                                    {{ implode(', ', $value) }}
                                                @elseif(Str::startsWith($value, 'event-registrations/'))
                                                    <a href="{{ Storage::url($value) }}" target="_blank" class="text-blue-600 hover:underline">
                                                        View uploaded file
                                                    </a>
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        @endif

                        <!-- Status Update -->
                        <div class="flex justify-between items-center pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <div class="space-x-2">
                                <x-button
                                    wire:click="updateStatus({{ $selectedRegistration->id }}, 'confirmed')"
                                    color="success"
                                    label="Confirm"
                                    sm
                                />
                                <x-button
                                    wire:click="updateStatus({{ $selectedRegistration->id }}, 'pending')"
                                    color="warning"
                                    label="Set Pending"
                                    sm
                                />
                                <x-button
                                    wire:click="updateStatus({{ $selectedRegistration->id }}, 'cancelled')"
                                    color="red"
                                    label="Cancel"
                                    sm
                                />
                            </div>
                            <x-button wire:click="closeModal" color="secondary" label="Close" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
