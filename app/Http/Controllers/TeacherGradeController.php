<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseGrade;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeacherGradeController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user()->teacher;
        $courses = $teacher
            ? $teacher->courses()->where('courses.is_active', true)->orderBy('courses.name')->get()
            : collect();
        $course = $courses->firstWhere('id', $request->integer('course_id'));
        $students = $course
            ? Student::query()
                ->where('status', 'active')
                ->whereHas('courseEnrollments', fn ($query) => $query->where('course_id', $course->id))
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get()
            : collect();
        $grades = $course
            ? CourseGrade::query()
                ->with('student')
                ->where('course_id', $course->id)
                ->where('teacher_id', $teacher->id)
                ->orderByDesc('assessed_on')
                ->orderBy('assessment_name')
                ->get()
            : collect();

        return view('teacher.grades.index', compact('teacher', 'courses', 'course', 'students', 'grades'));
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $teacher = $this->teacherFor($request);
        $data = $this->validatedData($request, $teacher);
        $grade = CourseGrade::create($data + ['teacher_id' => $teacher->id]);
        $logger->record($request, 'created', $grade, null, $grade->getAttributes());

        return redirect()->route('teacher.grades.index', ['course_id' => $grade->course_id])
            ->with('success', 'La note a été enregistrée.');
    }

    public function edit(Request $request, CourseGrade $grade): View
    {
        $teacher = $this->teacherFor($request);
        abort_unless($grade->teacher_id === $teacher->id, 404);

        $grade->load(['course', 'student']);

        return view('teacher.grades.edit', compact('grade'));
    }

    public function update(Request $request, CourseGrade $grade, ActivityLogger $logger): RedirectResponse
    {
        $teacher = $this->teacherFor($request);
        abort_unless($grade->teacher_id === $teacher->id, 404);
        $data = $this->validatedData($request, $teacher, $grade);
        $oldValues = $grade->only(array_keys($data));
        $grade->update($data);
        $logger->record($request, 'updated', $grade, $oldValues, $data);

        return redirect()->route('teacher.grades.index', ['course_id' => $grade->course_id])
            ->with('success', 'La note a été modifiée.');
    }

    public function destroy(Request $request, CourseGrade $grade, ActivityLogger $logger): RedirectResponse
    {
        $teacher = $this->teacherFor($request);
        abort_unless($grade->teacher_id === $teacher->id, 404);
        $courseId = $grade->course_id;
        $logger->record($request, 'deleted', $grade, $grade->getAttributes());
        $grade->delete();

        return redirect()->route('teacher.grades.index', ['course_id' => $courseId])
            ->with('success', 'La note a été supprimée.');
    }

    private function teacherFor(Request $request): Teacher
    {
        $teacher = $request->user()->teacher;
        abort_unless($teacher && $teacher->is_active, 403, 'Votre profil enseignant est inactif ou incomplet.');

        return $teacher;
    }

    private function validatedData(Request $request, Teacher $teacher, ?CourseGrade $grade = null): array
    {
        $data = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'assessment_name' => ['required', 'string', 'max:120'],
            'assessment_type' => ['required', Rule::in(['controle', 'examen', 'devoir', 'tp', 'autre'])],
            'score' => ['required', 'numeric', 'between:0,20'],
            'assessed_on' => ['required', 'date', 'before_or_equal:today'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ], [
            'score.between' => 'La note doit être comprise entre 0 et 20.',
            'assessed_on.before_or_equal' => 'La date de l’évaluation ne peut pas être dans le futur.',
        ]);

        $course = Course::query()
            ->whereKey($data['course_id'])
            ->where('is_active', true)
            ->whereHas('teachers', fn ($query) => $query->whereKey($teacher->id))
            ->whereHas('enrollments', fn ($query) => $query
                ->where('student_id', $data['student_id'])
                ->whereHas('student', fn ($students) => $students->where('status', 'active')))
            ->first();

        if (! $course) {
            throw ValidationException::withMessages([
                'course_id' => 'Le cours doit être actif, vous être affecté à ce cours et l’étudiant doit y être inscrit.',
            ]);
        }

        $duplicate = CourseGrade::query()
            ->where('course_id', $course->id)
            ->where('student_id', $data['student_id'])
            ->where('assessment_name', $data['assessment_name'])
            ->whereDate('assessed_on', $data['assessed_on'])
            ->when($grade, fn ($query) => $query->where('id', '!=', $grade->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'assessment_name' => 'Une note existe déjà pour cet étudiant, cette évaluation et cette date.',
            ]);
        }

        return collect($data)->except('course_id')->all() + ['course_id' => $course->id];
    }
}
