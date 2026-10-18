<?php

namespace App\Services;

use App\Models\AcademicClass;
use App\Models\Course;
use App\Models\Exam;
use App\Models\Faculty;
use App\Models\Formation;
use App\Models\Level;
use App\Models\Seance;
use App\Models\Student;
use App\Models\StudentGroup;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class DepartmentScope
{
    public function query(Builder|Relation $query, string $entity, User $user): Builder
    {
        if ($query instanceof Relation) {
            $query = $query->getQuery();
        }

        if (! $user->hasRole('department_manager')) {
            return $query;
        }

        abort_unless($user->department_id, 403, 'Votre compte n’est associé à aucun département.');

        return match ($entity) {
            'departments' => $query->whereKey($user->department_id),
            'faculties' => $query->whereHas('departments', fn (Builder $relation) => $relation->whereKey($user->department_id)),
            'formations' => $query->where('department_id', $user->department_id),
            'levels' => $query->whereHas('formation', fn (Builder $relation) => $relation->where('department_id', $user->department_id)),
            'classes' => $query->whereHas('formation', fn (Builder $relation) => $relation->where('department_id', $user->department_id)),
            'groups' => $query->whereHas('academicClass.formation', fn (Builder $relation) => $relation->where('department_id', $user->department_id)),
            'students' => $query->where(function (Builder $students) use ($user) {
                $students->whereHas('academicClass.formation', fn (Builder $formation) => $formation->where('department_id', $user->department_id))
                    ->orWhereHas('group.academicClass.formation', fn (Builder $formation) => $formation->where('department_id', $user->department_id));
            }),
            'teachers' => $query->where('department_id', $user->department_id),
            'courses' => $query->where(function (Builder $courses) use ($user) {
                $courses->where('department_id', $user->department_id)
                    ->orWhereHas('formation', fn (Builder $formation) => $formation->where('department_id', $user->department_id));
            }),
            'seances', 'exams' => $query->whereHas('course', function (Builder $course) use ($user) {
                $course->where('department_id', $user->department_id)
                    ->orWhereHas('formation', fn (Builder $formation) => $formation->where('department_id', $user->department_id));
            }),
            default => $query,
        };
    }

    public function assertModel(Model $model, string $entity, User $user): void
    {
        abort_unless(
            $this->query($model::query(), $entity, $user)->whereKey($model->getKey())->exists(),
            404
        );
    }

    public function assertRelated(string $entity, int|string|null $id, User $user): void
    {
        if ($id === null || ! $user->hasRole('department_manager')) {
            return;
        }

        $query = match ($entity) {
            'formation' => Formation::query(),
            'class' => AcademicClass::query(),
            'group' => StudentGroup::query(),
            'level' => Level::query(),
            'department' => \App\Models\Department::query(),
            'faculty' => Faculty::query(),
            'teacher' => Teacher::query(),
            'course' => Course::query(),
            default => null,
        };

        if ($query) {
            abort_unless($this->query($query, match ($entity) {
                'formation' => 'formations',
                'class' => 'classes',
                'group' => 'groups',
                'level' => 'levels',
                'department' => 'departments',
                'faculty' => 'faculties',
                'teacher' => 'teachers',
                'course' => 'courses',
            }, $user)->whereKey($id)->exists(), 403, 'Cette donnée n’appartient pas à votre département.');
        }
    }
}
