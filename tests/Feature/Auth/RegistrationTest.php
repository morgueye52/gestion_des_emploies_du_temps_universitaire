<?php

use App\Models\Student;
use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200)
        ->assertSee('auth-page')
        ->assertSee('Créez un compte étudiant');
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'prenom' => 'Test',
        'email' => 'test@example.com',
        'role' => 'super_admin',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('student.dashboard'));
    $student = Student::where('user_id', User::where('email', 'test@example.com')->value('id'))->first();
    expect($student)->not->toBeNull()
        ->and($student->first_name)->toBe('Test')
        ->and($student->academic_class_id)->toBeNull()
        ->and($student->status)->toBe('active')
        ->and($student->user->role)->toBe('student');

    $student->user->forceFill(['email_verified_at' => now()])->save();

    $this->actingAs($student->user->fresh())
        ->get(route('student.enrollments.index'))
        ->assertOk()
        ->assertSee('aucune classe ne vous a encore été affectée')
        ->assertSee('Le service de scolarité doit vous rattacher à une classe active');
});
