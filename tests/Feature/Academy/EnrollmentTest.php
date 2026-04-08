<?php

use App\Events\StudentRegisteredToAcademy;
use App\Models\{BelieversAcademy, BelieversAcademyTeams, Chapter, StudentClasses, Team, TeamUser, User};
use Livewire\Volt\Volt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Create chapter
    $this->chapter = Chapter::factory()->create();

    // Create team with team lead
    $this->team = Team::factory()->create(['chapter_id' => $this->chapter->id]);
    $this->teamLead = User::factory()->create(['chapter_id' => $this->chapter->id]);
    TeamUser::create([
        'user_id' => $this->teamLead->id,
        'team_id' => $this->team->id,
        'chapter_id' => $this->chapter->id,
        'role_in_team' => 'team_lead',
    ]);

    // Create academy
    $this->academy = BelieversAcademy::factory()->create([
        'chapter_id' => $this->chapter->id,
        'status' => 'open',
        'start_at' => now()->subDays(1),
    ]);

    // Link team to academy
    BelieversAcademyTeams::create([
        'academy_id' => $this->academy->id,
        'team_id' => $this->team->id,
        'chapter_id' => $this->chapter->id,
    ]);
});

test('student can view academy registration form', function () {
    $response = $this->get(route('home.believers.register', ['chapter' => $this->chapter->name]));

    $response->assertStatus(200)
        ->assertSee('Believers Academy Registration');
});

test('student can register for academy', function () {
    Event::fake();

    $student = User::factory()->create(['chapter_id' => $this->chapter->id]);

    $response = Volt::test('home.believers.register', [
        'chapter' => $this->chapter->name,
    ])
        ->actingAs($student)
        ->set('selectedChapter', $this->chapter->id)
        ->set('howDidYouKnowAboutUs', 'friendsAndFamily')
        ->set('interest', 'Bible study')
        ->set('number', '+234 801 234 5678')
        ->call('register');

    $response->assertHasNoErrors();

    // Verify student class was created
    expect(StudentClasses::where('user_id', $student->id)->count())->toBe(1);

    $studentClass = StudentClasses::where('user_id', $student->id)->first();
    expect($studentClass->academy_id)->toBe($this->academy->id);
    expect($studentClass->status)->toBe('started');

    // Event should be dispatched
    Event::assertDispatched(StudentRegisteredToAcademy::class);
});

test('new student receives enrollment confirmation email', function () {
    Notification::fake();

    $student = User::factory()->create(['chapter_id' => $this->chapter->id]);

    Volt::test('home.believers.register', [
        'chapter' => $this->chapter->name,
    ])
        ->actingAs($student)
        ->set('selectedChapter', $this->chapter->id)
        ->set('howDidYouKnowAboutUs', 'friendsAndFamily')
        ->set('interest', 'Bible study')
        ->set('number', '+234 801 234 5678')
        ->call('register');

    Notification::assertSentTo(
        $student,
        \App\Notifications\StudentEnrolledNotification::class
    );
});

test('student cannot register twice for same academy', function () {
    $student = User::factory()->create(['chapter_id' => $this->chapter->id]);

    // First registration
    StudentClasses::create([
        'user_id' => $student->id,
        'academy_id' => $this->academy->id,
        'status' => 'started',
        'class_completed' => json_encode([]),
    ]);

    // Second attempt
    $response = Volt::test('home.believers.register', [
        'chapter' => $this->chapter->name,
    ])
        ->actingAs($student)
        ->set('selectedChapter', $this->chapter->id)
        ->set('howDidYouKnowAboutUs', 'friendsAndFamily')
        ->set('interest', 'Bible study')
        ->set('number', '+234 801 234 5678')
        ->call('register');

    expect(StudentClasses::where('user_id', $student->id)->count())->toBe(1);
});

test('registration fails without required fields', function () {
    $student = User::factory()->create(['chapter_id' => $this->chapter->id]);

    $response = Volt::test('home.believers.register', [
        'chapter' => $this->chapter->name,
    ])
        ->actingAs($student)
        ->set('selectedChapter', $this->chapter->id)
        ->call('register');

    $response->assertHasErrors(['howDidYouKnowAboutUs', 'number']);
});

test('registration fails with invalid phone number', function () {
    $student = User::factory()->create(['chapter_id' => $this->chapter->id]);

    $response = Volt::test('home.believers.register', [
        'chapter' => $this->chapter->name,
    ])
        ->actingAs($student)
        ->set('selectedChapter', $this->chapter->id)
        ->set('howDidYouKnowAboutUs', 'friendsAndFamily')
        ->set('number', 'invalid')
        ->call('register');

    $response->assertHasErrors('number');
});

test('academy shows correct status messages', function () {
    $student = User::factory()->create(['chapter_id' => $this->chapter->id]);

    // Academy not yet opened
    $closedAcademy = BelieversAcademy::factory()->create([
        'chapter_id' => $this->chapter->id,
        'status' => 'open',
        'start_at' => now()->addDays(10),
    ]);

    BelieversAcademyTeams::create([
        'academy_id' => $closedAcademy->id,
        'team_id' => $this->team->id,
        'chapter_id' => $this->chapter->id,
    ]);

    $response = Volt::test('home.believers.register', [
        'chapter' => $this->chapter->name,
    ])
        ->actingAs($student)
        ->set('selectedChapter', $this->chapter->id);

    // Change academy in the test
    $component = $response->component();
    expect($component->statusType)->toBe('countdown');
});
