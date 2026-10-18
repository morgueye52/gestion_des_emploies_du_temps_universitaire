<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200)
        ->assertSee('auth-page')
        ->assertSee('Identifiant ou adresse électronique');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('student.dashboard'));
});

test('users can sign in with their account login', function () {
    $user = User::factory()->create([
        'login' => 'professeur.test',
        'role' => 'teacher',
    ]);

    $response = $this->post('/login', [
        'email' => 'professeur.test',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('teacher.dashboard'));
});

test('administrators are sent directly to the administration dashboard', function () {
    $administrator = User::factory()->create(['role' => 'super_admin']);

    $this->post('/login', [
        'email' => $administrator->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard'));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
