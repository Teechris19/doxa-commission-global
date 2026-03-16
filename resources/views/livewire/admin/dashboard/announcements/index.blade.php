<?php

use App\Models\BroadcastAnnouncement;
use App\Models\Chapter;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions;

    #[Url]
    public ?string $chapter = null;

    public ?int $chapterId = null;
    public array $chapters = [];

    public ?int $editingId = null;
    public string $title = '';
    public string $message = '';
    public string $status = 'draft';
    public ?string $send_at = null;
    public string $channel = 'mail_database';
    public string $target_type = 'both';
    public string $target_audience = 'all_users';

    public function mount(): void
    {
        if (Auth::user()->hasRole('super-admin')) {
            $this->chapters = Chapter::orderBy('name')->get(['id', 'name'])->toArray();
            if ($this->chapter) {
                $this->chapterId = Chapter::where('name', $this->chapter)->value('id');
            }
        } else {
            $this->chapterId = Auth::user()->chapter_id;
        }
    }

    public function with(): array
    {
        $announcements = BroadcastAnnouncement::query()
            ->with(['chapter', 'creator'])
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->latest()
            ->get();

        return [
            'announcements' => $announcements,
        ];
    }

    public function edit(int $id): void
    {
        $announcement = BroadcastAnnouncement::findOrFail($id);
        $this->editingId = $announcement->id;
        $this->title = $announcement->title;
        $this->message = $announcement->message;
        $this->status = $announcement->status;
        $this->send_at = optional($announcement->send_at)->format('Y-m-d\TH:i');
        $this->channel = $announcement->channel;
        $this->chapterId = $announcement->chapter_id;
        $this->target_type = $announcement->target_type ?? 'both';
        $this->target_audience = $announcement->target_audience ?? 'all_users';
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->message = '';
        $this->status = 'draft';
        $this->send_at = null;
        $this->channel = 'mail_database';
        $this->target_type = 'both';
        $this->target_audience = 'all_users';

        if (!Auth::user()->hasRole('super-admin')) {
            $this->chapterId = Auth::user()->chapter_id;
        }
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'status' => ['required', 'in:draft,scheduled,sent'],
            'send_at' => ['nullable', 'date'],
            'channel' => ['required', 'in:mail_database,mail,database'],
            'chapterId' => ['nullable', 'exists:chapters,id'],
            'target_type' => ['required', 'in:admin_dashboard,user_toast,both'],
            'target_audience' => ['required', 'in:all_users,admins,team_leads'],
        ]);

        $user = Auth::user();
        $creatorType = $user->hasRole('super-admin') ? 'super_admin' : 'admin';

        $payload = [
            'title' => $this->title,
            'message' => $this->message,
            'status' => $this->status,
            'send_at' => $this->send_at ? \Carbon\Carbon::parse($this->send_at) : null,
            'channel' => $this->channel,
            'chapter_id' => $this->chapterId,
            'target_type' => $this->target_type,
            'target_audience' => $this->target_audience,
            'creator_type' => $creatorType,
            'created_by' => $user->id,
        ];

        if ($this->editingId) {
            $announcement = BroadcastAnnouncement::findOrFail($this->editingId);
            // Don't override creator info on edit
            unset($payload['creator_type'], $payload['created_by']);
            $announcement->update($payload);
        } else {
            BroadcastAnnouncement::create($payload);
        }

        $this->toast()->success('Done', 'Announcement saved.')->send();
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        BroadcastAnnouncement::findOrFail($id)->delete();
        $this->toast()->success('Deleted', 'Announcement removed.')->send();
    }
};
?>

<div>
    <x-fancy-header title="Announcements" subtitle="Schedule and send broadcast messages" :breadcrumbs="[
        ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
        ['label' => 'Announcements']
    ]" />

    <div class="mt-6 space-y-6">
        <x-card class="w-full">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit Announcement' : 'New Announcement' }}</h3>
                <p class="text-sm text-slate-500">Create messages for email and in‑app delivery.</p>
            </div>
            <form wire:submit.prevent="save" class="space-y-4">
                <x-input label="Title" wire:model="title" />
                <x-textarea label="Message" wire:model="message" />

                @if(auth()->user()->hasRole('super-admin'))
                    <x-select label="Chapter (optional)" wire:model="chapterId">
                        <option value="">All Chapters</option>
                        @foreach ($chapters as $chap)
                            <option value="{{ $chap['id'] }}">{{ $chap['name'] }}</option>
                        @endforeach
                    </x-select>
                @endif

                <x-select label="Target" wire:model="target_type">
                    <option value="both">Admin Dashboard + User Toast</option>
                    <option value="admin_dashboard">Admin Dashboard Only</option>
                    <option value="user_toast">User Toast Only</option>
                </x-select>

                <x-select label="Send To" wire:model="target_audience">
                    <option value="all_users">All Users (Branch Members)</option>
                    <option value="admins">All Admins</option>
                    <option value="team_leads">Team Leads (Branch)</option>
                </x-select>

                <x-select label="Channel" wire:model="channel">
                    <option value="mail_database">Email + In-App</option>
                    <option value="mail">Email Only</option>
                    <option value="database">In-App Only</option>
                </x-select>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-select label="Status" wire:model="status">
                        <option value="draft">Draft</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="sent">Sent</option>
                    </x-select>
                    <x-input type="datetime-local" label="Send At" wire:model="send_at" />
                </div>

                <div class="flex gap-2">
                    <x-button type="submit" class="bg-blue-600 hover:bg-blue-700">Save</x-button>
                    <x-button type="button" wire:click="resetForm">Reset</x-button>
                </div>
            </form>
        </x-card>

        <x-card class="w-full">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Recent Announcements</h3>
                <span class="text-xs text-slate-500">{{ $announcements->count() }} total</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-slate-500">
                        <tr>
                            <th class="py-2">Title</th>
                            <th class="py-2">Target</th>
                            <th class="py-2">Send To</th>
                            <th class="py-2">Status</th>
                            <th class="py-2">Send At</th>
                            <th class="py-2">Channel</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($announcements as $announcement)
                            @php
                                $badge = match($announcement->status) {
                                    'sent' => 'bg-emerald-100 text-emerald-700',
                                    'scheduled' => 'bg-blue-100 text-blue-700',
                                    default => 'bg-slate-100 text-slate-600',
                                };
                            @endphp
                            <tr class="border-t">
                                <td class="py-2">
                                    <p class="font-medium text-slate-900">{{ $announcement->title }}</p>
                                    <p class="text-xs text-slate-500 line-clamp-1">{{ $announcement->message }}</p>
                                    @if($announcement->chapter)
                                        <span class="text-xs bg-blue-100 text-blue-700 px-1.5 rounded">{{ $announcement->chapter->name }}</span>
                                    @endif
                                    @if($announcement->creator)
                                        <span class="text-xs bg-purple-100 text-purple-700 px-1.5 rounded">{{ $announcement->creator_type === 'super_admin' ? 'Super Admin' : 'Admin' }}</span>
                                    @endif
                                </td>
                                <td class="py-2">
                                    @php
                                        $targetBadge = match($announcement->target_type) {
                                            'admin_dashboard' => 'bg-indigo-100 text-indigo-700',
                                            'user_toast' => 'bg-amber-100 text-amber-700',
                                            default => 'bg-emerald-100 text-emerald-700',
                                        };
                                        $targetLabel = match($announcement->target_type) {
                                            'admin_dashboard' => 'Admin',
                                            'user_toast' => 'Toast',
                                            default => 'Both',
                                        };
                                    @endphp
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $targetBadge }}">
                                        {{ $targetLabel }}
                                    </span>
                                </td>
                                <td class="py-2">
                                    @php
                                        $audienceBadge = match($announcement->target_audience) {
                                            'admins' => 'bg-rose-100 text-rose-700',
                                            'team_leads' => 'bg-orange-100 text-orange-700',
                                            default => 'bg-blue-100 text-blue-700',
                                        };
                                        $audienceLabel = match($announcement->target_audience) {
                                            'admins' => 'Admins',
                                            'team_leads' => 'Team Leads',
                                            default => 'Users',
                                        };
                                    @endphp
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $audienceBadge }}">
                                        {{ $audienceLabel }}
                                    </span>
                                </td>
                                <td class="py-2">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge }}">
                                        {{ ucfirst($announcement->status) }}
                                    </span>
                                </td>
                                <td class="py-2">
                                    {{ $announcement->send_at ? $announcement->send_at->format('M d, Y H:i') : '—' }}
                                </td>
                                <td class="py-2">{{ str_replace('_', ' + ', $announcement->channel) }}</td>
                                <td class="py-2 text-right">
                                    <button wire:click="edit({{ $announcement->id }})" class="text-blue-600 hover:text-blue-700">Edit</button>
                                    <button wire:click="delete({{ $announcement->id }})" class="ml-3 text-rose-600 hover:text-rose-700">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-slate-500">No announcements yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>
