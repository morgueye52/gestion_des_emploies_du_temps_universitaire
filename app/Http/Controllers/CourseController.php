<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Department;
use App\Models\Formation;
use App\Models\Semester;
use App\Models\Teacher;
use App\Services\ActivityLogger;
use App\Services\DepartmentScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request, DepartmentScope $scope): View
    {
        $courses = $scope->query(Course::query(), 'courses', $request->user())
            ->with(['department', 'formation', 'semester', 'teachers'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';
                $query->where(fn ($search) => $search->where('name', 'like', $term)->orWhere('code', 'like', $term));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.courses.index', compact('courses'));
    }

    public function create(Request $request, DepartmentScope $scope): View
    {
        return view('admin.courses.create', $this->formOptions($request->user(), $scope));
    }

    public function store(Request $request, ActivityLogger $logger, DepartmentScope $scope): RedirectResponse
    {
        $data = $this->validatedData($request, null, $scope);
        $course = Course::create(collect($data)->except('teachers')->all());
        $course->teachers()->sync($data['teachers'] ?? []);
        $logger->record($request, 'created', $course, null, $course->getAttributes());

        return redirect()->route('admin.courses.index')->with('success', 'Cours créé.');
    }

    public function show(Request $request, Course $course, DepartmentScope $scope): View
    {
        $scope->assertModel($course, 'courses', $request->user());
        $course->load([
            'department',
            'formation',
            'semester',
            'teachers' => fn ($query) => $scope->query($query, 'teachers', $request->user()),
        ]);

        return view('admin.courses.show', compact('course'));
    }

    public function edit(Request $request, Course $course, DepartmentScope $scope): View
    {
        $scope->assertModel($course, 'courses', $request->user());
        $course->load('teachers');

        return view('admin.courses.edit', $this->formOptions($request->user(), $scope) + compact('course'));
    }

    public function update(Request $request, Course $course, ActivityLogger $logger, DepartmentScope $scope): RedirectResponse
    {
        $scope->assertModel($course, 'courses', $request->user());
        $data = $this->validatedData($request, $course, $scope);
        $oldValues = $course->only(array_keys(collect($data)->except('teachers')->all()));
        $course->update(collect($data)->except('teachers')->all());
        $course->teachers()->sync($data['teachers'] ?? []);
        $logger->record($request, 'updated', $course, $oldValues, collect($data)->except('teachers')->all());

        return redirect()->route('admin.courses.index')->with('success', 'Cours mis à jour.');
    }

    public function destroy(Request $request, Course $course, ActivityLogger $logger, DepartmentScope $scope): RedirectResponse
    {
        $scope->assertModel($course, 'courses', $request->user());
        if ($course->sessions()->exists() || $course->exams()->exists()) {
            throw ValidationException::withMessages([
                'course' => 'Ce cours ne peut pas être supprimé tant qu’il est utilisé par une séance ou un examen.',
            ]);
        }

        $logger->record($request, 'deleted', $course, $course->getAttributes());
        $course->delete();

        return redirect()->route('admin.courses.index')->with('success', 'Cours supprimé.');
    }

    private function validatedData(Request $request, ?Course $course, DepartmentScope $scope): array
    {
        if ($request->user()->hasRole('department_manager')) {
            $request->merge(['department_id' => $request->user()->department_id]);
        }

        $data = $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'formation_id' => ['nullable', 'exists:formations,id'],
            'semester_id' => ['nullable', 'exists:semesters,id'],
            'code' => ['required', 'string', 'max:64', Rule::unique('courses', 'code')->ignore($course?->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'credits' => ['required', 'integer', 'min:0', 'max:60'],
            'weekly_hours' => ['required', 'integer', 'min:0', 'max:60'],
            'is_active' => ['sometimes', 'boolean'],
            'teachers' => ['nullable', 'array'],
            'teachers.*' => ['integer', 'distinct', 'exists:teachers,id'],
        ]);

        $scope->assertRelated('formation', $data['formation_id'] ?? null, $request->user());
        foreach ($data['teachers'] ?? [] as $teacherId) {
            $scope->assertRelated('teacher', $teacherId, $request->user());
            $teacher = \App\Models\Teacher::find($teacherId);
            if ($teacher && ! $teacher->is_active) {
                throw ValidationException::withMessages([
                    'teachers' => 'L’enseignant sélectionné est inactif. Activez son profil pour le rattacher au cours.',
                ]);
            }
        }

        if (! empty($data['department_id'])) {
            $department = \App\Models\Department::find($data['department_id']);
            if ($department && ! $department->is_active) {
                throw ValidationException::withMessages([
                    'department_id' => 'Ce département est inactif. Activez-le avant de l’utiliser.',
                ]);
            }
        }

        if (! empty($data['formation_id'])) {
            $formation = \App\Models\Formation::find($data['formation_id']);
            if ($formation && ! $formation->is_active) {
                throw ValidationException::withMessages([
                    'formation_id' => 'Cette formation est inactive. Activez-la avant de la relier à un cours.',
                ]);
            }
        }

        if (! empty($data['semester_id'])) {
            $semester = \App\Models\Semester::find($data['semester_id']);
            if ($semester && ! $semester->is_active) {
                throw ValidationException::withMessages([
                    'semester_id' => 'Ce semestre est inactif. Activez-le avant de l’associer au cours.',
                ]);
            }
        }

        return $data;
    }

    private function formOptions(\App\Models\User $user, DepartmentScope $scope): array
    {
        return [
            'departments' => $scope->query(Department::query(), 'departments', $user)->orderBy('name')->get(),
            'formations' => $scope->query(Formation::query(), 'formations', $user)->orderBy('name')->get(),
            'semesters' => Semester::with('academicYear')->orderByDesc('starts_on')->get(),
            'teachers' => $scope->query(Teacher::query(), 'teachers', $user)->orderBy('last_name')->get(),
        ];
    }
}
