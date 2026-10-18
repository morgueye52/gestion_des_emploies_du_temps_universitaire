<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\Seance;
use App\Models\Semester;
use App\Models\Course;
use Carbon\Carbon;
use App\Models\StudentGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class ExamSchedulingService
{
    public function validate(array $data, ?Exam $ignore = null): void
    {
        $conflicts = Exam::query()
            ->when($ignore, fn (Builder $query) => $query->whereKeyNot($ignore->getKey()))
            ->where('starts_at', '<', $data['ends_at'])
            ->where('ends_at', '>', $data['starts_at'])
            ->get();
        $weekday = Carbon::parse($data['starts_at'])->dayOfWeekIso;
        $startsAt = Carbon::parse($data['starts_at'])->format('H:i:s');
        $endsAt = Carbon::parse($data['ends_at'])->format('H:i:s');
        $activeSemester = Course::find($data['course_id'])?->semester
            ?? Semester::where('is_active', true)->first();
        $sessionConflicts = Seance::query()
            ->where('weekday', $weekday)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->where(function (Builder $query) use ($data) {
                $query->whereNull('session_date')
                    ->orWhereDate('session_date', Carbon::parse($data['starts_at'])->toDateString());
            })
            ->get();
        if ($activeSemester) {
            $examDate = Carbon::parse($data['starts_at']);
            $sessionConflicts = $examDate->between($activeSemester->starts_on, $activeSemester->ends_on)
                ? $sessionConflicts
                : collect();
        }

        $errors = [];

        if ($conflicts->contains(fn (Exam $exam) => $exam->room_id === (int) $data['room_id'])) {
            $errors['room_id'] = 'Conflit détecté : cette salle est déjà réservée pour un examen.';
        }
        if ($sessionConflicts->contains(fn (Seance $session) => $session->room_id === (int) $data['room_id'])) {
            $errors['room_id'] = 'Conflit détecté : cette salle est occupée par un cours.';
        }

        if (! empty($data['teacher_id']) && $conflicts->contains(
            fn (Exam $exam) => $exam->teacher_id === (int) $data['teacher_id']
        )) {
            $errors['teacher_id'] = 'Conflit détecté : cet enseignant surveille déjà un examen.';
        }
        if (! empty($data['teacher_id']) && $sessionConflicts->contains(
            fn (Seance $session) => $session->teacher_id === (int) $data['teacher_id']
        )) {
            $errors['teacher_id'] = 'Conflit détecté : cet enseignant donne déjà un cours à cette heure.';
        }

        if (! empty($data['group_id']) && $conflicts->contains(
            fn (Exam $exam) => $exam->group_id === (int) $data['group_id']
        )) {
            $errors['group_id'] = 'Conflit détecté : ce groupe a déjà un examen sur ce créneau.';
        }
        if (! empty($data['group_id']) && $sessionConflicts->contains(
            fn (Seance $session) => $session->group_id === (int) $data['group_id']
        )) {
            $errors['group_id'] = 'Conflit détecté : ce groupe a déjà un cours sur ce créneau.';
        }

        $groupCapacity = ! empty($data['group_id'])
            ? StudentGroup::find($data['group_id'])?->capacity ?? 0
            : 0;
        $students = max((int) ($data['expected_students'] ?? 0), $groupCapacity);

        if ($students > (int) $data['room_capacity']) {
            $errors['room_id'] = 'Conflit détecté : la capacité de cette salle est insuffisante.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
