<?php

use App\Models\{CellGroup, CellLeader, Chapter, User};
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions;

    #[Url(keep: true)]
    public ?string $chapter = null;

    public ?int $chapterId = null;
    public array $chapters = [];

    public array $cellLeaders = [];
    public bool $showAddModal = false;
    public ?int $editingLeaderId = null;

    public ?int $selectedCellId = null;
    public ?int $selectedUserId = null;

    public function mount(): void
    {
        $user = Auth::user();

        if ($user->hasRole('super-admin')) {
            $this->chapters = Chapter::orderBy('name')->get()->all();
            if ($this->chapter) {
                $this->chapterId = Chapter::where('name', $this->chapter)->value('id');
            }

            if (!$this->chapterId && !empty($this->chapters)) {
                $this->chapterId = $this->chapters[0]->id;
            }
        } else {
            $this->chapterId = $user?->chapter_id;
        }

        $this->loadCellLeaders();
    }

    public function updatedChapterId(): void
    {
        if (!$this->chapterId) {
            return;
        }

        $selected = Chapter::find($this->chapterId);
        $this->chapter = $selected?->name;
        $this->loadCellLeaders();
    }

    protected function loadCellLeaders(): void
    {
        if (!$this->chapterId) {
            return;
        }

        $this->cellLeaders = CellLeader::with(['cellGroup', 'user'])
            ->whereHas('cellGroup', fn($q) => $q->where('chapter_id', $this->chapterId))
            ->orderBy('name')
            ->get()
            ->map(fn($leader) => [
                'id' => $leader->id,
                'cell_name' => $leader->cellGroup?->name ?? 'N/A',
                'cell_id' => $leader->cell_group_id,
                'user_name' => $leader->user?->name ?? $leader->name ?? 'N/A',
                'user_id' => $leader->user_id,
                'phone' => $leader->user?->phone ?? $leader->phone ?? 'N/A',
                'email' => $leader->user?->email ?? $leader->email ?? 'N/A',
            ])
            ->toArray();
    }

    public function openAddModal(): void
    {
        $this->resetForm();
        $this->showAddModal = true;
    }

    public function openEditModal(int $id): void
    {
        $leader = CellLeader::find($id);
        if (!$leader) {
            return;
        }

        $this->editingLeaderId = $id;
        $this->selectedCellId = $leader->cell_group_id;
        $this->selectedUserId = $leader->user_id;
        $this->showAddModal = true;
    }

    public function cellSelected(): void
    {
        if (!$this->selectedCellId) {
            $this->selectedUserId = null;
            return;
        }

        // Auto-populate the current leader when a cell is selected
        $currentLeader = CellLeader::where('cell_group_id', $this->selectedCellId)->first();
        if ($currentLeader) {
            $this->selectedUserId = $currentLeader->user_id;
            $this->editingLeaderId = $currentLeader->id;
        } else {
            $this->selectedUserId = null;
            $this->editingLeaderId = null;
        }
    }

    public function closeModal(): void
    {
        $this->showAddModal = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->editingLeaderId = null;
        $this->selectedCellId = null;
        $this->selectedUserId = null;
    }

    public function saveLeader(): void
    {
        $validated = $this->validate([
            'selectedCellId' => 'required|integer|exists:cell_groups,id',
            'selectedUserId' => 'required|integer|exists:users,id',
        ]);

        $user = User::find($validated['selectedUserId']);
        $cell = CellGroup::find($validated['selectedCellId']);

        if (!$user || !$cell) {
            $this->toast()->error('Error', 'Invalid selection.')->send();
            return;
        }

        // Find existing leader for this cell
        $existingLeader = CellLeader::where('cell_group_id', $validated['selectedCellId'])->first();

        if ($existingLeader) {
            // Update the existing leader instead of creating a duplicate
            $existingLeader->update([
                'user_id' => $validated['selectedUserId'],
                'name' => $user->name,
                'phone' => $user->phone ?? '',
                'email' => $user->email ?? '',
                'is_primary' => true,
            ]);
            $this->toast()->success('Updated', 'Cell leader updated successfully.')->send();
        } else {
            // Create new leader
            CellLeader::create([
                'cell_group_id' => $validated['selectedCellId'],
                'user_id' => $validated['selectedUserId'],
                'name' => $user->name,
                'phone' => $user->phone ?? '',
                'email' => $user->email ?? '',
                'is_primary' => true,
            ]);
            $this->toast()->success('Added', 'Cell leader added successfully.')->send();
        }

        $this->closeModal();
        $this->loadCellLeaders();
    }

    public ?int $deleteId = null;

    public function deleteLeader(int $id): void
    {
        $this->deleteId = $id;
        $this->dialog()
            ->error('Are you sure you want to remove this cell leader?')
            ->hook([
                'ok' => [
                    'method' => 'confirmDeleteLeader',
                ],
            ])
            ->send();
    }

    public function confirmDeleteLeader(): void
    {
        if (!$this->deleteId) {
            return;
        }

        $leader = CellLeader::find($this->deleteId);
        if ($leader) {
            $leader->delete();
            $this->toast()->success('Removed', 'Cell leader removed successfully.')->send();
            $this->loadCellLeaders();
        }

        $this->deleteId = null;
    }

    public function getCellsProperty()
    {
        if (!$this->chapterId) {
            return collect();
        }

        return CellGroup::where('chapter_id', $this->chapterId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getUsersProperty()
    {
        if (!$this->chapterId) {
            return collect();
        }

        return User::where('chapter_id', $this->chapterId)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
};

?>

<div class="space-y-6">
    <x-fancy-header
        title="Cell Leaders"
        subtitle="Assign leaders to cell groups"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Cells', 'url' => route('admin.dashboard.cells.index', request()->query())],
            ['label' => 'Cell Leaders']
        ]"
    >
        <x-button wire:click="openAddModal" icon="plus" class="bg-blue-600 hover:bg-blue-700">Add Cell Leader</x-button>
    </x-fancy-header>

    @if(Auth::user()->hasRole('super-admin'))
        <div class="rounded-xl bg-white p-4 shadow dark:bg-slate-800">
            <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Branch</label>
            <select wire:model.live="chapterId" class="w-full max-w-sm rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                <option value="">Select branch</option>
                @foreach($chapters as $chapterOption)
                    <option value="{{ $chapterOption->id }}">{{ $chapterOption->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="overflow-x-auto rounded-xl bg-white shadow dark:bg-slate-800">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 dark:text-slate-300">Cell Group</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 dark:text-slate-300">Leader</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 dark:text-slate-300">Contact</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 dark:text-slate-300">Role</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-slate-700 dark:text-slate-300">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-700 dark:bg-slate-900">
                @forelse($cellLeaders as $leader)
                    <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <td class="px-4 py-3.5">
                            <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $leader['cell_name'] }}</p>
                        </td>
                        <td class="px-4 py-3.5">
                            <p class="font-medium text-slate-700 dark:text-slate-300">{{ $leader['user_name'] }}</p>
                        </td>
                        <td class="px-4 py-3.5">
                            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $leader['phone'] }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-500">{{ $leader['email'] }}</p>
                        </td>
                        <td class="px-4 py-3.5">
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">Leader</span>
                        </td>
                        <td class="px-4 py-3.5 text-right">
                            <div class="flex justify-end gap-2">
                                <button type="button" wire:click="openEditModal({{ $leader['id'] }})" class="rounded-md border border-blue-200 bg-white px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-50 dark:border-blue-700 dark:bg-slate-800 dark:text-blue-400 dark:hover:bg-slate-700">Edit</button>
                                <button type="button" wire:click="deleteLeader({{ $leader['id'] }})" class="rounded-md border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-50 dark:border-rose-700 dark:bg-slate-800 dark:text-rose-400 dark:hover:bg-slate-700">Remove</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm font-medium text-slate-500 dark:text-slate-400">No cell leaders assigned yet. Click "Add Cell Leader" to assign one.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Add/Edit Modal --}}
    @if($showAddModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" wire:click="closeModal">
            <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-slate-800" wire:click.stop>
                <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ $editingLeaderId ? 'Edit' : 'Add' }} Cell Leader</h3>
                </div>

                <div class="space-y-4 p-6">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Cell Group</label>
                        <select wire:model="selectedCellId" wire:change="cellSelected" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                            <option value="">Select cell group</option>
                            @foreach($this->cells as $cell)
                                <option value="{{ $cell->id }}">{{ $cell->name }}</option>
                            @endforeach
                        </select>
                        @error('selectedCellId') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">User / Leader</label>
                        <select wire:model="selectedUserId" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                            <option value="">Select user</option>
                            @foreach($this->users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        @error('selectedUserId') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="border-t border-slate-200 px-6 py-4 dark:border-slate-700">
                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="closeModal" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600">Cancel</button>
                        <button type="button" wire:click="saveLeader" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">{{ $editingLeaderId ? 'Update' : 'Add' }} Leader</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
