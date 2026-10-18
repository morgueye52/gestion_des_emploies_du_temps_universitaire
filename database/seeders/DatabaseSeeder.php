<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = [
            'super_admin' => 'Super Administrateur',
            'director_general' => 'Directeur Général',
            'academic_direction' => 'Direction Académique',
            'studies_manager' => 'Responsable des Études',
            'department_manager' => 'Responsable de Département',
            'registrar' => 'Administrateur Scolarité',
            'hr_admin' => 'Administrateur RH',
            'it_admin' => 'Administrateur Informatique',
            'teacher' => 'Enseignant',
            'student' => 'Étudiant',
        ];

        foreach ($roles as $name => $label) {
            \App\Models\Role::updateOrCreate(['name' => $name], ['label' => $label]);
        }

        $permissions = [
            'academic_structure.manage' => 'Gérer la structure académique',
            'students.manage' => 'Gérer les étudiants',
            'teachers.manage' => 'Gérer les enseignants',
            'courses.manage' => 'Gérer les cours',
            'rooms.manage' => 'Gérer les salles',
            'timetables.manage' => 'Gérer les emplois du temps',
            'exams.manage' => 'Gérer les examens',
            'reports.view' => 'Consulter les rapports',
        ];

        foreach ($permissions as $name => $label) {
            \App\Models\Permission::updateOrCreate(['name' => $name], ['label' => $label]);
        }

        $administratorPermissions = \App\Models\Permission::whereIn('name', array_keys($permissions))
            ->pluck('id');

        foreach (array_slice(array_keys($roles), 0, 8) as $roleName) {
            \App\Models\Role::where('name', $roleName)->firstOrFail()
                ->permissions()->syncWithoutDetaching($administratorPermissions);
        }

    }
}
