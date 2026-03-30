<?php

use App\Models\Chapter;
use App\Models\User;
use App\Models\Team;
use App\Models\Appointment;
use App\Models\Transport;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {

    public $total_chapters = 0;
    public $total_members = 0;
    public $total_teams = 0;
    public $total_admins = 0;
    public $recent_members = [];
    public $chapters_list = [];

    public function mount()
    {
        $this->total_chapters = Chapter::count();
        $this->total_members = User::count();
        $this->total_teams = Team::count();
        $this->total_admins = User::role('admin')->count();
        
        $this->recent_members = User::latest()
            ->take(5)
            ->get(['id', 'name', 'email', 'chapter_id', 'created_at'])
            ->map(function($user) {
                $user->chapter_name = $user->chapter ? $user->chapter->name : 'N/A';
                return $user;
            })
            ->toArray();
            
        $this->chapters_list = Chapter::withCount(['users', 'teams'])
            ->latest()
            ->take(5)
            ->get()
            ->toArray();
    }
}; ?>

<div class="dark:bg-zinc-900">
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-card header="Chapters" minimize class="dark:bg-zinc-800 dark:text-gray-200">
            <div class="text-5xl m-3 font-[montserat] text-slate-900 dark:text-gray-100">{{ $total_chapters }}</div>
            <span><small class="text-slate-600 dark:text-gray-400">Total active chapters</small></span>
            <x-link :href="route('super-admin.conclaves')" text="Manage All"
                icon="arrow-up-right" position="right" wire:navigate />
        </x-card>

        <x-card header="Members" minimize class="dark:bg-zinc-800 dark:text-gray-200">
            <div class="text-5xl m-3 font-[montserat] text-slate-900 dark:text-gray-100">{{ $total_members }}</div>
            <span><small class="text-slate-600 dark:text-gray-400">Total members across all chapters</small></span>
            <x-link :href="route('admin.dashboard.members')" text="View All"
                icon="arrow-up-right" position="right" wire:navigate />
        </x-card>

        <x-card header="Teams" minimize class="dark:bg-zinc-800 dark:text-gray-200">
            <div class="text-5xl m-3 font-[montserat] text-slate-900 dark:text-gray-100">{{ $total_teams }}</div>
            <span><small class="text-slate-600 dark:text-gray-400">Active teams across all chapters</small></span>
            <x-link :href="route('admin.dashboard.teams')" text="View Teams"
                icon="arrow-up-right" position="right" wire:navigate />
        </x-card>

        <x-card header="Chapter Admins" minimize class="dark:bg-zinc-800 dark:text-gray-200">
            <div class="text-5xl m-3 font-[montserat] text-slate-900 dark:text-gray-100">{{ $total_admins }}</div>
            <span><small class="text-slate-600 dark:text-gray-400">Total chapter admins</small></span>
            <x-link :href="route('admin.dashboard.members')" text="Manage Admins"
                icon="arrow-up-right" position="right" wire:navigate />
        </x-card>
    </div>

    <div class="grid lg:grid-cols-2 gap-6 mt-6">
        <x-card header="Quick Actions" class="dark:bg-zinc-800 dark:text-gray-200">
            <div class="text-sm text-slate-600 dark:text-gray-400 mb-4">
                Super admin quick actions.
            </div>
            <div class="flex flex-wrap gap-3">
                <x-link :href="route('super-admin.conclaves.create')" text="New Chapter"
                    icon="plus" position="right" wire:navigate />
                <x-link :href="route('super-admin.conclaves.add-admin')" text="Assign Admin"
                    icon="user-plus" position="right" wire:navigate />
                <x-link :href="route('admin.dashboard.members.create')" text="Add Member"
                    icon="user-plus" position="right" wire:navigate />
                <x-link :href="route('admin.dashboard.teams.create')" text="Create Team"
                    icon="plus" position="right" wire:navigate />
                <x-link :href="route('admin.dashboard.settings.index')" text="Global Settings"
                    icon="cog" position="right" wire:navigate />
            </div>
        </x-card>

        <x-card header="Recent Members" class="dark:bg-zinc-800 dark:text-gray-200">
            <div class="text-sm text-slate-600 dark:text-gray-400 mb-4">
                Latest registrations across all chapters.
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-slate-500 dark:text-gray-400">
                        <tr>
                            <th class="py-2 pr-4">Name</th>
                            <th class="py-2 pr-4">Email</th>
                            <th class="py-2 pr-4">Chapter</th>
                            <th class="py-2">Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recent_members as $member)
                            <tr class="border-t border-slate-200 dark:border-zinc-700">
                                <td class="py-2 pr-4 text-slate-900 dark:text-gray-200">{{ $member['name'] }}</td>
                                <td class="py-2 pr-4 text-slate-600 dark:text-gray-400">{{ $member['email'] }}</td>
                                <td class="py-2 pr-4 text-slate-600 dark:text-gray-400">{{ $member['chapter_name'] ?? 'N/A' }}</td>
                                <td class="py-2 text-slate-600 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($member['created_at'])->toDayDateTimeString() }}
                                </td>
                            </tr>
                        @empty
                            <tr class="border-t border-slate-200 dark:border-zinc-700">
                                <td class="py-2 text-slate-500 dark:text-gray-400" colspan="4">
                                    No recent members found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <div class="mt-6">
        <x-card header="Recent Chapters" class="dark:bg-zinc-800 dark:text-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-slate-500 dark:text-gray-400">
                        <tr>
                            <th class="py-2 pr-4">Chapter Name</th>
                            <th class="py-2 pr-4">Members</th>
                            <th class="py-2 pr-4">Teams</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($chapters_list as $chapter)
                            <tr class="border-t border-slate-200 dark:border-zinc-700">
                                <td class="py-2 pr-4 text-slate-900 dark:text-gray-200">{{ $chapter['name'] }}</td>
                                <td class="py-2 pr-4 text-slate-600 dark:text-gray-400">{{ $chapter['users_count'] }}</td>
                                <td class="py-2 pr-4 text-slate-600 dark:text-gray-400">{{ $chapter['teams_count'] }}</td>
                            </tr>
                        @empty
                            <tr class="border-t border-slate-200 dark:border-zinc-700">
                                <td class="py-2 text-slate-500 dark:text-gray-400" colspan="3">
                                    No chapters found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>
