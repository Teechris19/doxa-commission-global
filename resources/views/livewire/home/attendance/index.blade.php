<?php

use App\Models\{AttendanceCheckin, AttendanceSession};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    public $attendanceHistory = [];
    public $stats = [
        'total' => 0,
        'thisMonth' => 0,
        'thisYear' => 0,
    ];

    public function mount()
    {
        if (Auth::check()) {
            $this->loadAttendanceData();
        }
    }

    private function loadAttendanceData()
    {
        $userId = Auth::id();

        $this->attendanceHistory = AttendanceCheckin::where('user_id', $userId)
            ->with(['session', 'session.event'])
            ->latest('checked_in_at')
            ->limit(20)
            ->get();

        $this->stats['total'] = AttendanceCheckin::where('user_id', $userId)->count();
        $this->stats['thisMonth'] = AttendanceCheckin::where('user_id', $userId)
            ->whereMonth('checked_in_at', now()->month)
            ->whereYear('checked_in_at', now()->year)
            ->count();
        $this->stats['thisYear'] = AttendanceCheckin::where('user_id', $userId)
            ->whereYear('checked_in_at', now()->year)
            ->count();
    }
};
?>

<div class="bg-white pb-12">
    <section class="border-b border-indigo-100 bg-gradient-to-b from-indigo-50 to-white">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-16">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-indigo-600">My Attendance</p>
                <h1 class="mt-3 text-3xl font-semibold text-slate-900 sm:text-4xl">Service Attendance History</h1>
                <p class="mt-4 text-sm leading-7 text-slate-600">Track your attendance at church services and events.</p>
            </div>
        </div>
    </section>

    @auth
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid gap-6 md:grid-cols-3">
            <div class="rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 p-6 text-white">
                <p class="text-xs font-semibold uppercase tracking-wider opacity-90">Total Check-ins</p>
                <p class="mt-2 text-3xl font-bold">{{ $stats['total'] }}</p>
            </div>
            <div class="rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-white">
                <p class="text-xs font-semibold uppercase tracking-wider opacity-90">This Month</p>
                <p class="mt-2 text-3xl font-bold">{{ $stats['thisMonth'] }}</p>
            </div>
            <div class="rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 p-6 text-white">
                <p class="text-xs font-semibold uppercase tracking-wider opacity-90">This Year</p>
                <p class="mt-2 text-3xl font-bold">{{ $stats['thisYear'] }}</p>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h2 class="mb-6 text-2xl font-semibold text-gray-900">Recent Attendance</h2>

        @if($attendanceHistory->isEmpty())
            <div class="rounded-xl border border-gray-200 p-8 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 mb-4">
                    <i class="bi bi-calendar-check text-2xl text-indigo-600"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">No attendance records yet</h3>
                <p class="mt-2 text-gray-500">Your check-in history will appear here after attending services.</p>
            </div>
        @else
            <div class="rounded-xl border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Service/Event</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Source</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($attendanceHistory as $attendance)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ $attendance->checked_in_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $attendance->session?->event?->title ?? $attendance->session?->name ?? 'General Service' }}
                                    </p>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ $attendance->checked_in_at->format('h:i A') }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                        {{ $attendance->source === 'app' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $attendance->source === 'manual' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $attendance->source === 'kiosk' ? 'bg-purple-100 text-purple-800' : '' }}">
                                        {{ ucfirst($attendance->source) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
    @else
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="rounded-xl border border-gray-200 p-8 text-center">
            <h3 class="text-lg font-medium text-gray-900">Login to View Attendance</h3>
            <p class="mt-2 text-gray-500 mb-4">Please login to view your attendance history.</p>
            <a href="{{ route('home.login') }}" class="inline-flex rounded-lg bg-indigo-600 px-6 py-3 font-semibold text-white hover:bg-indigo-700">
                Login
            </a>
        </div>
    </section>
    @endauth
</div>
