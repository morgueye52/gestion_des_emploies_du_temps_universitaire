<?php

namespace App\Services;

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\Exam;
use App\Models\Faculty;
use App\Models\Formation;
use App\Models\Level;
use App\Models\Seance;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGroup;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AcademicCatalogService
{
    private const ENTITIES = [
        'faculties' => [
            'model' => Faculty::class,
            'title' => 'Facultés',
            'singular' => 'Faculté',
            'fields' => ['name', 'code', 'description', 'is_active'],
            'validation' => [
                'name' => ['required', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:64'],
                'description' => ['nullable', 'string'],
                'is_active' => ['required', 'boolean'],
            ],
            'options' => [],
        ],
        'departments' => [
            'model' => Department::class,
            'title' => 'Départements',
            'singular' => 'Département',
            'fields' => ['faculty_id', 'name', 'code', 'description', 'is_active'],
            'validation' => [
                'faculty_id' => ['nullable', 'exists:faculties,id'],
                'name' => ['required', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:64'],
                'description' => ['nullable', 'string'],
                'is_active' => ['required', 'boolean'],
            ],
            'options' => ['faculty_id' => [Faculty::class, 'name']],
        ],
        'formations' => [
            'model' => Formation::class,
            'title' => 'Formations',
            'singular' => 'Formation',
            'fields' => ['department_id', 'name', 'code', 'description', 'is_active'],
            'validation' => [
                'department_id' => ['required', 'exists:departments,id'],
                'name' => ['required', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:64'],
                'description' => ['nullable', 'string'],
                'is_active' => ['required', 'boolean'],
            ],
            'options' => ['department_id' => [Department::class, 'name']],
        ],
        'levels' => [
            'model' => Level::class,
            'title' => 'Niveaux',
            'singular' => 'Niveau',
            'fields' => ['formation_id', 'name', 'position'],
            'validation' => [
                'formation_id' => ['required', 'exists:formations,id'],
                'name' => ['required', 'string', 'max:100'],
                'position' => ['required', 'integer', 'min:1', 'max:20'],
            ],
            'options' => ['formation_id' => [Formation::class, 'name']],
        ],
        'academic-years' => [
            'model' => AcademicYear::class,
            'title' => 'Années universitaires',
            'singular' => 'Année universitaire',
            'fields' => ['name', 'starts_on', 'ends_on', 'is_active'],
            'validation' => [
                'name' => ['required', 'string', 'max:32'],
                'starts_on' => ['required', 'date'],
                'ends_on' => ['required', 'date', 'after:starts_on'],
                'is_active' => ['required', 'boolean'],
            ],
            'options' => [],
        ],
        'semesters' => [
            'model' => Semester::class,
            'title' => 'Semestres',
            'singular' => 'Semestre',
            'fields' => ['academic_year_id', 'name', 'starts_on', 'ends_on', 'is_active'],
            'validation' => [
                'academic_year_id' => ['required', 'exists:academic_years,id'],
                'name' => ['required', 'string', 'max:100'],
                'starts_on' => ['required', 'date'],
                'ends_on' => ['required', 'date', 'after:starts_on'],
                'is_active' => ['required', 'boolean'],
            ],
            'options' => ['academic_year_id' => [AcademicYear::class, 'name']],
        ],
        'classes' => [
            'model' => AcademicClass::class,
            'title' => 'Classes',
            'singular' => 'Classe',
            'fields' => ['formation_id', 'level_id', 'name', 'code', 'capacity', 'is_active'],
            'validation' => [
                'formation_id' => ['required', 'exists:formations,id'],
                'level_id' => ['nullable', 'exists:levels,id'],
                'name' => ['required', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:64'],
                'capacity' => ['required', 'integer', 'min:0', 'max:10000'],
                'is_active' => ['required', 'boolean'],
            ],
            'options' => ['formation_id' => [Formation::class, 'name'], 'level_id' => [Level::class, 'name']],
        ],
        'groups' => [
            'model' => StudentGroup::class,
            'title' => 'Groupes',
            'singular' => 'Groupe',
            'fields' => ['academic_class_id', 'name', 'code', 'capacity'],
            'validation' => [
                'academic_class_id' => ['required', 'exists:academic_classes,id'],
                'name' => ['required', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:64'],
                'capacity' => ['required', 'integer', 'min:0', 'max:10000'],
            ],
            'options' => ['academic_class_id' => [AcademicClass::class, 'name']],
        ],
    ];

    public function entities(): array
    {
        return self::ENTITIES;
    }

    public function definition(string $entity): array
    {
        abort_unless(isset(self::ENTITIES[$entity]), 404);

        return self::ENTITIES[$entity];
    }

    public function query(string $entity, ?User $user = null, ?DepartmentScope $scope = null)
    {
        $definition = $this->definition($entity);
        $query = $definition['model']::query();

        if ($user && $scope) {
            $query = $scope->query($query, $entity, $user);
        }

        return $query->orderBy('id', 'desc');
    }

    public function find(string $entity, int $id, ?User $user = null, ?DepartmentScope $scope = null): Model
    {
        $definition = $this->definition($entity);
        $query = $definition['model']::query();

        if ($user && $scope) {
            $query = $scope->query($query, $entity, $user);
        }

        return $query->findOrFail($id);
    }

    public function validate(string $entity, array $input, ?Model $record = null, ?User $user = null, ?DepartmentScope $scope = null): array
    {
        if ($user?->hasRole('department_manager') && $entity === 'formations') {
            $input['department_id'] = $user->department_id;
        }

        $definition = $this->definition($entity);
        $rules = $definition['validation'];
        $uniqueColumns = match ($entity) {
            'faculties', 'departments', 'formations' => ['code'],
            'classes' => ['code'],
            'academic-years' => ['name'],
            'levels', 'semesters', 'groups' => [],
            default => [],
        };

        foreach ($uniqueColumns as $column) {
            $rules[$column][] = Rule::unique($record?->getTable() ?? (new $definition['model'])->getTable(), $column)
                ->ignore($record?->getKey());
        }

        if ($entity === 'levels') {
            $rules['name'][] = Rule::unique('levels', 'name')
                ->where('formation_id', $input['formation_id'] ?? null)
                ->ignore($record?->getKey());
        }

        if ($entity === 'semesters') {
            $rules['name'][] = Rule::unique('semesters', 'name')
                ->where('academic_year_id', $input['academic_year_id'] ?? null)
                ->ignore($record?->getKey());
        }

        if ($entity === 'groups') {
            $rules['code'][] = Rule::unique('groups', 'code')
                ->where('academic_class_id', $input['academic_class_id'] ?? null)
                ->ignore($record?->getKey());
        }

        $validated = validator($input, $rules)->validate();

        if ($user && $scope) {
            $relatedEntities = [
                'faculty_id' => 'departments',
                'department_id' => 'departments',
                'formation_id' => 'formations',
                'level_id' => 'levels',
                'academic_class_id' => 'classes',
            ];

            foreach ($relatedEntities as $field => $relatedEntity) {
                if (isset($validated[$field])) {
                    $scope->assertRelated($relatedEntity === 'departments' && $field === 'faculty_id' ? 'faculty' : match ($relatedEntity) {
                        'departments' => 'department',
                        'formations' => 'formation',
                        'levels' => 'level',
                        'classes' => 'class',
                    }, $validated[$field], $user);
                }
            }
        }

        return $validated;
    }

    public function options(string $entity, ?User $user = null): array
    {
        $definition = $this->definition($entity);
        $options = [];

        foreach ($definition['options'] as $field => [$model, $labelColumn]) {
            $query = $model::query();

            if (Schema::hasColumn($query->getModel()->getTable(), 'is_active')) {
                $query = $query->where('is_active', true);
            }

            if ($user?->hasRole('department_manager')) {
                $query = match ($field) {
                    'department_id' => $query->whereKey($user->department_id),
                    'formation_id' => $query->where('department_id', $user->department_id),
                    'level_id' => $query->whereHas('formation', fn ($formations) => $formations->where('department_id', $user->department_id)),
                    'academic_class_id' => $query->whereHas('formation', fn ($formations) => $formations->where('department_id', $user->department_id)),
                    default => $query,
                };
            }
            $options[$field] = $query->orderBy($labelColumn)->get(['id', $labelColumn]);
        }

        return $options;
    }

    public function deletionBlocker(string $entity, Model $record): ?string
    {
        $hasRelatedRecords = match ($entity) {
            'faculties' => Department::where('faculty_id', $record->getKey())->exists(),
            'departments' => Formation::where('department_id', $record->getKey())->exists()
                || Teacher::where('department_id', $record->getKey())->exists()
                || Course::where('department_id', $record->getKey())->exists(),
            'formations' => AcademicClass::where('formation_id', $record->getKey())->exists()
                || Course::where('formation_id', $record->getKey())->exists(),
            'levels' => AcademicClass::where('level_id', $record->getKey())->exists(),
            'academic-years' => Semester::where('academic_year_id', $record->getKey())->exists(),
            'semesters' => Course::where('semester_id', $record->getKey())->exists()
                || Seance::where('semester_id', $record->getKey())->exists(),
            'classes' => Student::where('academic_class_id', $record->getKey())->exists()
                || StudentGroup::where('academic_class_id', $record->getKey())->exists(),
            'groups' => Student::where('group_id', $record->getKey())->exists()
                || Seance::where('group_id', $record->getKey())->exists()
                || Exam::where('group_id', $record->getKey())->exists(),
            default => false,
        };

        return $hasRelatedRecords
            ? 'Cet élément ne peut pas être supprimé tant que des données académiques y sont rattachées.'
            : null;
    }
}
