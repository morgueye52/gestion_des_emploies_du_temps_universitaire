<?php

namespace App\Services;

use App\Models\Seance;
use App\Models\User;
use App\Notifications\SessionScheduleChanged;

class SessionNotifier
{
    public function notify(Seance $session, string $message): void
    {
        $session->loadMissing(['course', 'teacher.user', 'academicClass.formation', 'group.students.user', 'group.academicClass.formation']);
        $departmentId = $session->course?->department_id
            ?? $session->academicClass?->formation?->department_id
            ?? $session->group?->academicClass?->formation?->department_id;

        $administrators = User::query()
            ->whereIn('role', ['admin', 'super_admin', 'academic_direction', 'studies_manager'])
            ->get();

        if ($departmentId !== null) {
            $administrators = $administrators->merge(
                User::query()
                    ->where('role', 'department_manager')
                    ->where('department_id', $departmentId)
                    ->get()
            );
        }

        $students = $session->group
            ? $session->group->students->pluck('user')
            : ($session->academicClass?->students->pluck('user') ?? collect());

        $recipients = collect($administrators)
            ->concat([$session->teacher?->user])
            ->concat($students)
            ->filter()
            ->unique('id');

        foreach ($recipients as $recipient) {
            $recipient->notify(new SessionScheduleChanged($message));
        }
    }
}
