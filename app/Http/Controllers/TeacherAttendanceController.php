<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Seance;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeacherAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $this->teacherFor($request);
        $validated = $request->validate([
            'date' => ['sometimes', 'date_format:Y-m-d', 'before_or_equal:today'],
            'session_id' => ['sometimes', 'integer'],
        ]);
        $date = $validated['date'] ?? now()->toDateString();
        $sessions = Seance::query()
            ->with(['course', 'room', 'group.academicClass'])
            ->where('teacher_id', $teacher->id)
            ->where(function ($query) use ($date) {
                $query->whereDate('session_date', $date)
                    ->orWhere(function ($recurring) use ($date) {
                        $recurring->whereNull('session_date')
                            ->where('weekday', \Illuminate\Support\Carbon::parse($date)->dayOfWeekIso);
                    });
            })
            ->orderBy('starts_at')
            ->get();

        $selectedSession = $sessions->firstWhere('id', (int) ($validated['session_id'] ?? 0));
        $students = collect();
        $attendanceByStudent = collect();

        if ($selectedSession) {
            $students = Student::query()
                ->with('courseEnrollments')
                ->where('status', 'active')
                ->whereHas('courseEnrollments', fn ($query) => $query->where('course_id', $selectedSession->course_id))
                ->when($selectedSession->group_id, fn ($query) => $query->where('group_id', $selectedSession->group_id))
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
            $attendanceByStudent = AttendanceRecord::query()
                ->where('session_id', $selectedSession->id)
                ->whereDate('attendance_date', $date)
                ->get()
                ->keyBy('student_id');
        }

        return view('teacher.attendance.index', compact(
            'teacher',
            'date',
            'sessions',
            'selectedSession',
            'students',
            'attendanceByStudent'
        ));
    }

    public function store(Request $request, Seance $session, ActivityLogger $logger): RedirectResponse
    {
        $teacher = $this->teacherFor($request);
        abort_unless($session->teacher_id === $teacher->id, 404);

        $data = $request->validate([
            'attendance_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'attendance' => ['required', 'array', 'min:1'],
            'attendance.*.status' => ['required', Rule::in(['present', 'absent', 'late', 'excused'])],
            'attendance.*.comment' => ['nullable', 'string', 'max:500'],
        ], [
            'attendance.required' => 'Sélectionnez au moins un étudiant avant d’enregistrer l’appel.',
            'attendance.*.status.in' => 'Choisissez un statut de présence valide.',
        ]);

        $date = $data['attendance_date'];
        $this->assertSessionOccursOn($session, $date);

        $students = Student::query()
            ->where('status', 'active')
            ->whereHas('courseEnrollments', fn ($query) => $query->where('course_id', $session->course_id))
            ->when($session->group_id, fn ($query) => $query->where('group_id', $session->group_id))
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
        $submittedStudentIds = array_map('strval', array_keys($data['attendance']));

        if (array_diff($submittedStudentIds, $students) || array_diff($students, $submittedStudentIds)) {
            throw ValidationException::withMessages([
                'attendance' => 'La liste des étudiants inscrits a changé. Actualisez la page avant d’enregistrer l’appel.',
            ]);
        }

        DB::transaction(function () use ($data, $date, $session, $teacher, $students, $request, $logger) {
            foreach ($students as $studentId) {
                $attendance = $data['attendance'][$studentId] ?? $data['attendance'][(int) $studentId];
                $record = AttendanceRecord::query()->firstOrNew([
                    'session_id' => $session->id,
                    'attendance_date' => $date,
                    'student_id' => $studentId,
                ]);
                $oldValues = $record->exists ? $record->getAttributes() : null;
                $record->fill([
                    'teacher_id' => $teacher->id,
                    'status' => $attendance['status'],
                    'comment' => $attendance['comment'] ?? null,
                ])->save();
                $logger->record(
                    $request,
                    $oldValues === null ? 'created' : 'updated',
                    $record,
                    $oldValues,
                    $record->getAttributes()
                );
            }
        });

        return redirect()->route('teacher.attendance.index', [
            'date' => $date,
            'session_id' => $session->id,
        ])->with('success', 'Les présences ont été enregistrées.');
    }

    private function teacherFor(Request $request): Teacher
    {
        $teacher = $request->user()->teacher;
        abort_unless($teacher && $teacher->is_active, 403, 'Votre profil enseignant est inactif ou incomplet.');

        return $teacher;
    }

    private function assertSessionOccursOn(Seance $session, string $date): void
    {
        $sessionDate = $session->session_date?->toDateString();
        $weekday = \Illuminate\Support\Carbon::parse($date)->dayOfWeekIso;
        $occurs = $sessionDate !== null
            ? $sessionDate === $date
            : (int) $session->weekday === $weekday;

        if (! $occurs) {
            throw ValidationException::withMessages([
                'attendance_date' => 'La date choisie ne correspond pas à cette séance.',
            ]);
        }
    }
}
