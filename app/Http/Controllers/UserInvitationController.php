<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\User;
use App\Models\UserInvitation;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserInvitationController extends Controller
{
    private const INVITABLE_ROLES = [
        'super_admin' => 'Super Administrateur',
        'director_general' => 'Directeur Général',
        'academic_direction' => 'Direction Académique',
        'studies_manager' => 'Responsable des Études',
        'department_manager' => 'Responsable de Département',
        'registrar' => 'Administrateur Scolarité',
        'hr_admin' => 'Administrateur RH',
        'it_admin' => 'Administrateur Informatique',
        'teacher' => 'Enseignant',
    ];

    public function index(): View
    {
        $invitations = UserInvitation::query()
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->paginate(15);

        return view('admin.invitations.index', compact('invitations'));
    }

    public function create(): View
    {
        return view('admin.invitations.create', [
            'roles' => self::INVITABLE_ROLES,
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim($request->string('email')->toString())),
        ]);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys(self::INVITABLE_ROLES))],
            'department_id' => [
                Rule::requiredIf($request->input('role') === 'department_manager'),
                'nullable',
                'exists:departments,id',
            ],
        ]);

        $token = Str::random(64);
        $invitation = DB::transaction(function () use ($request, $validated, $token) {
            UserInvitation::query()
                ->where('email', $validated['email'])
                ->whereNull('accepted_at')
                ->delete();

            return UserInvitation::create([
                'invited_by' => $request->user()->id,
                'email' => Str::lower($validated['email']),
                'role' => $validated['role'],
                'department_id' => $validated['role'] === 'department_manager' ? $validated['department_id'] : null,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addDays(7),
            ]);
        });

        return redirect()
            ->route('admin.invitations.index')
            ->with('invitation_url', route('invitations.accept', ['token' => $token]))
            ->with('invitation_email', $invitation->email)
            ->with('success', 'Invitation créée. Transmettez le lien privé à la personne invitée avant son expiration.');
    }

    public function destroy(UserInvitation $invitation): RedirectResponse
    {
        $invitation->delete();

        return redirect()->route('admin.invitations.index')->with('success', 'Invitation révoquée.');
    }

    public function accept(string $token): View
    {
        $invitation = $this->findValidInvitation($token);

        return view('auth.accept-invitation', compact('invitation', 'token'));
    }

    public function register(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->findValidInvitation($token);

        $validated = $request->validate([
            'prenom' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'login' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:users,login'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($invitation, $validated, $token) {
            $lockedInvitation = UserInvitation::query()
                ->whereKey($invitation->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(
                $lockedInvitation->accepted_at || $lockedInvitation->expires_at->isPast()
                    || ! hash_equals($lockedInvitation->token_hash, hash('sha256', $token)),
                404
            );

            if (User::query()->where('email', $lockedInvitation->email)->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'Un compte utilise déjà cette adresse. Contactez le Super Admin.',
                ]);
            }

            $user = User::create([
                'prenom' => $validated['prenom'],
                'name' => $validated['name'],
                'email' => $lockedInvitation->email,
                'login' => ($validated['login'] ?? null) ?: $this->uniqueLogin($lockedInvitation->email),
                'password' => $validated['password'],
                'role' => $lockedInvitation->role,
                'department_id' => $lockedInvitation->department_id,
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            if ($user->hasRole('teacher')) {
                Teacher::create([
                    'user_id' => $user->id,
                    'first_name' => $validated['prenom'],
                    'last_name' => $validated['name'],
                    'email' => $lockedInvitation->email,
                ]);
            }

            $lockedInvitation->update(['accepted_at' => now()]);

            return $user;
        });

        Auth::login($user);

        return redirect()->route($user->dashboardRouteName());
    }

    private function findValidInvitation(string $token): UserInvitation
    {
        return UserInvitation::query()
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();
    }

    private function uniqueLogin(string $email): string
    {
        $base = Str::before($email, '@');
        $login = $base;

        while (User::query()->where('login', $login)->exists()) {
            $login = $base.'-'.Str::lower(Str::random(6));
        }

        return $login;
    }
}
