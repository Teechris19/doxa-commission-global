<?php

use App\Models\AppointmentTeams;
use App\Models\AttendanceTeams;
use App\Models\BelieversAcademyTeams;
use App\Models\Chapter;
use App\Models\EventTeam;
use App\Models\PrayerRequestTeam;
use App\Models\Team;
use App\Models\TeamFunction;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions;

    #[Url]
    public ?string $chapter = null;

    public ?int $chapterId = null;

    public array $teams = [];

    public array $enabledByFunction = [];

    public array $functions = [
        ['key' => 'transport', 'label' => 'Transportation'],
        ['key' => 'appointments', 'label' => 'Appointments'],
        ['key' => 'prayer_requests', 'label' => 'Prayer Requests'],
        ['key' => 'partnerships', 'label' => 'Partnerships'],
        ['key' => 'believers_academy', 'label' => "Believer's Academy"],
        ['key' => 'reports', 'label' => 'Report'],
        ['key' => 'analytics', 'label' => 'Analytics'],
        ['key' => 'attendance', 'label' => 'Attendance'],
        ['key' => 'events', 'label' => 'Events'],
        ['key' => 'media', 'label' => 'Media'],
    ];

    protected array $relationMap = [
        'appointments' => AppointmentTeams::class,
        'prayer_requests' => PrayerRequestTeam::class,
        'believers_academy' => BelieversAcademyTeams::class,
        'events' => EventTeam::class,
        'attendance' => AttendanceTeams::class,
    ];

    public function mount(): void
    {
        $this->chapterId = Chapter::where('name', '=', e($this->chapter))->value('id');
        $this->loadData();
    }

    protected function loadData(): void
    {
        $this->teams = Team::where('chapter_id', $this->chapterId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($team) => ['id' => $team->id, 'name' => $team->name])
            ->toArray();

        $enabled = [];

        foreach ($this->relationMap as $key => $model) {
            $enabled[$key] = $model::where('chapter_id', $this->chapterId)
                ->pluck('team_id')
                ->unique()
                ->values()
                ->all();
        }

        foreach ($this->teams as $team) {
            $teamFunctions = TeamFunction::where('team_id', $team['id'])->first();
            $functionMap = $teamFunctions?->function ?? [];

            foreach ($this->functions as $func) {
                $key = $func['key'];
                if (array_key_exists($key, $this->relationMap)) {
                    continue;
                }
                if (!empty($functionMap[$key])) {
                    $enabled[$key][] = $team['id'];
                }
            }
        }

        $this->enabledByFunction = $enabled;
    }

    public function toggleTeam(string $functionKey, int $teamId): void
    {
        if (array_key_exists($functionKey, $this->relationMap)) {
            $model = $this->relationMap[$functionKey];
            $existing = $model::where('chapter_id', $this->chapterId)
                ->where('team_id', $teamId)
                ->first();

            if ($existing) {
                $existing->delete();
                $this->toast()->success('Done', 'Team removed from function.')->send();
            } else {
                $model::create([
                    'chapter_id' => $this->chapterId,
                    'team_id' => $teamId,
                ]);
                $this->toast()->success('Done', 'Team assigned to function.')->send();
            }

            $this->loadData();
            return;
        }

        $teamFunction = TeamFunction::firstOrCreate(
            ['team_id' => $teamId],
            ['function' => ['report' => true]]
        );
        $functionMap = $teamFunction->function ?? [];

        if (!empty($functionMap[$functionKey])) {
            unset($functionMap[$functionKey]);
            $this->toast()->success('Done', 'Team permission removed.')->send();
        } else {
            $functionMap[$functionKey] = true;
            $this->toast()->success('Done', 'Team permission granted.')->send();
        }

        // Ensure we always have a valid JSON object (not empty)
        if (empty($functionMap)) {
            $functionMap = ['report' => true];
        }

        $teamFunction->function = $functionMap;
        $teamFunction->save();

        $this->loadData();
    }
}; ?>

<div>
    <x-fancy-header title="Team Functions" subtitle="Assign teams to platform functions" :breadcrumbs="[
        ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
        ['label' => 'Settings', 'url' => route('admin.dashboard.settings.index', request()->query())],
        ['label' => 'Team Functions']
    ]" class="mb-4">
    </x-fancy-header>

    <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-6">
        Use this page to grant or remove team access across all platform functions for the current chapter.
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @foreach ($functions as $func)
            @php
                $enabledIds = $enabledByFunction[$func['key']] ?? [];
                $enabledTeams = collect($teams)->filter(fn($t) => in_array($t['id'], $enabledIds));
                $availableTeams = collect($teams)->reject(fn($t) => in_array($t['id'], $enabledIds));
            @endphp
            <x-card header="{{ $func['label'] }}" class="dark:bg-zinc-900 dark:text-gray-200 text-zinc-900">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="text-sm font-semibold mb-2 text-zinc-700 dark:text-zinc-300">
                            Assigned Teams <span class="float-right">{{ $enabledTeams->count() }}</span>
                        </div>
                        <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                            @forelse ($enabledTeams as $team)
                                <div class="flex items-center justify-between rounded-lg border border-zinc-200 dark:border-zinc-700 p-2">
                                    <span>{{ $team['name'] }}</span>
                                    <button wire:click="toggleTeam('{{ $func['key'] }}', {{ $team['id'] }})"
                                        class="px-3 py-1 text-xs rounded-lg bg-red-500 text-white hover:bg-red-600">
                                        Remove
                                    </button>
                                </div>
                            @empty
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">No teams assigned.</div>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-semibold mb-2 text-zinc-700 dark:text-zinc-300">
                            Available Teams <span class="float-right">{{ $availableTeams->count() }}</span>
                        </div>
                        <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                            @forelse ($availableTeams as $team)
                                <div class="flex items-center justify-between rounded-lg border border-zinc-200 dark:border-zinc-700 p-2">
                                    <span>{{ $team['name'] }}</span>
                                    <button wire:click="toggleTeam('{{ $func['key'] }}', {{ $team['id'] }})"
                                        class="px-3 py-1 text-xs rounded-lg bg-green-500 text-white hover:bg-green-600">
                                        Add
                                    </button>
                                </div>
                            @empty
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">No available teams.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </x-card>
        @endforeach
    </div>
</div>
