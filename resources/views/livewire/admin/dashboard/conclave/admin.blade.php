<?php

use App\Models\Chapter;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;


new  #[Layout('components.layouts.admin')]  class extends Component {

    public $total_conclaves;

    public $chapter_id;
    public $oldAdminId;

    public ?string $search = null;
    public $selectedUserId;

    public function mount()
    {
        $this->total_conclaves = Chapter::count();
        
        $chapterName = request()->query('chapter');
        if ($chapterName) {
            $chapter = Chapter::where('name', '=', $chapterName)->first();
            if ($chapter) {
                $this->chapter_id = $chapter->id;
            }
        }
    }

    public function getChapters()
    {
        return Chapter::with(['admin'])->paginate();
    }

    public function getUsers()
    {
        if (!$this->chapter_id) {
            return collect([]);
        }
        
        return User::where('chapter_id', $this->chapter_id)
            ->when($this->search, function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                      ->orWhere('email', 'like', "%{$this->search}%");
            })
            ->paginate(10);
    }


    public function edit()
    {
        $chapter = Chapter::findOrFail($this->chapter_id);
        $user = User::where('id', '=', $this->oldAdminId)->first();

        $user->removeRole('admin');
        $user->assignRole('member');
        $this->assignAdmin();

        $this->dispatch('$refresh');
        $this->dispatch("admin-changed");
    }


    /**
     * Handle an incoming registration request.
     */
    public function save(): void
    {
        $this->assignAdmin();
        $this->dispatch('$refresh');
        $this->dispatch("admin-assigned");
    }

    protected function assignAdmin()
    {
        $user = User::find($this->selectedUserId);
        if (!$user) {
            return;
        }

        $chapter = Chapter::find($this->chapter_id);
        if (!$chapter) {
            return;
        }

        $user->chapter_id = $chapter->id;
        $user->save();

        $user->assignRole('admin');
        $this->reset();
    }


}; ?>

<div>
    <x-fancy-header title="Chapter Admins" subtitle="Assign or change chapter administrators" :breadcrumbs="[
        ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
        ['label' => 'Chapters', 'url' => route('super-admin.conclaves', request()->query())],
        ['label' => 'Assign Admins']
    ]" class="mb-4">
    </x-fancy-header>

    <x-card class="dark:bg-zinc-900 dark:text-gray-200 text-zinc-900">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="text-lg font-semibold">Chapters</div>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                    Total of {{ $total_conclaves }} chapters
                </div>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-zinc-500 dark:text-zinc-400">
                    <tr>
                        <th class="py-2 pr-4">Chapter</th>
                        <th class="py-2 pr-4">Admin</th>
                        <th class="py-2 pr-4">Email</th>
                        <th class="py-2 pr-4">Created</th>
                        <th class="py-2">Actions</th>
                    </tr>
                </thead>
                    <tbody>
                    @forelse ($this->getChapters() as $chapter)
                        @php
                            $admin = $chapter->admin;
                        @endphp
                        <tr class="border-t border-zinc-200 dark:border-zinc-700">
                            <td class="py-2 pr-4 font-medium">{{ $chapter->name }}</td>
                            <td class="py-2 pr-4">{{ $admin?->name ?? 'No admin assigned' }}</td>
                            <td class="py-2 pr-4 text-zinc-600 dark:text-zinc-400">{{ $admin?->email ?? '-' }}</td>
                            <td class="py-2 pr-4 text-zinc-600 dark:text-zinc-400">
                                {{ optional($chapter->created_at)->toDayDateTimeString() }}
                            </td>
                            <td class="py-2">
        @if($admin)
            <div class="flex flex-col gap-2">
                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                    Current Admin: {{ $admin->name }} ({{ $admin->email }})
                </div>
                <button class="rounded-lg px-3 py-1.5 text-sm bg-zinc-900 text-white dark:bg-white dark:text-zinc-900 hover:opacity-90"
                    x-on:click="$wire.set('chapter_id', {{ $chapter->id }}).then(() => $wire.set('oldAdminId', {{ $admin->id }})).then(() => $wire.set('search', '')).then(() => $modalOpen('change-admin-modal'))">
                    Change Admin
                </button>
            </div>
        @else
            <div class="flex flex-col gap-2">
                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                    No admin assigned
                </div>
                <button class="rounded-lg px-3 py-1.5 text-sm bg-zinc-900 text-white dark:bg-white dark:text-zinc-900 hover:opacity-90"
                    x-on:click="$wire.set('chapter_id', {{ $chapter->id }}).then(() => $wire.set('search', '')).then(() => $modalOpen('select-admin-modal'))">
                    Assign Admin
                </button>
            </div>
        @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="border-t border-zinc-200 dark:border-zinc-700">
                            <td class="py-2 text-zinc-500 dark:text-zinc-400" colspan="5">
                                No chapters found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-modal id="select-admin-modal" center>
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-400">Chapter ID: {{ $chapter_id }}</div>
                <button wire:click="$refresh" class="text-xs text-blue-400 hover:text-blue-300">Refresh</button>
            </div>
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-gray-300">Search Users</label>
                <input type="text" wire:model.live="search" 
                       placeholder="Search by name or email..." 
                       class="px-3 py-2 border border-gray-600 bg-dark-700 text-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="overflow-y-auto max-h-64">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-zinc-500 dark:text-zinc-400">
                        <tr>
                            <th class="py-2 pr-4">Name</th>
                            <th class="py-2 pr-4">Email</th>
                            <th class="py-2 pr-4">Chapter</th>
                            <th class="py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->getUsers() as $user)
                            <tr class="border-t border-zinc-200 dark:border-zinc-700">
                                <td class="py-2 pr-4">{{ $user->name }}</td>
                                <td class="py-2 pr-4 text-zinc-600 dark:text-zinc-400">{{ $user->email }}</td>
                                <td class="py-2 pr-4 text-zinc-600 dark:text-zinc-400">{{ $user->chapter?->name ?? 'N/A' }}</td>
                                <td class="py-2">
                                    <button class="rounded-lg px-3 py-1.5 text-sm bg-zinc-900 text-white dark:bg-white dark:text-zinc-900 hover:opacity-90"
                                        x-on:click="$wire.set('selectedUserId', {{ $user->id }}); $wire.save();">
                                        Select
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr class="border-t border-zinc-200 dark:border-zinc-700">
                                <td class="py-2 text-zinc-500 dark:text-zinc-400" colspan="4">
                                    No users found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-end mt-4">
                <flux:button x-on:click="$modalClose('select-admin-modal')" class="w-full mt-2 bg-zinc-950">Cancel</flux:button>
            </div>
        </div>
    </x-modal>

    <x-modal id="change-admin-modal" center>
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                    Current Admin: {{ $oldAdminId ? User::find($oldAdminId)->name : 'N/A' }}
                </div>
                <button wire:click="$refresh" class="text-xs text-blue-400 hover:text-blue-300">Refresh</button>
            </div>
            
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-gray-300">Search Users</label>
                <input type="text" wire:model.live="search" 
                       placeholder="Search by name or email..." 
                       class="px-3 py-2 border border-gray-600 bg-dark-700 text-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="overflow-y-auto max-h-64">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-zinc-500 dark:text-zinc-400">
                        <tr>
                            <th class="py-2 pr-4">Name</th>
                            <th class="py-2 pr-4">Email</th>
                            <th class="py-2 pr-4">Chapter</th>
                            <th class="py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->getUsers() as $user)
                            <tr class="border-t border-zinc-200 dark:border-zinc-700">
                                <td class="py-2 pr-4">{{ $user->name }}</td>
                                <td class="py-2 pr-4 text-zinc-600 dark:text-zinc-400">{{ $user->email }}</td>
                                <td class="py-2 pr-4 text-zinc-600 dark:text-zinc-400">{{ $user->chapter?->name ?? 'N/A' }}</td>
                                <td class="py-2">
                                    <button class="rounded-lg px-3 py-1.5 text-sm bg-zinc-900 text-white dark:bg-white dark:text-zinc-900 hover:opacity-90"
                                        x-on:click="$wire.set('selectedUserId', {{ $user->id }}); $wire.edit();">
                                        Change
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr class="border-t border-zinc-200 dark:border-zinc-700">
                                <td class="py-2 text-zinc-500 dark:text-zinc-400" colspan="4">
                                    No users found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-end mt-4">
                <flux:button x-on:click="$modalClose('change-admin-modal')" class="w-full mt-2 bg-zinc-950">Cancel</flux:button>
            </div>
        </div>
    </x-modal>
    @script
    <script>
        $wire.on('admin-assigned', (event) => {
            $modalClose('select-admin-modal')
        });
        $wire.on('admin-changed', (event)=>{
            $modalClose('change-admin-modal')
        });
    </script>
    @endscript

</div>
