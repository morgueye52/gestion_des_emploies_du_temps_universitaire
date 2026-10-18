<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Room;
use App\Models\Seance;
use App\Models\Semester;
use App\Models\StudentGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimetableGenerationService
{
    public function __construct(
        private readonly TimetableService $validator,
        private readonly SessionNotifier $notifier,
    ) {}

    public function generate(?int $semesterId = null): array
    {
        $semester = $semesterId
            ? Semester::findOrFail($semesterId)
            : Semester::where('is_active', true)->first();

        if (! $semester) {
            return ['created' => 0, 'skipped' => ['Aucun semestre actif n’est défini.']];
        }

        $courses = Course::query()
            ->with(['teachers.availabilities', 'formation.classes.groups.students.user'])
            ->where('is_active', true)
            ->where(function ($query) use ($semester) {
                $query->whereNull('semester_id')->orWhere('semester_id', $semester->id);
            })
            ->orderBy('id')
            ->get();
        $rooms = Room::where('status', 'available')->orderBy('capacity')->get();
        $created = 0;
        $skipped = [];

        DB::transaction(function () use ($courses, $rooms, $semester, &$created, &$skipped) {
            foreach ($courses as $course) {
                if (! $course->formation) {
                    $skipped[] = "{$course->name} : aucune formation n’est définie.";

                    continue;
                }

                if ($course->teachers->isEmpty()) {
                    $skipped[] = "{$course->name} : aucun enseignant n’est affecté.";

                    continue;
                }

                $groups = $course->formation->classes
                    ->flatMap(fn ($class) => $class->groups)
                    ->values();

                if ($groups->isEmpty()) {
                    $skipped[] = "{$course->name} : aucun groupe n’est créé pour cette formation.";

                    continue;
                }

                foreach ($groups as $group) {
                    $scheduledSessions = Seance::query()
                        ->where('course_id', $course->id)
                        ->where('group_id', $group->id)
                        ->where('semester_id', $semester->id)
                        ->get(['starts_at', 'ends_at']);
                    $scheduledHours = $scheduledSessions->sum(
                        fn (Seance $session) => (strtotime($session->ends_at) - strtotime($session->starts_at)) / 3600
                    );
                    $remaining = max(0, $course->weekly_hours - (int) round($scheduledHours));

                    while ($remaining > 0) {
                        $duration = min(2, $remaining);
                        $candidates = $this->orderedSlots($group);
                        $placed = false;

                        foreach ($candidates as [$weekday, $startsAt]) {
                            $endsAt = date('H:i:s', strtotime($startsAt) + ($duration * 3600));
                            $expected = $group->capacity ?: $group->students->count();

                            foreach ($course->teachers as $teacher) {
                                $teacherHours = Seance::query()
                                    ->where('teacher_id', $teacher->id)
                                    ->where('semester_id', $semester->id)
                                    ->get(['starts_at', 'ends_at'])
                                    ->sum(fn (Seance $session) => (strtotime($session->ends_at) - strtotime($session->starts_at)) / 3600);
                                if ($teacher->weekly_hours > 0 && $teacherHours + $duration > $teacher->weekly_hours) {
                                    continue;
                                }

                                foreach ($rooms->where('capacity', '>=', $expected) as $room) {
                                    $data = [
                                        'course_id' => $course->id,
                                        'teacher_id' => $teacher->id,
                                        'room_id' => $room->id,
                                        'group_id' => $group->id,
                                        'academic_class_id' => $group->academic_class_id,
                                        'semester_id' => $semester->id,
                                        'weekday' => $weekday,
                                        'starts_at' => $startsAt,
                                        'ends_at' => $endsAt,
                                        'room_capacity' => $room->capacity,
                                    ];

                                    try {
                                        $this->validator->validateSession($data);
                                    } catch (ValidationException) {
                                        continue;
                                    }

                                    $session = Seance::create($data + ['type' => 'lecture']);
                                    $this->notifier->notify(
                                        $session,
                                        'Une séance de '.$course->name.' a été ajoutée à votre emploi du temps.'
                                    );
                                    $created++;
                                    $remaining -= $duration;
                                    $placed = true;

                                    break 3;
                                }
                            }
                        }

                        if (! $placed) {
                            $skipped[] = "{$course->name} / {$group->name} : aucun créneau et aucune salle compatibles pour les {$remaining} h restantes.";

                            break;
                        }
                    }
                }
            }
        });

        return ['created' => $created, 'skipped' => array_values(array_unique($skipped))];
    }

    private function orderedSlots(StudentGroup $group): array
    {
        $load = Seance::query()
            ->where('group_id', $group->id)
            ->whereBetween('weekday', [1, 6])
            ->selectRaw('weekday, count(*) as total')
            ->groupBy('weekday')
            ->pluck('total', 'weekday');
        $days = range(1, 6);
        usort($days, fn (int $left, int $right) => ($load[$left] ?? 0) <=> ($load[$right] ?? 0));
        $slots = [];

        foreach ($days as $day) {
            foreach (['08:00', '10:00', '12:00', '14:00', '16:00', '18:00'] as $time) {
                $slots[] = [$day, $time];
            }
        }

        return $slots;
    }
}
