<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\Student;
use App\Models\StudentGroup;
use App\Services\ActivityLogger;
use App\Services\DepartmentScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request, DepartmentScope $scope): View
    {
        $students = $scope->query(Student::query(), 'students', $request->user())
            ->with(['academicClass', 'group'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';
                $query->where(fn ($search) => $search
                    ->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('matricule', 'like', $term));
            })
            ->orderBy('last_name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.students.index', compact('students'));
    }

    public function create(Request $request, DepartmentScope $scope): View
    {
        return view('admin.students.create', $this->formOptions($request->user(), $scope));
    }

    public function store(Request $request, ActivityLogger $logger, DepartmentScope $scope): RedirectResponse
    {
        $student = Student::create($this->validatedData($request, null, $scope));
        $logger->record($request, 'created', $student, null, $student->getAttributes());

        return redirect()->route('admin.students.index')->with('success', 'Étudiant ajouté.');
    }

    public function show(Request $request, Student $student, DepartmentScope $scope): View
    {
        $scope->assertModel($student, 'students', $request->user());
        $student->load(['academicClass', 'group']);

        return view('admin.students.show', compact('student'));
    }

    public function edit(Request $request, Student $student, DepartmentScope $scope): View
    {
        $scope->assertModel($student, 'students', $request->user());

        return view('admin.students.edit', $this->formOptions($request->user(), $scope) + compact('student'));
    }

    public function update(Request $request, Student $student, ActivityLogger $logger, DepartmentScope $scope): RedirectResponse
    {
        $scope->assertModel($student, 'students', $request->user());
        $data = $this->validatedData($request, $student, $scope);
        $oldValues = $student->only(array_keys($data));
        $student->update($data);
        $logger->record($request, 'updated', $student, $oldValues, $data);

        return redirect()->route('admin.students.index')->with('success', 'Étudiant mis à jour.');
    }

    public function destroy(Request $request, Student $student, ActivityLogger $logger, DepartmentScope $scope): RedirectResponse
    {
        $scope->assertModel($student, 'students', $request->user());
        $logger->record($request, 'deleted', $student, $student->getAttributes());
        $student->delete();

        return redirect()->route('admin.students.index')->with('success', 'Étudiant supprimé.');
    }

    private function validatedData(Request $request, ?Student $student, DepartmentScope $scope): array
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id', Rule::unique('students', 'user_id')->ignore($student?->id)],
            'academic_class_id' => [
                Rule::requiredIf($request->user()->hasRole('department_manager')),
                'nullable',
                'exists:academic_classes,id',
            ],
            'group_id' => ['nullable', 'exists:groups,id'],
            'matricule' => ['required', 'string', 'max:64', Rule::unique('students', 'matricule')->ignore($student?->id)],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('students', 'email')->ignore($student?->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'enrolled_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'inactive', 'graduated', 'suspended'])],
        ]);

        $scope->assertRelated('class', $data['academic_class_id'] ?? null, $request->user());
        $scope->assertRelated('group', $data['group_id'] ?? null, $request->user());

        if (! empty($data['academic_class_id'])) {
            $academicClass = \App\Models\AcademicClass::find($data['academic_class_id']);
            if ($academicClass && ! $academicClass->is_active) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'academic_class_id' => 'Cette classe est inactive. Activez-la avant d’affecter un étudiant.',
                ]);
            }
        }

        if (! empty($data['group_id'])) {
            $group = \App\Models\StudentGroup::find($data['group_id']);
            if ($group && $group->academicClass && ! $group->academicClass->is_active) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'group_id' => 'Le groupe sélectionné appartient à une classe inactive.',
                ]);
            }
        }

        return $data;
    }

    private function formOptions(\App\Models\User $user, DepartmentScope $scope): array
    {
        $classes = $scope->query(AcademicClass::query(), 'classes', $user)->orderBy('name')->get();
        $groups = $scope->query(StudentGroup::with('academicClass'), 'groups', $user)->orderBy('name')->get();

        return [
            'classes' => $classes,
            'groups' => $groups,
        ];
    }
}
