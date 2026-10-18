<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentEnrollmentController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user()->student()
            ->with('academicClass.formation.department')
            ->first();
        $enrollmentBlockedMessage = $this->enrollmentBlockedMessage($student);
        $enrollments = $student
            ? CourseEnrollment::query()
                ->with(['course.semester.academicYear', 'course.teachers'])
                ->where('student_id', $student->id)
                ->latest()
                ->get()
            : collect();
        $availableCourses = collect();

        if ($enrollmentBlockedMessage === null) {
            $formationId = $student->academicClass->formation_id;
            $availableCourses = Course::query()
                ->with(['semester.academicYear', 'teachers'])
                ->where('is_active', true)
                ->where(function ($query) use ($formationId) {
                    $query->whereNull('formation_id')
                        ->orWhere(function ($matchingFormation) use ($formationId) {
                            $matchingFormation->where('formation_id', $formationId)
                                ->whereHas('formation', fn ($formation) => $formation
                                    ->where('is_active', true)
                                    ->whereHas('department', fn ($department) => $department->where('is_active', true)));
                        });
                })
                ->where(function ($query) {
                    $query->whereNull('semester_id')
                        ->orWhereHas('semester', fn ($semesters) => $semesters
                            ->where('is_active', true)
                            ->whereHas('academicYear', fn ($years) => $years->where('is_active', true)));
                })
                ->whereDoesntHave('enrollments', fn ($query) => $query->where('student_id', $student->id))
                ->orderBy('name')
                ->get();
        }

        return view('student.enrollments.index', compact(
            'student',
            'enrollments',
            'availableCourses',
            'enrollmentBlockedMessage'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $student = $request->user()->student()
            ->with('academicClass.formation.department')
            ->first();
        $enrollmentBlockedMessage = $this->enrollmentBlockedMessage($student);

        if ($enrollmentBlockedMessage !== null) {
            throw ValidationException::withMessages([
                'course_id' => $enrollmentBlockedMessage,
            ]);
        }

        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
        ]);

        $formationId = $student->academicClass->formation_id;
        $course = Course::query()
            ->with('semester')
            ->whereKey($validated['course_id'])
            ->where('is_active', true)
            ->where(function ($query) use ($formationId) {
                $query->whereNull('formation_id')
                    ->orWhere(function ($matchingFormation) use ($formationId) {
                        $matchingFormation->where('formation_id', $formationId)
                            ->whereHas('formation', fn ($formation) => $formation
                                ->where('is_active', true)
                                ->whereHas('department', fn ($department) => $department->where('is_active', true)));
                    });
            })
            ->where(function ($query) {
                $query->whereNull('semester_id')
                    ->orWhereHas('semester', fn ($semesters) => $semesters
                        ->where('is_active', true)
                        ->whereHas('academicYear', fn ($years) => $years->where('is_active', true)));
            })
            ->first();

        if (! $course) {
            throw ValidationException::withMessages([
                'course_id' => 'Ce cours n’est pas disponible pour votre formation ou son semestre est inactif.',
            ]);
        }

        if ($student->courseEnrollments()->where('course_id', $course->id)->exists()) {
            throw ValidationException::withMessages([
                'course_id' => 'Vous êtes déjà inscrit à ce cours.',
            ]);
        }

        $student->courseEnrollments()->create(['course_id' => $course->id]);

        return redirect()->route('student.enrollments.index')
            ->with('success', 'Votre inscription au cours « '.$course->name.' » est confirmée.');
    }

    private function enrollmentBlockedMessage(?Student $student): ?string
    {
        if ($student === null) {
            return 'Votre compte n’est pas encore associé à un dossier étudiant. Contactez le service de scolarité.';
        }

        if ($student->status !== 'active') {
            return 'Votre dossier étudiant n’est pas actif. Contactez le service de scolarité pour vérifier son statut.';
        }

        if ($student->academicClass === null) {
            return 'Votre dossier a été créé, mais aucune classe ne vous a encore été affectée. Le service de scolarité doit vous rattacher à une classe active avant votre inscription aux cours. Un groupe doit également vous être affecté pour afficher votre emploi du temps.';
        }

        if (! $student->academicClass->is_active) {
            return 'Votre classe est inactive. Contactez le service de scolarité pour être affecté à une classe active.';
        }

        $formation = $student->academicClass->formation;

        if ($formation === null || ! $formation->is_active) {
            return 'La formation associée à votre classe est inactive ou manquante. Contactez le service de scolarité.';
        }

        if (! $formation->department?->is_active) {
            return 'Le département associé à votre formation est inactif ou manquant. Contactez le service de scolarité.';
        }

        return null;
    }
}
