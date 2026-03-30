<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\SchedulerController;

Volt::route('/', 'home.landing')->name('home');

// Home Auth Routes (for regular users)
Route::middleware('guest')->group(function () {
    Volt::route('/home/login', 'home.auth.login')->name('home.login');
    Volt::route('/home/password/request', 'home.auth.forgot-password')->name('home.password.request');
    Volt::route('/home/password/reset/{token}', 'home.auth.reset-password')->name('password.reset');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');
    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
    Volt::route('profile', 'profile.index')->name('profile.index');
});

Volt::route('appointments', 'home.appointment')->name('appointment');
Volt::route('prayer_request', 'home.prayers.request')->name('prayer.request');
Volt::route('sermons', 'home.sermons.index')->name('sermons.index');
Volt::route('sermons/series', 'home.sermons.series')->name('sermons.series');
Volt::route('sermons/series/{id}', 'home.sermons.series-detail')->name('sermons.series-detail');
Volt::route('about', 'home.about.index')->name('about');
Volt::route('conclaves', 'home.conclaves.index')->name('conclaves.index');
Volt::route('testimonies', 'home.testimonies.index')->name('testimonies.index');
Volt::route('missions', 'home.missions.index')->name('missions.index');
Volt::route('cells', 'home.cells.index')->name('cells.index');
Volt::route('location', 'home.location.index')->name('location.index');
//-----------------------------------------------------------------------------------
//BELIEVERS ACADEMY ROUTE
//------------------------------------------------------------------------------------
Volt::route('believers_academy', 'home.believers.index')->name('believers.academy');
Volt::route('believers_academy/register', 'home.believers.register')->name('believers_academy.register');
Volt::route('believers_academy/dashboard', 'home.believers.dashboard')->name('believers_academy.dashboard');
//-----------------------------------------------------------------------------------
//CERTIFICATE ROUTES
//-----------------------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::get('certificate/form', [CertificateController::class, 'showForm'])->name('certificate.form');
    Route::get('certificate/generate', [CertificateController::class, 'generateCertificate'])->name('certificate.generate');
});
//-----------------------------------------------------------------------------------
//pARTNERSHIP ROUTES
//------------------------------------------------------------------------------------
Volt::route('partnership', 'home.partnership.index')->name('home.partnership.index');
Volt::route('giving', 'home.giving.index')->name('giving.index');
Volt::route('attendance', 'home.attendance.index')->name('attendance.index');
//------------------------------------------------------------------------------------
//EVENT ROUTES
//====================================================================================
// Event Index
Volt::route('/events', 'home.events.index')
    ->name('events.index');

// Event Registration (takes event slug or id)
Volt::route('/events/{event_id}/register', 'home.event.register')
    ->name('events.register');

// Event Gallery (takes event slug or id)
Volt::route('/events/{event}/gallery', 'home.events.gallery')
    ->name('events.gallery');

//------------------------------------------------------------------------------------
//TRANSPORT ROUTES
//------------------------------------------------------------------------------------
Volt::route('/transport', 'home.transport')->name('transport');
Route::post('/transport/pickup-request', [
    \App\Http\Controllers\TransportController::class,
    'store'
])->name('transport.store');

include __DIR__ .'/super_admin_route.php';

include __DIR__ .'/admin_route.php';

Route::get('/internal/scheduler/tick', [SchedulerController::class, 'tick'])
    ->name('scheduler.tick');

require __DIR__ . '/auth.php';
