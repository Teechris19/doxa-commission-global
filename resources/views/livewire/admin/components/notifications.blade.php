<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.admin')] class extends Component
{
    public function getNotificationsProperty()
    {
        return Auth::user()
            ->notifications()
            ->latest()
            ->take(10)
            ->get();
    }

    public function getUnreadCountProperty()
    {
        return Auth::user()->unreadNotifications()->count();
    }

    public function markAsRead(string $id): void
    {
        Auth::user()->notifications()->where('id', $id)->update(['read_at' => now()]);
    }

    public function markAllRead(): void
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);
    }
};

?>

<div class="relative">
    <flux:dropdown position="bottom" align="end">
        <div class="relative">
            <x-button.circle icon="bell" />
            @if($this->unreadCount > 0)
                <span class="absolute -top-1 -right-1 inline-flex items-center justify-center rounded-full bg-red-600 text-white text-[10px] h-4 min-w-4 px-1">
                    {{ $this->unreadCount }}
                </span>
            @endif
        </div>

        <flux:menu class="w-[320px]">
            <div class="px-3 py-2 text-xs text-zinc-500 dark:text-zinc-400">
                Notifications
            </div>
            <flux:menu.separator />

            @forelse($this->notifications as $notification)
                @php
                    $data = $notification->data ?? [];
                    $reportId = $data['report_id'] ?? null;
                    $title = $data['title'] ?? 'Report';
                    $from = $data['from_level'] ?? '';
                    $to = $data['to_level'] ?? '';
                    $isUnread = $notification->read_at === null;
                @endphp

                <flux:menu.item
                    class="flex flex-col items-start gap-1"
                    wire:click="markAsRead('{{ $notification->id }}')"
                    :href="$reportId ? route('admin.dashboard.reports.view-report', ['id' => $reportId] + request()->query()) : null"
                    wire:navigate
                >
                    <div class="text-sm font-medium {{ $isUnread ? 'text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400' }}">
                        {{ $title }}
                    </div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                        Moved from {{ ucfirst($from) }} to {{ ucfirst($to) }}
                    </div>
                </flux:menu.item>
            @empty
                <div class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400">
                    No notifications yet.
                </div>
            @endforelse

            @if($this->unreadCount > 0)
                <flux:menu.separator />
                <flux:menu.item wire:click="markAllRead">
                    Mark all as read
                </flux:menu.item>
            @endif
        </flux:menu>
    </flux:dropdown>
</div>
