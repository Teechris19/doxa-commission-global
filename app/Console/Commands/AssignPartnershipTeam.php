<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Team;
use App\Models\Chapter;
use App\Models\TeamFunction;

class AssignPartnershipTeam extends Command
{
    protected $signature = 'partnerships:assign-team {--team=} {--chapter=}';
    protected $description = 'Assign a team to handle partnerships function';

    public function handle(): int
    {
        $this->info('=== Available Chapters ===');
        Chapter::all(['id', 'name'])->each(function($c) {
            $this->line("{$c->id}: {$c->name}");
        });

        $chapterId = $this->option('chapter') ?? $this->ask('Enter Chapter ID');
        
        if (!$chapterId) {
            $this->error('Chapter ID is required');
            return self::FAILURE;
        }

        $this->info(PHP_EOL . "=== Teams in Chapter {$chapterId} ===");
        $teams = Team::where('chapter_id', $chapterId)->get(['id', 'name']);
        
        if ($teams->isEmpty()) {
            $this->error('No teams found for this chapter');
            return self::FAILURE;
        }

        $teams->each(function($t) {
            $this->line("{$t->id}: {$t->name}");
        });

        $teamId = $this->option('team') ?? $this->ask('Enter Team ID to assign to Partnerships');
        
        if (!$teamId) {
            $this->error('Team ID is required');
            return self::FAILURE;
        }

        $team = Team::find($teamId);
        if (!$team) {
            $this->error('Invalid Team ID!');
            return self::FAILURE;
        }

        if ($team->chapter_id != $chapterId) {
            $this->error('This team does not belong to the selected chapter!');
            return self::FAILURE;
        }

        $teamFunction = TeamFunction::firstOrCreate(
            ['team_id' => $teamId],
            ['function' => []]
        );

        $functionMap = $teamFunction->function ?? [];
        
        if (!empty($functionMap['partnerships'])) {
            $this->warn("Team '{$team->name}' is already assigned to Partnerships!");
            return self::SUCCESS;
        }
        
        $functionMap['partnerships'] = true;
        $teamFunction->function = $functionMap;
        $teamFunction->save();

        $this->info(PHP_EOL . "✓ Team '{$team->name}' has been assigned to handle Partnerships!");
        $this->info(PHP_EOL . "Current functions for this team:");
        foreach ($functionMap as $key => $value) {
            if ($value) $this->line("  - {$key}");
        }

        return self::SUCCESS;
    }
}
