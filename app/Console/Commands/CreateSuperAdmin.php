<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateSuperAdmin extends Command
{
    protected $signature = 'university:create-super-admin';

    protected $description = 'Créer le premier compte Super Admin de l’université';

    public function handle(): int
    {
        if (User::query()->where('role', 'super_admin')->exists()) {
            $this->components->error('Un compte Super Admin existe déjà. Utilisez les invitations depuis l’administration.');

            return self::FAILURE;
        }

        $this->components->info('Création du premier compte Super Admin');
        $prenom = trim($this->ask('Prénom'));
        $name = trim($this->ask('Nom'));
        $email = mb_strtolower(trim($this->ask('Adresse électronique')));
        $login = trim($this->ask('Identifiant de connexion', str($email)->before('@')->toString()));

        $validator = Validator::make(
            compact('prenom', 'name', 'email', 'login'),
            [
                'prenom' => ['required', 'string', 'max:255'],
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'login' => ['required', 'string', 'max:255', 'unique:users,login'],
            ],
            attributes: [
                'prenom' => 'prénom',
                'name' => 'nom',
                'email' => 'adresse électronique',
                'login' => 'identifiant',
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $password = $this->secret('Choisissez un mot de passe (8 caractères minimum)');
        $passwordValidator = Validator::make(
            ['password' => $password],
            ['password' => ['required', Password::min(8)]],
            attributes: ['password' => 'mot de passe'],
        );

        if ($passwordValidator->fails()) {
            foreach ($passwordValidator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        if (! $this->confirm('Confirmez-vous la création de ce compte Super Admin ?', true)) {
            $this->components->warn('Création annulée.');

            return self::SUCCESS;
        }

        if ($this->call('db:seed') !== self::SUCCESS) {
            $this->components->error('Impossible de préparer les rôles. Aucun compte administrateur n’a été créé.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($prenom, $name, $email, $login, $password) {
            User::create([
                'prenom' => $prenom,
                'name' => $name,
                'email' => $email,
                'login' => $login,
                'password' => $password,
                'role' => 'super_admin',
                'email_verified_at' => now(),
                'is_active' => true,
            ]);
        });

        $this->components->info('Compte Super Admin créé. Connectez-vous avec votre adresse électronique ou votre identifiant.');

        return self::SUCCESS;
    }
}
