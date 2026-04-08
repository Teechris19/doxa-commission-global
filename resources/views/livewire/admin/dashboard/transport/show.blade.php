<?php

use App\Models\Transport;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions;

    public ?Transport $transport = null;
    public string $notes = '';
    public string $status = '';

    public function mount($id)
    {
        $this->transport = Transport::with('pickupLocation')->findOrFail($id);
        $this->notes = $this->transport->notes ?? '';
        $this->status = $this->transport->status;
    }

    public function updateStatus()
    {
        $this->validate([
            'status' => 'required|in:pending,approved,rejected',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->transport->update([
            'status' => $this->status,
            'notes' => $this->notes,
            'processed_at' => $this->status === 'pending' ? null : now(),
        ]);

        $this->toast()->success('Success', 'Transport request updated successfully')->send();
    }
}; ?>

<div>
    <!-- Back Button -->
    <div class="mb-6">
        <a href="{{ route('admin.dashboard.transport.index', request()->query()) }}"
            class="inline-flex items-center gap-2 rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to List
        </a>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Content -->
        <div class="lg:col-span-2">
            <!-- Requestor Information -->
            <x-card class="mb-6">
                <div class="mb-4 border-b border-gray-200 pb-4 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Requestor Information</h3>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400">Full Name</label>
                            <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white">{{ $transport->name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400">Phone Number</label>
                            <a href="tel:{{ $transport->phone }}"
                                class="mt-1 text-base font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                                {{ $transport->phone }}
                            </a>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400">Pickup Location</label>
                        <p class="mt-1 text-base text-gray-900 dark:text-white">{{ $transport->pickup_location }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400">Pickup Time</label>
                        <p class="mt-1 text-base text-gray-900 dark:text-white">{{ $transport->pickup_time ? \Carbon\Carbon::parse($transport->pickup_time)->format('g:i A') : 'Not set' }}</p>
                    </div>
                </div>
            </x-card>

            <!-- Status & Processing -->
            <x-card>
                <div class="mb-4 border-b border-gray-200 pb-4 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Status & Processing</h3>
                </div>

                <form wire:submit.prevent="updateStatus" class="space-y-4">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select id="status" wire:model="status"
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                        <textarea id="notes" wire:model="notes" rows="4" placeholder="Add any notes or comments..."
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400"></textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="submit"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove>
                                <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Update Status
                            </span>
                            <span wire:loading>
                                <x-spinner-loader size="sm" color="white"></x-spinner-loader>
                                Updating...
                            </span>
                        </button>
                        <a href="{{ route('admin.dashboard.transport.index', request()->query()) }}"
                            class="inline-flex items-center rounded-lg bg-gray-300 px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-400 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                            Back to list
                        </a>
                    </div>
                </form>
            </x-card>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <!-- Timeline -->
            <x-card class="mb-6">
                <div class="mb-4 border-b border-gray-200 pb-4 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Timeline</h3>
                </div>

                <div class="space-y-4">
                    <!-- Submitted -->
                    <div class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <div class="h-4 w-4 rounded-full bg-indigo-600 dark:bg-indigo-400"></div>
                            @if($transport->processed_at)
                                <div class="mt-2 h-6 w-1 bg-gray-300 dark:bg-gray-600"></div>
                            @endif
                        </div>
                        <div class="flex-1 pt-1">
                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-400">SUBMITTED</p>
                            <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $transport->created_at->format('M d, Y H:i') }}
                            </p>
                        </div>
                    </div>

                    <!-- Processed -->
                    @if($transport->processed_at)
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="h-4 w-4 rounded-full {{ $transport->status === 'approved' ? 'bg-green-600 dark:bg-green-400' : 'bg-red-600 dark:bg-red-400' }}"></div>
                            </div>
                            <div class="flex-1 pt-1">
                                <p class="text-xs font-semibold {{ $transport->status === 'approved' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ strtoupper($transport->status) }}
                                </p>
                                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $transport->processed_at->format('M d, Y H:i') }}
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </x-card>

            <!-- Status Badge -->
            <x-card>
                <div class="text-center">
                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-400">CURRENT STATUS</p>
                    <div class="mt-3">
                        @if($transport->status === 'pending')
                            <span class="inline-flex items-center rounded-full bg-yellow-100 px-4 py-2 text-sm font-semibold text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                <span class="h-2 w-2 rounded-full bg-yellow-500 mr-2"></span>
                                Pending
                            </span>
                        @elseif($transport->status === 'approved')
                            <span class="inline-flex items-center rounded-full bg-green-100 px-4 py-2 text-sm font-semibold text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                <span class="h-2 w-2 rounded-full bg-green-500 mr-2"></span>
                                Approved
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-red-100 px-4 py-2 text-sm font-semibold text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                <span class="h-2 w-2 rounded-full bg-red-500 mr-2"></span>
                                Rejected
                            </span>
                        @endif
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</div>
