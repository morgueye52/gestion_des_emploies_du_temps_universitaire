<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSessionRequest;
use App\Models\AcademicClass;
use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\Exam;
use App\Models\Room;
use App\Models\Seance;
use App\Models\Semester;
use App\Models\StudentGroup;
use App\Models\Teacher;
use App\Services\DepartmentScope;
use App\Services\SessionNotifier;
use App\Services\TimetableGenerationService;
use App\Services\TimetableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeanceController extends Controller
{
    public function index(Request $request, DepartmentScope $scope): View
    {
        $seances = $scope->query(Seance::query(), 'seances', $request->user())
            ->with(['course', 'teacher', 'room', 'academicClass', 'group.academicClass', 'semester'])
            ->when($request->filled('weekday'), fn ($query) => $query->where('weekday', $request->integer('weekday')))
            ->when($request->filled('room_id'), fn ($query) => $query->where('room_id', $request->integer('room_id')))
            ->when($request->filled('teacher_id'), fn ($query) => $query->where('teacher_id', $request->integer('teacher_id')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->whereHas('course', fn ($courses) => $courses->where('name', 'like', '%'.$request->string('search').'%'));
            })
            ->orderBy('weekday')
            ->orderBy('starts_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.seances.index', compact('seances'));
    }

    public function create(Request $request, DepartmentScope $scope): View
    {
        return view('admin.seances.create', $this->formOptions($request->user(), $scope));
    }

    public function store(StoreSessionRequest $request, TimetableService $timetable, DepartmentScope $scope, SessionNotifier $notifier): RedirectResponse
    {
        $data = $request->validated();
        $this->assertScopedReferences($data, $request, $scope);
        $this->assertActiveReferences($data);
        $this->assignAcademicClassAndSemester($data);
        $data['room_capacity'] = Room::findOrFail($data['room_id'])->capacity;
        $timetable->validateSession($data);

        $session = Seance::create($data);
        $this->logActivity($request, 'created', $session);
        $notifier->notify($session, 'Une nouvelle séance de '.$session->course->name.' a été ajoutée à votre emploi du temps.');

        return redirect()->route('admin.seances.index')->with('success', 'La séance a été créée.');
    }

    public function show(Request $request, Seance $seance, DepartmentScope $scope): View
    {
        $scope->assertModel($seance, 'seances', $request->user());
        $seance->load(['course', 'teacher', 'room', 'academicClass', 'group.academicClass', 'semester']);

        return view('admin.seances.show', compact('seance'));
    }

    public function edit(Request $request, Seance $seance, DepartmentScope $scope): View
    {
        $scope->assertModel($seance, 'seances', $request->user());

        return view('admin.seances.edit', $this->formOptions($request->user(), $scope) + compact('seance'));
    }

    public function update(StoreSessionRequest $request, Seance $seance, TimetableService $timetable, DepartmentScope $scope, SessionNotifier $notifier): RedirectResponse
    {
        $scope->assertModel($seance, 'seances', $request->user());
        $data = $request->validated();
        $this->assertScopedReferences($data, $request, $scope);
        $this->assertActiveReferences($data);
        $this->assignAcademicClassAndSemester($data);
        $data['room_capacity'] = Room::findOrFail($data['room_id'])->capacity;
        $timetable->validateSession($data, $seance);

        $oldValues = $seance->only(['course_id', 'teacher_id', 'room_id', 'group_id', 'academic_class_id', 'semester_id', 'weekday', 'starts_at', 'ends_at']);
        $seance->update($data);
        $this->logActivity($request, 'updated', $seance, $oldValues);
        $message = 'La séance de '.$seance->course->name.' a été modifiée.';
        $notifier->notify($seance, $message);

        return redirect()->route('admin.seances.index')->with('success', 'La séance a été mise à jour.');
    }

    public function destroy(Request $request, Seance $seance, DepartmentScope $scope, SessionNotifier $notifier): RedirectResponse
    {
        $scope->assertModel($seance, 'seances', $request->user());
        $oldValues = $seance->only(['course_id', 'teacher_id', 'room_id', 'group_id', 'academic_class_id', 'semester_id', 'weekday', 'starts_at', 'ends_at']);
        $this->logActivity($request, 'deleted', $seance, $oldValues);
        $notifier->notify($seance, 'La séance de '.$seance->course->name.' a été supprimée de votre emploi du temps.');
        $seance->delete();

        return redirect()->route('admin.seances.index')->with('success', 'La séance a été supprimée.');
    }

    public function timetable(Request $request, DepartmentScope $scope): View
    {
        $selectedSemester = $request->filled('semester_id')
            ? Semester::findOrFail($request->integer('semester_id'))
            : Semester::query()
                ->orderByDesc('is_active')
                ->orderByDesc('starts_on')
                ->first();
        $selectedWeekday = $request->integer('weekday');
        $weekdays = $selectedWeekday >= 1 && $selectedWeekday <= 6
            ? [$selectedWeekday]
            : range(1, 6);
        $sessionsQuery = $scope->query(Seance::query(), 'seances', $request->user())
            ->with(['course', 'teacher', 'room', 'academicClass', 'group.academicClass'])
            ->when($request->filled('teacher_id'), fn ($query) => $query->where('teacher_id', $request->integer('teacher_id')))
            ->when($request->filled('group_id'), fn ($query) => $query->where('group_id', $request->integer('group_id')))
            ->when($request->filled('room_id'), fn ($query) => $query->where('room_id', $request->integer('room_id')))
            ->when($request->filled('academic_class_id'), function ($query) use ($request) {
                $query->where(function ($classFilter) use ($request) {
                    $classFilter->where('academic_class_id', $request->integer('academic_class_id'))
                        ->orWhereHas(
                            'group',
                            fn ($groups) => $groups->where('academic_class_id', $request->integer('academic_class_id'))
                        );
                });
            })
            ->when($selectedSemester, fn ($query) => $query->where(function ($semesterQuery) use ($selectedSemester) {
                $semesterQuery->where('semester_id', $selectedSemester->id)
                    ->orWhere(function ($legacySessions) use ($selectedSemester) {
                        $legacySessions->whereNull('semester_id')
                            ->whereHas('course', fn ($courses) => $courses->where('semester_id', $selectedSemester->id));
                    });
            }));

        if (count($weekdays) === 1) {
            $sessionsQuery->where('weekday', $selectedWeekday);
        }

        $sessions = $sessionsQuery->orderBy('starts_at')->get();
        $selectedTeacher = $request->filled('teacher_id')
            ? $scope->query(Teacher::query(), 'teachers', $request->user())->with('availabilities')->findOrFail($request->integer('teacher_id'))
            : null;
        $selectedRoom = $request->filled('room_id') ? Room::findOrFail($request->integer('room_id')) : null;
        $selectedClass = $request->filled('academic_class_id')
            ? $scope->query(AcademicClass::query(), 'classes', $request->user())->findOrFail($request->integer('academic_class_id'))
            : null;
        $canViewExams = $request->user()->hasRole([
            'admin', 'super_admin', 'director_general', 'academic_direction',
            'studies_manager', 'department_manager', 'registrar',
        ]);
        $hasConflictPreviewFilter = $selectedSemester
            || $request->filled('teacher_id')
            || $request->filled('room_id')
            || $request->filled('academic_class_id');
        $exams = $canViewExams && $hasConflictPreviewFilter
            ? $scope->query(Exam::query(), 'exams', $request->user())
                ->with(['course', 'room', 'teacher', 'group.academicClass'])
                ->when(
                    $selectedSemester,
                    fn ($query) => $query->whereDate('starts_at', '>=', $selectedSemester->starts_on)
                        ->whereDate('starts_at', '<=', $selectedSemester->ends_on),
                    fn ($query) => $query->where('ends_at', '>=', now())
                )
                ->when($request->filled('teacher_id'), fn ($query) => $query->where('teacher_id', $request->integer('teacher_id')))
                ->when($request->filled('room_id'), fn ($query) => $query->where('room_id', $request->integer('room_id')))
                ->when($request->filled('academic_class_id'), fn ($query) => $query->whereHas(
                    'group',
                    fn ($groups) => $groups->where('academic_class_id', $request->integer('academic_class_id'))
                ))
                ->get()
            : collect();

        return view('admin.timetable', [
            'sessions' => $sessions,
            'exams' => $exams,
            'weekdays' => $weekdays,
            'teachers' => $scope->query(Teacher::query(), 'teachers', $request->user())->orderBy('last_name')->get(),
            'groups' => $scope->query(StudentGroup::with('academicClass'), 'groups', $request->user())->orderBy('name')->get(),
            'classes' => $scope->query(AcademicClass::query(), 'classes', $request->user())->orderBy('name')->get(),
            'semesters' => Semester::with('academicYear')->orderByDesc('starts_on')->get(),
            'rooms' => Room::orderBy('name')->get(),
            'selectedTeacher' => $selectedTeacher,
            'selectedRoom' => $selectedRoom,
            'selectedClass' => $selectedClass,
            'selectedSemester' => $selectedSemester,
        ]);
    }

    public function generate(Request $request, TimetableGenerationService $generator): RedirectResponse
    {
        $validated = $request->validate([
            'semester_id' => ['nullable', 'exists:semesters,id'],
        ]);
        $result = $generator->generate(isset($validated['semester_id']) ? (int) $validated['semester_id'] : null);
        $message = $result['created'].' séance(s) créée(s).';

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'generated',
            'subject_type' => Seance::class,
            'old_values' => null,
            'new_values' => ['created' => $result['created'], 'skipped' => $result['skipped']],
            'ip_address' => $request->ip(),
        ]);

        if ($result['skipped'] !== []) {
            $message .= ' Certaines séances nécessitent une intervention : '.implode(' ', $result['skipped']);
        }

        return redirect()->route('admin.timetable')->with($result['created'] ? 'success' : 'warning', $message);
    }

    private function formOptions(\App\Models\User $user, DepartmentScope $scope): array
    {
        return [
            'courses' => $scope->query(Course::query(), 'courses', $user)->orderBy('name')->get(),
            'rooms' => Room::orderBy('name')->get(),
            'teachers' => $scope->query(Teacher::query(), 'teachers', $user)->orderBy('last_name')->get(),
            'groups' => $scope->query(StudentGroup::with('academicClass'), 'groups', $user)->orderBy('name')->get(),
            'classes' => $scope->query(AcademicClass::query(), 'classes', $user)->orderBy('name')->get(),
            'semesters' => Semester::with('academicYear')->orderByDesc('starts_on')->get(),
        ];
    }

    private function assertScopedReferences(array $data, Request $request, DepartmentScope $scope): void
    {
        $scope->assertRelated('course', $data['course_id'], $request->user());
        $scope->assertRelated('teacher', $data['teacher_id'] ?? null, $request->user());
        $scope->assertRelated('group', $data['group_id'] ?? null, $request->user());
        $scope->assertRelated('class', $data['academic_class_id'] ?? null, $request->user());
    }

    private function assertActiveReferences(array $data): void
    {
        $course = Course::find($data['course_id'] ?? null);
        if ($course && ! $course->is_active) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'course_id' => 'Ce cours est actuellement inactif. Activez-le pour pouvoir l’utiliser dans une séance.',
            ]);
        }

        if (! empty($data['teacher_id'])) {
            $teacher = Teacher::find($data['teacher_id']);
            if ($teacher && ! $teacher->is_active) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'teacher_id' => 'Cet enseignant est inactif. Activez son profil avant de le sélectionner.',
                ]);
            }
        }

        $room = Room::find($data['room_id'] ?? null);
        if ($room && $room->status !== 'available') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'room_id' => 'Cette salle est actuellement inactive ou indisponible. Choisissez une salle active.',
            ]);
        }

        if (! empty($data['group_id'])) {
            $group = StudentGroup::find($data['group_id']);
            if ($group && $group->academicClass && ! $group->academicClass->is_active) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'group_id' => 'La classe associée à ce groupe est inactive.',
                ]);
            }
        }

        if (! empty($data['academic_class_id'])) {
            $class = AcademicClass::find($data['academic_class_id']);
            if ($class && ! $class->is_active) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'academic_class_id' => 'Cette classe est inactive. Activez-la pour l’utiliser dans une séance.',
                ]);
            }
        }

        if (! empty($data['semester_id'])) {
            $semester = Semester::find($data['semester_id']);
            if ($semester && ! $semester->is_active) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'semester_id' => 'Ce semestre est inactif. Activez-le avant de planifier une séance.',
                ]);
            }
        }
    }

    private function assignAcademicClassAndSemester(array &$data): void
    {
        $course = Course::findOrFail($data['course_id']);

        if ($course->semester_id && ! empty($data['semester_id']) && (int) $data['semester_id'] !== $course->semester_id) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'semester_id' => 'Le semestre choisi ne correspond pas au semestre du cours.',
            ]);
        }

        $data['semester_id'] = ($data['semester_id'] ?? null) ?: $course->semester_id;

        if (empty($data['group_id'])) {
            return;
        }

        $group = StudentGroup::findOrFail($data['group_id']);

        if (! empty($data['academic_class_id']) && (int) $data['academic_class_id'] !== $group->academic_class_id) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'group_id' => 'Le groupe sélectionné ne fait pas partie de la classe choisie.',
            ]);
        }

        $data['academic_class_id'] = $group->academic_class_id;
    }

    private function logActivity(Request $request, string $action, Seance $session, ?array $oldValues = null): void
    {
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => Seance::class,
            'subject_id' => $session->id,
            'old_values' => $oldValues,
            'new_values' => $action === 'deleted' ? null : $session->only([
                'course_id', 'teacher_id', 'room_id', 'group_id', 'academic_class_id', 'semester_id', 'weekday', 'starts_at', 'ends_at',
            ]),
            'ip_address' => $request->ip(),
        ]);
    }
}
