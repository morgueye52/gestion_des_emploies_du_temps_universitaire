<?php

namespace App\Services;

use App\Models\AcademicClass;
use App\Models\Exam;
use App\Models\Seance;
use App\Models\StudentGroup;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class TimetableService
{
    public function validateSession(array $data, ?Seance $ignore = null): void
    {
        $errors = [];

        if ($data['ends_at'] <= $data['starts_at']) {
            $errors['ends_at'] = 'L’heure de fin doit être postérieure à l’heure de début.';
        }

        $overlaps = Seance::query()
            ->when($ignore, fn (Builder $query) => $query->whereKeyNot($ignore->getKey()))
            ->where('starts_at', '<', $data['ends_at'])
            ->where('ends_at', '>', $data['starts_at'])
            ->where(function (Builder $query) use ($data) {
                if (! empty($data['session_date'])) {
                    $query->where('session_date', $data['session_date'])
                        ->orWhere(function (Builder $recurring) use ($data) {
                            $recurring->whereNull('session_date')
                                ->where('weekday', $data['weekday']);
                        });

                    return;
                }

                $query->where('weekday', $data['weekday']);
            });

        $conflicts = (clone $overlaps)->with('group')->get();

        if ($conflicts->contains(fn (Seance $session) => $session->room_id === (int) $data['room_id'])) {
            $errors['room_id'] = 'Conflit détecté : cette salle est déjà occupée.';
        }

        if (! empty($data['teacher_id']) && $conflicts->contains(
            fn (Seance $session) => $session->teacher_id === (int) $data['teacher_id']
        )) {
            $errors['teacher_id'] = 'Conflit détecté : cet enseignant possède déjà un cours à cette heure.';
        }

        if (! empty($data['group_id']) && $conflicts->contains(
            fn (Seance $session) => $session->group_id === (int) $data['group_id']
        )) {
            $errors['group_id'] = 'Conflit détecté : ce groupe possède déjà un cours sur ce créneau.';
        }

        $group = ! empty($data['group_id']) ? StudentGroup::find($data['group_id']) : null;
        $class = ! empty($data['academic_class_id']) ? AcademicClass::find($data['academic_class_id']) : null;
        $expectedStudents = $group
            ? ($group->capacity ?: $group->students()->count())
            : ($class?->capacity ?: $class?->students()->count() ?: (int) ($data['expected_students'] ?? 0));

        if ($expectedStudents > 0 && $expectedStudents > (int) $data['room_capacity']) {
            $errors['room_id'] = 'Conflit détecté : la capacité de cette salle est insuffisante.';
        }

        if (! empty($data['teacher_id'])) {
            $teacher = Teacher::with('availabilities')->find($data['teacher_id']);
            $availability = $teacher?->availabilities
                ->where('weekday', (int) $data['weekday']);

            if ($availability?->isNotEmpty() && ! $availability->contains(
                fn ($slot) => substr($slot->starts_at, 0, 5) <= substr($data['starts_at'], 0, 5)
                    && substr($slot->ends_at, 0, 5) >= substr($data['ends_at'], 0, 5)
            )) {
                $errors['teacher_id'] = 'L’enseignant n’est pas disponible sur ce créneau.';
            }
        }

        if (! empty($data['session_date'])) {
            $examConflicts = Exam::query()
                ->whereDate('starts_at', $data['session_date'])
                ->where('starts_at', '<', $data['session_date'].' '.$data['ends_at'])
                ->where('ends_at', '>', $data['session_date'].' '.$data['starts_at'])
                ->get();

            if ($examConflicts->contains(fn (Exam $exam) => $exam->room_id === (int) $data['room_id'])) {
                $errors['room_id'] = 'Conflit détecté : cette salle est réservée pour un examen.';
            }

            if (! empty($data['teacher_id']) && $examConflicts->contains(
                fn (Exam $exam) => $exam->teacher_id === (int) $data['teacher_id']
            )) {
                $errors['teacher_id'] = 'Conflit détecté : cet enseignant surveille déjà un examen à cette heure.';
            }

            if (! empty($data['group_id']) && $examConflicts->contains(
                fn (Exam $exam) => $exam->group_id === (int) $data['group_id']
            )) {
                $errors['group_id'] = 'Conflit détecté : ce groupe a déjà un examen sur ce créneau.';
            }
        } else {
            $examConflicts = Exam::query()
                ->when(! empty($data['semester_id']), function ($query) use ($data) {
                    $semester = \App\Models\Semester::find($data['semester_id']);

                    if ($semester) {
                        $query->whereDate('starts_at', '>=', $semester->starts_on)
                            ->whereDate('starts_at', '<=', $semester->ends_on);
                    }
                })
                ->when(empty($data['semester_id']), fn ($query) => $query->where('ends_at', '>=', now()))
                ->whereTime('starts_at', '<', $data['ends_at'])
                ->whereTime('ends_at', '>', $data['starts_at'])
                ->get();
            $examConflicts = $examConflicts->filter(
                fn (Exam $exam) => $exam->starts_at->dayOfWeekIso === (int) $data['weekday']
            );

            if ($examConflicts->contains(fn (Exam $exam) => $exam->room_id === (int) $data['room_id'])) {
                $errors['room_id'] = 'Conflit détecté : cette salle est déjà réservée pour un examen pendant la période universitaire.';
            }

            if (! empty($data['teacher_id']) && $examConflicts->contains(
                fn (Exam $exam) => $exam->teacher_id === (int) $data['teacher_id']
            )) {
                $errors['teacher_id'] = 'Conflit détecté : cet enseignant surveille déjà un examen sur ce créneau.';
            }

            if (! empty($data['group_id']) && $examConflicts->contains(
                fn (Exam $exam) => $exam->group_id === (int) $data['group_id']
            )) {
                $errors['group_id'] = 'Conflit détecté : ce groupe a déjà un examen sur ce créneau.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
