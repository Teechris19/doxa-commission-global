<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Layout, Computed};
use App\Models\Partnership;
use App\Models\User;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithPagination;
    
    public $search = '';
    public $statusFilter = '';
    public $locationFilter = '';
    public $typeFilter = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $selectedPartnerships = [];
    public $showFilters = false;
    
    // Modal properties
    public $showDetailModal = false;
    public $showEditModal = false;
    public $selectedPartnership = null;
    
    // Edit form properties
    public $editForm = [
        'status' => '',
        'notes' => '',
        'assigned_to' => '',
        'partnership_type' => '',
        'proposed_amount' => '',
        'start_date' => '',
        'end_date' => '',
    ];
    
    public function mount()
    {
        $this->resetPage();
    }
    
    #[Computed]
    public function partnerships()
    {
        return Partnership::query()
            ->with(['assignedTo', 'reviewedBy'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhere('organization', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->locationFilter, fn($q) => $q->where('preferred_location', $this->locationFilter))
            ->when($this->typeFilter, fn($q) => $q->where('partnership_type', $this->typeFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);
    }
    
    #[Computed]
    public function stats()
    {
        return [
            'total' => Partnership::count(),
            'pending' => Partnership::where('status', 'pending')->count(),
            'under_review' => Partnership::where('status', 'under_review')->count(),
            'approved' => Partnership::where('status', 'approved')->count(),
            'active' => Partnership::where('status', 'active')->count(),
        ];
    }
    
    #[Computed]
    public function users()
    {
        return User::select('id', 'name')->orderBy('name')->get();
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
    
    public function viewPartnership($id)
    {
        $this->selectedPartnership = Partnership::with(['assignedTo', 'reviewedBy'])->find($id);
        $this->showDetailModal = true;
    }
    
    public function editPartnership($id)
    {
        $partnership = Partnership::find($id);
        $this->selectedPartnership = $partnership;
        
        $this->editForm = [
            'status' => $partnership->status,
            'notes' => $partnership->notes,
            'assigned_to' => $partnership->assigned_to,
            'partnership_type' => $partnership->partnership_type,
            'proposed_amount' => $partnership->proposed_amount,
            'start_date' => $partnership->start_date?->format('Y-m-d'),
            'end_date' => $partnership->end_date?->format('Y-m-d'),
        ];
        
        $this->showEditModal = true;
    }
    
    public function updatePartnership()
    {
        $this->validate([
            'editForm.status' => 'required|in:pending,under_review,approved,rejected,active',
            'editForm.notes' => 'nullable|string|max:1000',
            'editForm.assigned_to' => 'nullable|exists:users,id',
            'editForm.partnership_type' => 'nullable|in:financial,strategic,ministry,technology,other',
            'editForm.proposed_amount' => 'nullable|numeric|min:0',
            'editForm.start_date' => 'nullable|date',
            'editForm.end_date' => 'nullable|date|after_or_equal:editForm.start_date',
        ]);
        
        $updateData = array_filter($this->editForm, fn($value) => $value !== '');
        
        if (in_array($this->editForm['status'], ['approved', 'rejected', 'active'])) {
            $updateData['reviewed_at'] = now();
            $updateData['reviewed_by'] = auth()->id();
        }
        
        $this->selectedPartnership->update($updateData);
        
        $this->showEditModal = false;
        $this->dispatch('partnership-updated');
        session()->flash('success', 'Partnership updated successfully!');
    }
    
    public function bulkUpdateStatus($status)
    {
        if (empty($this->selectedPartnerships)) {
            session()->flash('error', 'Please select partnerships to update.');
            return;
        }
        
        Partnership::whereIn('id', $this->selectedPartnerships)
            ->update([
                'status' => $status,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);
        
        $this->selectedPartnerships = [];
        session()->flash('success', count($this->selectedPartnerships) . ' partnerships updated successfully!');
    }
    
    public function deletePartnership($id)
    {
        Partnership::find($id)->delete();
        session()->flash('success', 'Partnership deleted successfully!');
    }
    
    public function exportPartnerships()
    {
        // This would typically export to CSV or Excel
        session()->flash('info', 'Export functionality would be implemented here.');
    }
}; ?>

<div>
    <div class="container-fluid px-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center py-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800">Partnership Management</h1>
                <p class="mb-0 text-muted">Manage partnership applications and relationships</p>
            </div>
            <div class="d-flex gap-2">
                <button wire:click="exportPartnerships" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-download me-2"></i>Export
                </button>
                <button wire:click="$toggle('showFilters')" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-filter me-2"></i>Filters
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $this->stats['total'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-handshake fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $this->stats['pending'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Under Review</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $this->stats['under_review'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-search fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Approved</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $this->stats['approved'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Active</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $this->stats['active'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-star fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters (Collapsible) -->
        @if($showFilters)
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select wire:model.live="statusFilter" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="under_review">Under Review</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="active">Active</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Location</label>
                        <select wire:model.live="locationFilter" class="form-select form-select-sm">
                            <option value="">All Locations</option>
                            <option value="North America">North America</option>
                            <option value="Europe">Europe</option>
                            <option value="Asia-Pacific">Asia-Pacific</option>
                            <option value="Latin America">Latin America</option>
                            <option value="Middle East & Africa">Middle East & Africa</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select wire:model.live="typeFilter" class="form-select form-select-sm">
                            <option value="">All Types</option>
                            <option value="financial">Financial</option>
                            <option value="strategic">Strategic</option>
                            <option value="ministry">Ministry</option>
                            <option value="technology">Technology</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button wire:click="$set('statusFilter', ''); $set('locationFilter', ''); $set('typeFilter', '')" 
                                class="btn btn-outline-secondary btn-sm w-100">
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Search and Bulk Actions -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input wire:model.live.debounce.500ms="search" 
                                   type="text" 
                                   class="form-control" 
                                   placeholder="Search partnerships...">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        @if(!empty($selectedPartnerships))
                        <div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle btn-sm" type="button" data-bs-toggle="dropdown">
                                Bulk Actions ({{ count($selectedPartnerships) }})
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" wire:click="bulkUpdateStatus('approved')">Approve Selected</a></li>
                                <li><a class="dropdown-item" wire:click="bulkUpdateStatus('rejected')">Reject Selected</a></li>
                                <li><a class="dropdown-item" wire:click="bulkUpdateStatus('under_review')">Mark as Under Review</a></li>
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50">
                                    <input type="checkbox" class="form-check-input">
                                </th>
                                <th wire:click="sortBy('name')" class="cursor-pointer">
                                    Name
                                    @if($sortBy === 'name')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th>Contact</th>
                                <th wire:click="sortBy('preferred_location')" class="cursor-pointer">
                                    Location
                                    @if($sortBy === 'preferred_location')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th wire:click="sortBy('partnership_type')" class="cursor-pointer">
                                    Type
                                    @if($sortBy === 'partnership_type')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th wire:click="sortBy('status')" class="cursor-pointer">
                                    Status
                                    @if($sortBy === 'status')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th>Assigned To</th>
                                <th wire:click="sortBy('created_at')" class="cursor-pointer">
                                    Date
                                    @if($sortBy === 'created_at')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($this->partnerships as $partnership)
                            <tr>
                                <td>
                                    <input wire:model="selectedPartnerships" 
                                           value="{{ $partnership->id }}" 
                                           type="checkbox" 
                                           class="form-check-input">
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $partnership->name }}</div>
                                    @if($partnership->organization)
                                        <small class="text-muted">{{ $partnership->organization }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $partnership->email }}</div>
                                    @if($partnership->phone)
                                        <small class="text-muted">{{ $partnership->phone }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $partnership->preferred_location ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    @if($partnership->partnership_type)
                                        <span class="badge bg-info">{{ ucfirst($partnership->partnership_type) }}</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $partnership->status_badge }}">{{ ucfirst(str_replace('_', ' ', $partnership->status)) }}</span>
                                </td>
                                <td>
                                    @if($partnership->assignedTo)
                                        <span class="text-sm">{{ $partnership->assignedTo->name }}</span>
                                    @else
                                        <span class="text-muted">Unassigned</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $partnership->created_at->format('M d, Y') }}</small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button wire:click="viewPartnership({{ $partnership->id }})" 
                                                class="btn btn-outline-info btn-sm" 
                                                title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button wire:click="editPartnership({{ $partnership->id }})" 
                                                class="btn btn-outline-primary btn-sm" 
                                                title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button wire:click="deletePartnership({{ $partnership->id }})" 
                                                wire:confirm="Are you sure you want to delete this partnership?" 
                                                class="btn btn-outline-danger btn-sm" 
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-handshake fa-3x mb-3"></i>
                                        <p>No partnerships found</p>
                                        @if($search || $statusFilter || $locationFilter || $typeFilter)
                                            <button wire:click="$set('search', ''); $set('statusFilter', ''); $set('locationFilter', ''); $set('typeFilter', '')" 
                                                    class="btn btn-sm btn-outline-primary">
                                                Clear all filters
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            @if($this->partnerships->hasPages())
            <div class="card-footer">
                {{ $this->partnerships->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- Detail Modal -->
    @if($showDetailModal && $selectedPartnership)
    <div class="modal fade show" style="display: block;" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Partnership Details</h5>
                    <button wire:click="$set('showDetailModal', false)" type="button" class="btn-close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Contact Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Name:</strong></td><td>{{ $selectedPartnership->name }}</td></tr>
                                <tr><td><strong>Email:</strong></td><td>{{ $selectedPartnership->email }}</td></tr>
                                <tr><td><strong>Phone:</strong></td><td>{{ $selectedPartnership->phone ?? 'N/A' }}</td></tr>
                                <tr><td><strong>Organization:</strong></td><td>{{ $selectedPartnership->organization ?? 'N/A' }}</td></tr>
                                <tr><td><strong>Website:</strong></td><td>{{ $selectedPartnership->website ?? 'N/A' }}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Partnership Details</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Status:</strong></td><td><span class="badge bg-{{ $selectedPartnership->status_badge }}">{{ ucfirst(str_replace('_', ' ', $selectedPartnership->status)) }}</span></td></tr>
                                <tr><td><strong>Type:</strong></td><td>{{ $selectedPartnership->partnership_type ? ucfirst($selectedPartnership->partnership_type) : 'N/A' }}</td></tr>
                                <tr><td><strong>Location:</strong></td><td>{{ $selectedPartnership->preferred_location ?? 'N/A' }}</td></tr>
                                <tr><td><strong>Proposed Amount:</strong></td><td>{{ $selectedPartnership->proposed_amount ? '$' . number_format($selectedPartnership->proposed_amount, 2) : 'N/A' }}</td></tr>
                                <tr><td><strong>Start Date:</strong></td><td>{{ $selectedPartnership->start_date?->format('M d, Y') ?? 'N/A' }}</td></tr>
                                <tr><td><strong>End Date:</strong></td><td>{{ $selectedPartnership->end_date?->format('M d, Y') ?? 'N/A' }}</td></tr>
                            </table>
                        </div>
                    </div>
                    
                    @if($selectedPartnership->partnership_interests)
                    <div class="mt-3">
                        <h6>Partnership Interests</h6>
                        <p class="text-muted">{{ $selectedPartnership->partnership_interests }}</p>
                    </div>
                    @endif
                    
                    @if($selectedPartnership->notes)
                    <div class="mt-3">
                        <h6>Internal Notes</h6>
                        <p class="text-muted">{{ $selectedPartnership->notes }}</p>
                    </div>
                    @endif
                    
                    <div class="mt-3">
                        <h6>Management Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Assigned To:</strong></td><td>{{ $selectedPartnership->assignedTo?->name ?? 'Unassigned' }}</td></tr>
                            <tr><td><strong>Reviewed By:</strong></td><td>{{ $selectedPartnership->reviewedBy?->name ?? 'Not reviewed' }}</td></tr>
                            <tr><td><strong>Created:</strong></td><td>{{ $selectedPartnership->created_at->format('M d, Y g:i A') }}</td></tr>
                            <tr><td><strong>Last Updated:</strong></td><td>{{ $selectedPartnership->updated_at->format('M d, Y g:i A') }}</td></tr>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button wire:click="editPartnership({{ $selectedPartnership->id }})" class="btn btn-primary">Edit Partnership</button>
                    <button wire:click="$set('showDetailModal', false)" type="button" class="btn btn-secondary">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Edit Modal -->
    @if($showEditModal && $selectedPartnership)
    <div class="modal fade show" style="display: block;" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form wire:submit="updatePartnership">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Partnership - {{ $selectedPartnership->name }}</h5>
                        <button wire:click="$set('showEditModal', false)" type="button" class="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status *</label>
                                <select wire:model="editForm.status" class="form-select @error('editForm.status') is-invalid @enderror" required>
                                    <option value="">Select Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="under_review">Under Review</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="active">Active</option>
                                </select>
                                @error('editForm.status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Partnership Type</label>
                                <select wire:model="editForm.partnership_type" class="form-select @error('editForm.partnership_type') is-invalid @enderror">
                                    <option value="">Select Type</option>
                                    <option value="financial">Financial</option>
                                    <option value="strategic">Strategic</option>
                                    <option value="ministry">Ministry</option>
                                    <option value="technology">Technology</option>
                                    <option value="other">Other</option>
                                </select>
                                @error('editForm.partnership_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Assigned To</label>
                                <select wire:model="editForm.assigned_to" class="form-select @error('editForm.assigned_to') is-invalid @enderror">
                                    <option value="">Unassigned</option>
                                    @foreach($this->users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                @error('editForm.assigned_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Proposed Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input wire:model="editForm.proposed_amount" type="number" step="0.01" class="form-control @error('editForm.proposed_amount') is-invalid @enderror">
                                </div>
                                @error('editForm.proposed_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date</label>
                                <input wire:model="editForm.start_date" type="date" class="form-control @error('editForm.start_date') is-invalid @enderror">
                                @error('editForm.start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date</label>
                                <input wire:model="editForm.end_date" type="date" class="form-control @error('editForm.end_date') is-invalid @enderror">
                                @error('editForm.end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Internal Notes</label>
                            <textarea wire:model="editForm.notes" class="form-control @error('editForm.notes') is-invalid @enderror" rows="3" placeholder="Add internal notes about this partnership..."></textarea>
                            @error('editForm.notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update Partnership</button>
                        <button wire:click="$set('showEditModal', false)" type="button" class="btn btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif
    
    <!-- Toast Notifications -->
    @if (session()->has('success'))
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
            <div class="toast show" role="alert">
                <div class="toast-header">
                    <strong class="me-auto">Success</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    {{ session('success') }}
                </div>
            </div>
        </div>
    @endif
    
    @if (session()->has('error'))
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
            <div class="toast show" role="alert">
                <div class="toast-header">
                    <strong class="me-auto">Error</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    {{ session('error') }}
                </div>
            </div>
        </div>
    @endif
</div>
