@php
    $user = auth()->user();
    $announcements = $user
        ? $user->notifications()
            ->where('type', 'App\\Notifications\\BroadcastAnnouncementNotification')
            ->latest()
            ->take(3)
            ->get()
        : collect();
@endphp

@if($announcements->isNotEmpty())
<div
    x-data="{
        open: true,
        collapsed: false,
        ids: @js($announcements->pluck('id')),
        init() {
            const dismissed = JSON.parse(localStorage.getItem('dismissed_announcements') || '[]');
            const hasNew = this.ids.some(id => !dismissed.includes(id));
            this.open = hasNew;
            if (!hasNew) {
                this.collapsed = true;
            }
            if (hasNew) {
                setTimeout(() => { this.collapsed = true; }, 8000);
            }
        },
        dismissAll() {
            const dismissed = JSON.parse(localStorage.getItem('dismissed_announcements') || '[]');
            const merged = Array.from(new Set([...dismissed, ...this.ids]));
            localStorage.setItem('dismissed_announcements', JSON.stringify(merged));
            this.open = false;
            this.collapsed = true;
        }
    }"
    x-init="init()"
    class="fixed bottom-4 right-4 z-50 w-full max-w-sm"
>
    <div
        x-show="open && !collapsed"
        x-transition.opacity
        class="mb-3 rounded-2xl border border-blue-100 bg-white p-4 shadow-[0_15px_40px_-25px_rgba(37,99,235,0.6)]"
    >
        <div class="flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-600">Announcement</p>
            @if($announcements->first()->data['chapter_name'] ?? null)
                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">{{ $announcements->first()->data['chapter_name'] }}</span>
            @endif
        </div>
        <p class="mt-2 text-sm text-slate-700">
            {{ $announcements->first()->data['message'] ?? 'You have a new announcement.' }}
        </p>
        @if($announcements->first()->data['message_full'] ?? null)
            <p class="mt-1 text-xs text-slate-500">
                {{ $announcements->first()->data['message_full'] }}
            </p>
        @endif
        <div class="mt-3 flex justify-end gap-2">
            <button @click="collapsed = true" class="text-xs font-semibold text-slate-500 hover:text-slate-700">Later</button>
            <button @click="dismissAll()" class="text-xs font-semibold text-blue-600 hover:text-blue-700">Dismiss</button>
        </div>
    </div>

    <button
        x-show="collapsed"
        x-transition.opacity
        @click="open = true; collapsed = false"
        class="flex w-full items-center justify-between rounded-2xl border border-blue-100 bg-white px-4 py-3 text-left text-sm text-slate-700 shadow-[0_12px_30px_-25px_rgba(37,99,235,0.6)]"
    >
        <span>Announcements</span>
        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">{{ $announcements->count() }}</span>
    </button>
</div>
@endif
