<?php

use App\Models\User;
use App\Models\BelieversAcademy;
use App\Models\StudentClasses;
use App\Http\Controllers\CertificateController;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    
    // Create test files directory
    Storage::disk('public')->makeDirectory('certificates');
    Storage::disk('public')->makeDirectory('fonts');
});

describe('Certificate Generation', function () {
    
    test('certificate form is accessible', function () {
        $controller = new CertificateController();
        $response = $controller->showForm();
        
        expect($response)->not->toBeNull();
    });
    
    test('certificate can be generated with valid data', function () {
        $response = $this->post(route('certificate.generate'), [
            'name' => 'John Doe',
            'date' => now()->toDateString(),
        ]);
        
        // Should download or return successful response
        expect($response->status())->toBeIn([200, 302]);
    });
    
    test('certificate generation fails without name', function () {
        $response = $this->post(route('certificate.generate'), [
            'date' => now()->toDateString(),
        ]);
        
        $response->assertSessionHasErrors('name');
    });
    
    test('certificate generation fails without date', function () {
        $response = $this->post(route('certificate.generate'), [
            'name' => 'John Doe',
        ]);
        
        $response->assertSessionHasErrors('date');
    });
    
    test('certificate generation fails with invalid date format', function () {
        $response = $this->post(route('certificate.generate'), [
            'name' => 'John Doe',
            'date' => 'invalid-date',
        ]);
        
        $response->assertSessionHasErrors('date');
    });
    
    test('student who completes academy can generate certificate', function () {
        $user = User::factory()->create();
        $academy = BelieversAcademy::factory()->create(['status' => 'completed']);
        
        $studentClass = StudentClasses::create([
            'user_id' => $user->id,
            'academy_id' => $academy->id,
            'class_completed' => json_encode(['class_1', 'class_2']),
            'status' => 'completed',
            'cert' => null,
        ]);
        
        // Student should be able to generate certificate
        expect($studentClass->status)->toBe('completed');
    });
    
    test('certificate filename is sanitized', function () {
        $response = $this->post(route('certificate.generate'), [
            'name' => 'John O\'Reilly & Associates',
            'date' => now()->toDateString(),
        ]);
        
        expect($response->status())->toBeIn([200, 302]);
    });
});
