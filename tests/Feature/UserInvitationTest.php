<?php

use App\Models\Teacher;
use App\Models\Department;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Support\Str;

test('a super admin can create their first account through the interactive setup command', function () {
    $this->artisan('university:create-super-admin')
        ->expectsQuestion('Prénom', 'Léa')
        ->expectsQuestion('Nom', 'Martin')
        ->expectsQuestion('Adresse électronique', 'lea.martin@example.com')
        ->expectsQuestion('Identifiant de connexion', 'lea.martin')
        ->expectsQuestion('Choisissez un mot de passe (8 caractères minimum)', 'Secure-password-2026')
        ->expectsConfirmation('Confirmez-vous la création de ce compte Super Admin ?', 'yes')
        ->expectsOutputToContain('Compte Super Admin créé')
        ->assertExitCode(0);

    $administrator = User::where('email', 'lea.martin@example.com')->firstOrFail();
    expect($administrator->role)->toBe('super_admin')
        ->and($administrator->login)->toBe('lea.martin')
        ->and($administrator->email_verified_at)->not->toBeNull()
        ->and($administrator->is_active)->toBeTrue();
});

test('the first super admin cannot be created again by the setup command', function () {
    User::factory()->create(['role' => 'super_admin']);

    $this->artisan('university:create-super-admin')
        ->expectsOutputToContain('Un compte Super Admin existe déjà')
        ->assertExitCode(1);
});

test('only the super admin can create personnel invitations', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->get(route('admin.invitations.index'))
        ->assertForbidden();

    $administrator = User::factory()->create(['role' => 'super_admin']);

    $response = $this->actingAs($administrator)
        ->post(route('admin.invitations.store'), [
            'email' => 'direction@example.com',
            'role' => 'director_general',
        ])
        ->assertRedirect(route('admin.invitations.index'))
        ->assertSessionHas('invitation_url');

    $invitation = UserInvitation::where('email', 'direction@example.com')->firstOrFail();
    $invitationToken = basename(parse_url($response->getSession()->get('invitation_url'), PHP_URL_PATH));
    expect($invitation->token_hash)->toBe(hash('sha256', $invitationToken))
        ->and($invitation->role)->toBe('director_general')
        ->and($invitation->expires_at->isFuture())->toBeTrue();
});

test('department manager invitations require and preserve their assigned department', function () {
    $administrator = User::factory()->create(['role' => 'super_admin']);
    $department = Department::create([
        'name' => 'Département informatique',
        'code' => 'INFO',
        'is_active' => true,
    ]);

    $this->actingAs($administrator)
        ->post(route('admin.invitations.store'), [
            'email' => 'chef.info@example.com',
            'role' => 'department_manager',
        ])
        ->assertSessionHasErrors('department_id');

    $response = $this->post(route('admin.invitations.store'), [
        'email' => 'chef.info@example.com',
        'role' => 'department_manager',
        'department_id' => $department->id,
    ])->assertRedirect(route('admin.invitations.index'));

    $invitation = UserInvitation::where('email', 'chef.info@example.com')->firstOrFail();
    expect($invitation->department_id)->toBe($department->id);

    $token = basename(parse_url($response->getSession()->get('invitation_url'), PHP_URL_PATH));
    auth()->logout();

    $this->post(route('invitations.register', $token), [
        'prenom' => 'Awa',
        'name' => 'Diop',
        'password' => 'Secure-password-2026',
        'password_confirmation' => 'Secure-password-2026',
    ])->assertRedirect(route('admin.dashboard'));

    $manager = User::where('email', 'chef.info@example.com')->firstOrFail();
    expect($manager->role)->toBe('department_manager')
        ->and($manager->department_id)->toBe($department->id);
});

test('an invited staff member creates their own account with the invited role', function () {
    $token = Str::random(64);
    $invitation = UserInvitation::create([
        'email' => 'dg@example.com',
        'role' => 'director_general',
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addDays(7),
    ]);

    $this->post(route('invitations.register', $token), [
        'prenom' => 'Aminata',
        'name' => 'Ndiaye',
        'login' => 'aminata_ndiaye',
        'password' => 'Secure-password-2026',
        'password_confirmation' => 'Secure-password-2026',
    ])->assertRedirect(route('admin.dashboard'));

    $user = User::where('email', 'dg@example.com')->firstOrFail();
    expect($user->role)->toBe('director_general')
        ->and($user->prenom)->toBe('Aminata')
        ->and($user->name)->toBe('Ndiaye')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($invitation->fresh()->accepted_at)->not->toBeNull();
});

test('an invitation link can be opened and completed while the super admin is signed in', function () {
    $administrator = User::factory()->create(['role' => 'super_admin']);
    $token = Str::random(64);
    $invitation = UserInvitation::create([
        'email' => 'new.director@example.com',
        'role' => 'director_general',
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addDays(7),
    ]);

    $this->actingAs($administrator)
        ->get(route('invitations.accept', $token))
        ->assertOk()
        ->assertSee('Finalisez la création de votre compte.')
        ->assertSee('new.director@example.com');

    $this->post(route('invitations.register', $token), [
        'prenom' => 'Mariam',
        'name' => 'Diallo',
        'password' => 'Secure-password-2026',
        'password_confirmation' => 'Secure-password-2026',
    ])->assertRedirect(route('admin.dashboard'));

    $invitedUser = User::where('email', 'new.director@example.com')->firstOrFail();
    expect($invitation->fresh()->accepted_at)->not->toBeNull();
    $this->assertAuthenticatedAs($invitedUser);
    expect(auth()->id())->not->toBe($administrator->id);
});

test('accepting a teacher invitation also creates the linked teacher profile', function () {
    $token = Str::random(64);
    UserInvitation::create([
        'email' => 'teacher@example.com',
        'role' => 'teacher',
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addDays(7),
    ]);

    $this->post(route('invitations.register', $token), [
        'prenom' => 'Moussa',
        'name' => 'Ba',
        'password' => 'Secure-password-2026',
        'password_confirmation' => 'Secure-password-2026',
    ])->assertRedirect(route('teacher.dashboard'));

    $teacher = Teacher::where('email', 'teacher@example.com')->firstOrFail();
    expect($teacher->user->role)->toBe('teacher')
        ->and($teacher->first_name)->toBe('Moussa');
});

test('expired or used invitations cannot create accounts', function () {
    $token = Str::random(64);
    $invitation = UserInvitation::create([
        'email' => 'expired@example.com',
        'role' => 'it_admin',
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->subMinute(),
    ]);

    $this->get(route('invitations.accept', $token))->assertNotFound();

    $invitation->update(['expires_at' => now()->addDay(), 'accepted_at' => now()]);
    $this->get(route('invitations.accept', $token))->assertNotFound();

    expect(User::where('email', 'expired@example.com')->exists())->toBeFalse();
});
