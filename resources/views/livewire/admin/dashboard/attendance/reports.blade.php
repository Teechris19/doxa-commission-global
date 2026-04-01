<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Attendance Reports</h1>
            <p class="text-sm text-gray-600">Analytics and insights on attendance patterns.</p>
        </div>
        
        <div class="flex items-center gap-3">
            @if(auth()->user()->hasRole('super-admin'))
                <select wire:model.live="chapter" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach($chapters as $chap)
                        <option value="{{ $chap->name }}">{{ $chap->name }}</option>
                    @endforeach
                </select>
            @endif
            
            <select wire:model.live="dateFilter" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="7">Last 7 Days</option>
                <option value="30">Last 1 Month</option>
                <option value="180">Last 6 Months</option>
                <option value="365">Last 1 Year</option>
            </select>
        </div>
    </div>

    {{-- Overall Stats --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Attendance Rate</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $overallStats['attendance_rate'] }}%</p>
                </div>
                <div class="rounded-full bg-green-100 p-3">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-500">{{ $overallStats['present'] + $overallStats['late'] }} of {{ $overallStats['total'] }} marked present/late</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Absentee Rate</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $overallStats['absentee_rate'] }}%</p>
                </div>
                <div class="rounded-full bg-red-100 p-3">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-500">{{ $overallStats['absent'] }} absent records</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Present</p>
                    <p class="mt-2 text-3xl font-bold text-green-600">{{ $overallStats['present'] }}</p>
                </div>
                <div class="rounded-full bg-green-50 p-3">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-500">Members present</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Late</p>
                    <p class="mt-2 text-3xl font-bold text-amber-600">{{ $overallStats['late'] }}</p>
                </div>
                <div class="rounded-full bg-amber-50 p-3">
                    <svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-500">Members arrived late</p>
        </div>
    </div>

    {{-- Trend Chart --}}
    <x-card>
        <h2 class="text-lg font-semibold mb-4">Attendance Trend</h2>
        <canvas id="trendChart" height="80"></canvas>
    </x-card>

    {{-- Role Breakdown --}}
    <x-card>
        <h2 class="text-lg font-semibold mb-4">Attendance by Role</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Present</th>
                        <th class="px-4 py-3">Late</th>
                        <th class="px-4 py-3">Absent</th>
                        <th class="px-4 py-3">Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($roleBreakdown as $role)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $role['role'] }}</td>
                            <td class="px-4 py-3">{{ $role['total'] }}</td>
                            <td class="px-4 py-3 text-green-600">{{ $role['present'] }}</td>
                            <td class="px-4 py-3 text-amber-600">{{ $role['late'] }}</td>
                            <td class="px-4 py-3 text-red-600">{{ $role['absent'] }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                    {{ $role['rate'] >= 80 ? 'bg-green-100 text-green-800' : ($role['rate'] >= 50 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                    {{ $role['rate'] }}%
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- Team Stats --}}
    <x-card>
        <h2 class="text-lg font-semibold mb-4">Team Attendance Rates</h2>
        <div class="space-y-4">
            @foreach($teamStats as $team)
                <div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium">{{ $team['name'] }}</span>
                        <span class="text-gray-600">{{ $team['present'] + $team['late'] }}/{{ $team['total'] }} ({{ $team['rate'] }}%)</span>
                    </div>
                    <div class="mt-1 h-2 w-full rounded-full bg-gray-200">
                        <div class="h-2 rounded-full {{ $team['rate'] >= 80 ? 'bg-green-500' : ($team['rate'] >= 50 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $team['rate'] }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-card>

    {{-- Top Members --}}
    <x-card>
        <h2 class="text-lg font-semibold mb-4">Member Attendance (Top 20)</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Member</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Present</th>
                        <th class="px-4 py-3">Late</th>
                        <th class="px-4 py-3">Absent</th>
                        <th class="px-4 py-3">Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($memberStats as $member)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $member['name'] }}</td>
                            <td class="px-4 py-3">{{ $member['total'] }}</td>
                            <td class="px-4 py-3 text-green-600">{{ $member['present'] }}</td>
                            <td class="px-4 py-3 text-amber-600">{{ $member['late'] }}</td>
                            <td class="px-4 py-3 text-red-600">{{ $member['absent'] }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                    {{ $member['rate'] >= 80 ? 'bg-green-100 text-green-800' : ($member['rate'] >= 50 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                    {{ $member['rate'] }}%
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('livewire:navigated', initTrendChart);
    document.addEventListener('livewire:load', initTrendChart);

    let trendChart;
    function initTrendChart() {
        const ctx = document.getElementById('trendChart');
        if (!ctx) return;

        const data = @json($trendData);
        const labels = data.map(d => d.date);
        const presentData = data.map(d => d.present);
        const lateData = data.map(d => d.late);
        const absentData = data.map(d => d.absent);

        if (trendChart) {
            trendChart.destroy();
        }

        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Present',
                        data: presentData,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.3,
                        fill: true,
                    },
                    {
                        label: 'Late',
                        data: lateData,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.3,
                        fill: true,
                    },
                    {
                        label: 'Absent',
                        data: absentData,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.3,
                        fill: true,
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
</script>
