<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Exam;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\Teacher;
use App\Services\ExamSchedulingService;
use App\Services\ActivityLogger;
use App\Services\DepartmentScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(Request $request, DepartmentScope $scope): View
    {
        $exams = $scope->query(Exam::query(), 'exams', $request->user())->with(['course', 'room', 'teacher', 'group'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(fn ($filter) => $filter
                    ->where('name', 'like', '%'.$request->string('search').'%')
                    ->orWhereHas('course', fn ($courses) => $courses->where('name', 'like', '%'.$request->string('search').'%')));
            })
            ->orderBy('starts_at')->paginate(15)->withQueryString();

        return view('admin.exams.index', compact('exams'));
    }

    public function create(Request $request, DepartmentScope $scope): View
    {
        return view('admin.exams.form', $this->options($request->user(), $scope) + ['exam' => null]);
    }

    public function store(Request $request, ExamSchedulingService $scheduler, ActivityLogger $logger, DepartmentScope $scope): RedirectResponse
    {
        $data = $this->validatedData($request, $scope);
        $data['room_capacity'] = Room::findOrFail($data['room_id'])->capacity;
        $scheduler->validate($data);
        $exam = Exam::create($data);
        $logger->record($request, 'created', $exam, null, $exam->getAttributes());

        return redirect()->route('admin.exams.index')->with('success', 'Examen planifié.');
    }

    public function edit(Request $request, Exam $exam, DepartmentScope $scope): View
    {
        $scope->assertModel($exam, 'exams', $request->user());

        return view('admin.exams.form', $this->options($request->user(), $scope) + compact('exam'));
    }

    public function update(Request $request, Exam $exam, ExamSchedulingService $scheduler, ActivityLogger $logger, DepartmentScope $scope): RedirectResponse
    {
        $scope->assertModel($exam, 'exams', $request->user());
        $data = $this->validatedData($request, $scope);
        $data['room_capacity'] = Room::findOrFail($data['room_id'])->capacity;
        $scheduler->validate($data, $exam);
        $oldValues = $exam->only(array_keys(collect($data)->except('room_capacity')->all()));
        $exam->update($data);
        $logger->record($request, 'updated', $exam, $oldValues, collect($data)->except('room_capacity')->all());

        return redirect()->route('admin.exams.index')->with('success', 'Examen modifié.');
    }

    public function destroy(Request $request, Exam $exam, ActivityLogger $logger, DepartmentScope $scope): RedirectResponse
    {
        $scope->assertModel($exam, 'exams', $request->user());
        $logger->record($request, 'deleted', $exam, $exam->getAttributes());
        $exam->delete();

        return redirect()->route('admin.exams.index')->with('success', 'Examen supprimé.');
    }

    private function validatedData(Request $request, DepartmentScope $scope): array
    {
        $data = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'teacher_id' => ['nullable', 'exists:teachers,id'],
            'group_id' => ['nullable', 'exists:groups,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['exam', 'test', 'oral'])],
            'starts_at' => ['required', 'date', 'after_or_equal:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'expected_students' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);

        $scope->assertRelated('course', $data['course_id'], $request->user());
        $scope->assertRelated('teacher', $data['teacher_id'] ?? null, $request->user());
        $scope->assertRelated('group', $data['group_id'] ?? null, $request->user());

        $course = \App\Models\Course::find($data['course_id']);
        if ($course && ! $course->is_active) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'course_id' => 'Ce cours est inactif. Activez-le avant de planifier un examen.',
            ]);
        }

        $room = \App\Models\Room::find($data['room_id']);
        if ($room && $room->status !== 'available') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'room_id' => 'Cette salle est inactive ou indisponible. Sélectionnez une salle active.',
            ]);
        }

        return $data;
    }

    private function options(\App\Models\User $user, DepartmentScope $scope): array
    {
        return [
            'courses' => $scope->query(Course::query(), 'courses', $user)->orderBy('name')->get(),
            'rooms' => Room::orderBy('name')->get(),
            'teachers' => $scope->query(Teacher::query(), 'teachers', $user)->orderBy('last_name')->get(),
            'groups' => $scope->query(StudentGroup::with('academicClass'), 'groups', $user)->orderBy('name')->get(),
        ];
    }
}
