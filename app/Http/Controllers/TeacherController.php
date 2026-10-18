<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\DepartmentScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(Request $request, DepartmentScope $scope): View
    {
        $teachers = $scope->query(Teacher::query(), 'teachers', $request->user())->with('department')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';
                $query->where(fn ($search) => $search->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('employee_number', 'like', $term));
            })
            ->orderBy('last_name')->paginate(15)->withQueryString();

        return view('admin.teachers.index', compact('teachers'));
    }

    public function create(Request $request, DepartmentScope $scope): View
    {
        return view('admin.teachers.create', ['departments' => $this->departmentsFor($request->user(), $scope)]);
    }

    public function store(Request $request, ActivityLogger $logger, DepartmentScope $scope): RedirectResponse
    {
        $data = $this->validatedData($request, null, $scope);
        $teacher = DB::transaction(function () use ($data, $request) {
            if ($request->boolean('create_account')) {
                $data['user_id'] = $this->createTeacherAccount($data)->id;
            }
            $teacher = Teacher::create(collect($data)->except(['availabilities', 'create_account', 'account_password', 'account_password_confirmation'])->all());
            $teacher->availabilities()->createMany($data['availabilities'] ?? []);

            return $teacher;
        });
        $logger->record($request, 'created', $teacher, null, $teacher->getAttributes());

        return redirect()->route('admin.teachers.index')->with('success', 'Enseignant ajouté.');
    }

    public function show(Request $request, Teacher $teacher, DepartmentScope $scope): View
    {
        $scope->assertModel($teacher, 'teachers', $request->user());
        $teacher->load([
            'department',
            'courses' => fn ($query) => $scope->query($query, 'courses', $request->user()),
            'sessions' => fn ($query) => $scope->query($query, 'seances', $request->user())
                ->with(['course', 'room', 'group']),
        ]);

        return view('admin.teachers.show', compact('teacher'));
    }

    public function edit(Request $request, Teacher $teacher, DepartmentScope $scope): View
    {
        $scope->assertModel($teacher, 'teachers', $request->user());
        $teacher->load('availabilities');

        return view('admin.teachers.edit', [
            'teacher' => $teacher,
            'departments' => $this->departmentsFor($request->user(), $scope),
        ]);
    }

    public function update(Request $request, Teacher $teacher, ActivityLogger $logger, DepartmentScope $scope): RedirectResponse
    {
        $scope->assertModel($teacher, 'teachers', $request->user());
        $data = $this->validatedData($request, $teacher, $scope);
        $oldValues = $teacher->only(array_keys(collect($data)->except('availabilities')->all()));
        DB::transaction(function () use ($teacher, $data, $request) {
            if ($request->boolean('create_account')) {
                $data['user_id'] = $this->createTeacherAccount($data)->id;
            }
            $teacher->update(collect($data)->except(['availabilities', 'create_account', 'account_password', 'account_password_confirmation'])->all());
            $teacher->user?->update(['is_active' => $teacher->is_active]);
            $teacher->availabilities()->delete();
            $teacher->availabilities()->createMany($data['availabilities'] ?? []);
        });
        $logger->record($request, 'updated', $teacher, $oldValues, collect($data)->except('availabilities')->all());

        return redirect()->route('admin.teachers.index')->with('success', 'Enseignant mis à jour.');
    }

    public function destroy(Request $request, Teacher $teacher, ActivityLogger $logger, DepartmentScope $scope): RedirectResponse
    {
        $scope->assertModel($teacher, 'teachers', $request->user());
        if ($teacher->sessions()->exists() || $teacher->courses()->exists()) {
            throw ValidationException::withMessages([
                'teacher' => 'Cet enseignant ne peut pas être supprimé tant qu’il est affecté à des cours ou à des séances.',
            ]);
        }

        DB::transaction(function () use ($teacher, $request, $logger) {
            $logger->record($request, 'deleted', $teacher, $teacher->getAttributes());
            $teacher->user?->update(['is_active' => false]);
            $teacher->delete();
        });

        return redirect()->route('admin.teachers.index')->with('success', 'Enseignant supprimé.');
    }

    private function validatedData(Request $request, ?Teacher $teacher, DepartmentScope $scope): array
    {
        if ($request->user()->hasRole('department_manager')) {
            $request->merge(['department_id' => $request->user()->department_id]);
        }

        $data = $request->validate([
            'create_account' => ['sometimes', 'boolean', function ($attribute, $value, $fail) use ($teacher) {
                if ($value && $teacher?->user_id) {
                    $fail('Ce profil possède déjà un compte de connexion.');
                }
            }],
            'department_id' => ['nullable', 'exists:departments,id'],
            'employee_number' => ['nullable', 'string', 'max:64', Rule::unique('teachers', 'employee_number')->ignore($teacher?->id)],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'required_if:create_account,1',
                'email',
                'max:255',
                Rule::unique('teachers', 'email')->ignore($teacher?->id),
                Rule::when($request->boolean('create_account'), Rule::unique('users', 'email')),
            ],
            'account_password' => [Rule::requiredIf($request->boolean('create_account')), 'confirmed', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:40'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:120'],
            'weekly_hours' => ['required', 'integer', 'min:0', 'max:60'],
            'is_active' => ['sometimes', 'boolean'],
            'availabilities' => ['nullable', 'array'],
            'availabilities.*.weekday' => ['required', 'integer', 'between:1,6'],
            'availabilities.*.starts_at' => ['required', 'date_format:H:i'],
            'availabilities.*.ends_at' => ['required', 'date_format:H:i', 'after:availabilities.*.starts_at'],
        ]);

        if (! empty($data['department_id'])) {
            $department = \App\Models\Department::find($data['department_id']);
            if ($department && ! $department->is_active) {
                throw ValidationException::withMessages([
                    'department_id' => 'Ce département est inactif. Activez-le avant d’affecter un enseignant.',
                ]);
            }
        }

        return $data;
    }

    private function departmentsFor(\App\Models\User $user, DepartmentScope $scope)
    {
        return $scope->query(Department::query(), 'departments', $user)->orderBy('name')->get();
    }

    private function createTeacherAccount(array $data): User
    {
        return User::create([
            'name' => $data['last_name'],
            'prenom' => $data['first_name'],
            'email' => $data['email'],
            'password' => $data['account_password'],
            'role' => 'teacher',
            'is_active' => $data['is_active'] ?? true,
        ]);
    }
}
