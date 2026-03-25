<?php

use App\Models\Testimony;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions, WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $selectedTestimony = null;
    public $showViewModal = false;
    public $showEditModal = false;

    // Edit form
    public $editForm = [
        'name' => '',
        'email' => '',
        'testimony' => '',
        'status' => '',
    ];

    public function mount()
    {
        $this->resetPage();
    }

    public function testimonies()
    {
        return Testimony::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhere('testimony', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);
    }

    public function stats()
    {
        return [
            'total' => Testimony::count(),
            'pending' => Testimony::where('status', 'pending')->count(),
            'approved' => Testimony::where('status', 'approved')->count(),
            'rejected' => Testimony::where('status', 'rejected')->count(),
        ];
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function viewTestimony($id)
    {
        $this->selectedTestimony = Testimony::find($id);
        $this->showViewModal = true;
    }

    public function editTestimony($id)
    {
        $testimony = Testimony::find($id);
        $this->selectedTestimony = $testimony;
        
        $this->editForm = [
            'name' => $testimony->name,
            'email' => $testimony->email,
            'testimony' => $testimony->testimony,
            'status' => $testimony->status,
        ];
        
        $this->showEditModal = true;
    }

    public function updateTestimony()
    {
        $this->validate([
            'editForm.name' => 'required|string|max:255',
            'editForm.email' => 'required|email|max:255',
            'editForm.testimony' => 'required|string',
            'editForm.status' => 'required|in:pending,approved,rejected',
        ]);

        $this->selectedTestimony->update($this->editForm);

        $this->toast()->success('Updated', 'Testimony updated successfully.')->send();
        $this->showEditModal = false;
        $this->resetEditForm();
    }

    public function updateStatus($id, $status)
    {
        $testimony = Testimony::find($id);
        
        if ($testimony) {
            $testimony->update(['status' => $status]);
            $this->toast()->success('Updated', 'Testimony status updated to ' . ucfirst($status) . '.')->send();
        }
    }

    public function deleteTestimony($id)
    {
        $this->dialog()
            ->error('Are you sure you want to delete this testimony?')
            ->hook([
                'ok' => [
                    'method' => 'confirmDelete',
                    'params' => [$id],
                ],
            ])
            ->send();
    }

    public function confirmDelete($id)
    {
        $testimony = Testimony::find($id);
        
        if ($testimony) {
            $testimony->delete();
            $this->toast()->success('Deleted', 'Testimony deleted successfully.')->send();
        }
    }

    private function resetEditForm()
    {
        $this->editForm = [
            'name' => '',
            'email' => '',
            'testimony' => '',
            'status' => '',
        ];
        $this->selectedTestimony = null;
    }

    public function with(): array
    {
        return [
            'stats' => $this->stats(),
            'testimoniesList' => $this->testimonies(),
        ];
    }
}; ?>

<div>
    <div class="min-h-screen bg-gray-50 dark:bg-zinc-900 p-6">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Testimonies Management</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">View and manage testimonies from users</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-blue-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Total</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
                    </div>
                    <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-amber-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Pending</p>
                        <p class="mt-1 text-2xl font-bold text-amber-600">{{ $stats['pending'] }}</p>
                    </div>
                    <div class="rounded-full bg-amber-100 p-3 dark:bg-amber-900">
                        <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-emerald-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Approved</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $stats['approved'] }}</p>
                    </div>
                    <div class="rounded-full bg-emerald-100 p-3 dark:bg-emerald-900">
                        <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-red-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Rejected</p>
                        <p class="mt-1 text-2xl font-bold text-red-600">{{ $stats['rejected'] }}</p>
                    </div>
                    <div class="rounded-full bg-red-100 p-3 dark:bg-red-900">
                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                    <input 
                        type="text" 
                        wire:model.live.debounce.500ms="search" 
                        placeholder="Search testimonies..." 
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Status Filter</label>
                    <select wire:model.live="statusFilter" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button 
                        wire:click="$set('search', ''); $set('statusFilter', '')" 
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-zinc-600 dark:text-gray-300 dark:hover:bg-zinc-700"
                    >
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Testimonies Table -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                    <thead class="bg-gray-50 dark:bg-zinc-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Testimony</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                        @forelse($testimoniesList as $testimony)
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $testimony->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $testimony->email }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate">{{ Str::limit($testimony->testimony, 80) }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold 
                                        @if($testimony->status === 'approved') bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-400
                                        @elseif($testimony->status === 'pending') bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-400
                                        @else bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-400
                                        @endif
                                    ">
                                        {{ ucfirst($testimony->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $testimony->created_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button 
                                            wire:click="viewTestimony({{ $testimony->id }})" 
                                            class="rounded-lg p-2 text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-zinc-600"
                                            title="View"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                        <button 
                                            wire:click="editTestimony({{ $testimony->id }})" 
                                            class="rounded-lg p-2 text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-zinc-600"
                                            title="Edit"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <button 
                                            wire:click="deleteTestimony({{ $testimony->id }})" 
                                            class="rounded-lg p-2 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-zinc-600"
                                            title="Delete"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">No testimonies found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $testimoniesList->links() }}
        </div>
    </div>

    <!-- View Modal -->
    @if($showViewModal && $selectedTestimony)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" wire:click="$set('showViewModal', false)">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6" wire:click.stop>
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900">Testimony Details</h3>
                    <button wire:click="$set('showViewModal', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-medium uppercase text-gray-500">Name</label>
                        <p class="text-sm font-medium text-gray-900">{{ $selectedTestimony->name }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium uppercase text-gray-500">Email</label>
                        <p class="text-sm font-medium text-gray-900">{{ $selectedTestimony->email }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium uppercase text-gray-500">Status</label>
                        <p>
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold 
                                @if($selectedTestimony->status === 'approved') bg-emerald-100 text-emerald-700
                                @elseif($selectedTestimony->status === 'pending') bg-amber-100 text-amber-700
                                @else bg-red-100 text-red-700
                                @endif
                            ">
                                {{ ucfirst($selectedTestimony->status) }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <label class="text-xs font-medium uppercase text-gray-500">Testimony</label>
                        <p class="whitespace-pre-wrap text-sm text-gray-900">{{ $selectedTestimony->testimony }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium uppercase text-gray-500">Submitted On</label>
                        <p class="text-sm text-gray-900">{{ $selectedTestimony->created_at->format('F d, Y h:i A') }}</p>
                    </div>
                </div>

                <div class="mt-6 flex gap-2">
                    @if($selectedTestimony->status !== 'approved')
                        <button 
                            wire:click="updateStatus({{ $selectedTestimony->id }}, 'approved'); $set('showViewModal', false)" 
                            class="flex-1 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                        >
                            Approve
                        </button>
                    @endif
                    @if($selectedTestimony->status !== 'rejected')
                        <button 
                            wire:click="updateStatus({{ $selectedTestimony->id }}, 'rejected'); $set('showViewModal', false)" 
                            class="flex-1 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
                        >
                            Reject
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Modal -->
    @if($showEditModal && $selectedTestimony)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" wire:click="$set('showEditModal', false)">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6" wire:click.stop>
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900">Edit Testimony</h3>
                    <button wire:click="$set('showEditModal', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="updateTestimony" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" wire:model="editForm.name" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                        @error('editForm.name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" wire:model="editForm.email" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                        @error('editForm.email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Testimony</label>
                        <textarea wire:model="editForm.testimony" rows="5" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></textarea>
                        @error('editForm.testimony') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select wire:model="editForm.status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        @error('editForm.status') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <button type="button" wire:click="$set('showEditModal', false)" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
