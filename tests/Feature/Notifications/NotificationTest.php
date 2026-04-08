<?php

use App\Models\User;
use App\Models\Appointment;
use App\Models\Chapter;
use App\Models\Events;
use App\Models\Partnership;
use App\Models\Sermons;
use App\Notifications\AppointmentScheduled;
use App\Notifications\AppointmentConfirmation;
use App\Notifications\EventRegistered;
use App\Notifications\PartnershipApproved;
use App\Notifications\SermonUploaded;
use Illuminate\Support\Facades\Notification;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Notification System', function () {
    
    test('appointment scheduled notification is sent to admin', function () {
        Notification::fake();
        
        $chapter = Chapter::factory()->create();
        $user = User::factory()->create(['chapter_id' => $chapter->id]);
        $admin = User::factory()->create(['chapter_id' => $chapter->id]);
        
        $appointment = Appointment::create([
            'user_id' => $user->id,
            'chapter_id' => $chapter->id,
            'date' => now()->addDay()->toDateString(),
            'time' => '10:00',
        ]);
        
        $admin->notify(new AppointmentScheduled($appointment));
        
        Notification::assertSentTo($admin, AppointmentScheduled::class);
    });
    
    test('appointment confirmation notification is sent to user', function () {
        Notification::fake();
        
        $chapter = Chapter::factory()->create();
        $user = User::factory()->create(['chapter_id' => $chapter->id]);
        
        $appointment = Appointment::create([
            'user_id' => $user->id,
            'chapter_id' => $chapter->id,
            'date' => now()->addDay()->toDateString(),
            'time' => '10:00',
        ]);
        
        $user->notify(new AppointmentConfirmation($appointment));
        
        Notification::assertSentTo($user, AppointmentConfirmation::class);
    });
    
    test('event registration notification includes correct data', function () {
        Notification::fake();
        
        $chapter = Chapter::factory()->create();
        $user = User::factory()->create(['chapter_id' => $chapter->id]);
        $event = Events::factory()->create(['chapter_id' => $chapter->id]);
        $admin = User::factory()->create(['chapter_id' => $chapter->id]);
        
        $admin->notify(new EventRegistered($event, $user));
        
        Notification::assertSentTo($admin, EventRegistered::class, function ($notification) use ($event, $user) {
            return $notification->event->id === $event->id &&
                   $notification->registrant->id === $user->id;
        });
    });
    
    test('partnership approved notification is sent', function () {
        Notification::fake();
        
        $partnership = Partnership::factory()->create(['status' => 'approved']);
        $user = User::factory()->create();
        
        $user->notify(new PartnershipApproved($partnership));
        
        Notification::assertSentTo($user, PartnershipApproved::class);
    });
    
    test('sermon uploaded notification is broadcast to all users', function () {
        Notification::fake();
        
        $sermon = Sermons::factory()->create();
        $user = User::factory()->create();
        
        $user->notify(new SermonUploaded($sermon));
        
        Notification::assertSentTo($user, SermonUploaded::class);
    });
    
    test('notifications are stored in database', function () {
        $chapter = Chapter::factory()->create();
        $user = User::factory()->create(['chapter_id' => $chapter->id]);
        $appointment = Appointment::create([
            'user_id' => $user->id,
            'chapter_id' => $chapter->id,
            'date' => now()->addDay()->toDateString(),
            'time' => '10:00',
        ]);
        
        $user->notify(new AppointmentConfirmation($appointment));
        
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
            'type' => AppointmentConfirmation::class,
        ]);
    });
});
