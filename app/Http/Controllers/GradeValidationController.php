<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseGrade;
use App\Notifications\CourseGradeStatusNotification;
use App\Services\ActivityLogger;
use App\Services\DepartmentScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GradeValidationController extends Controller
{
    public function index(Request $request, DepartmentScope $scope): View
    {
        $filters = $request->validate([
            'status' => ['sometimes', Rule::in(['pending', 'published', 'rejected', 'all'])],
            'course_id' => ['sometimes', 'integer', 'exists:courses,id'],
        ]);
        $courseQuery = $scope->query(Course::query(), 'courses', $request->user());
        $courses = (clone $courseQuery)->orderBy('name')->get();

        if (! empty($filters['course_id'])) {
            abort_unless($courseQuery->whereKey($filters['course_id'])->exists(), 404);
        }

        $courseIds = $courseQuery->select('courses.id')->pluck('courses.id');
        $status = $filters['status'] ?? 'pending';
        $grades = CourseGrade::query()
            ->with(['course', 'student', 'teacher', 'reviewer'])
            ->whereIn('course_id', $courseIds)
            ->when($status !== 'all', fn (Builder $query) => $query->where('status', $status))
            ->when($filters['course_id'] ?? null, fn (Builder $query, int $courseId) => $query->where('course_id', $courseId))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('assessed_on')
            ->paginate(20)
            ->withQueryString();

        return view('admin.grades.validation', compact('grades', 'courses', 'filters', 'status'));
    }

    public function review(
        Request $request,
        CourseGrade $grade,
        DepartmentScope $scope,
        ActivityLogger $logger,
    ): RedirectResponse {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['published', 'rejected'])],
            'review_comment' => ['nullable', 'string', 'max:1000', 'required_if:decision,rejected'],
        ], [
            'review_comment.required_if' => 'Indiquez le motif du refus pour que l’enseignant puisse corriger la note.',
        ]);
        $courseIsVisible = $scope->query(Course::query(), 'courses', $request->user())
            ->whereKey($grade->course_id)
            ->exists();
        abort_unless($courseIsVisible, 404);

        DB::transaction(function () use ($request, $grade, $data, $logger) {
            $locked = CourseGrade::query()->lockForUpdate()->findOrFail($grade->id);
            abort_unless($locked->status === 'pending', 409, 'Cette note a déjà été traitée.');

            $oldValues = $locked->getAttributes();
            $locked->update([
                'status' => $data['decision'],
                'reviewer_user_id' => $request->user()->id,
                'reviewed_at' => now(),
                'review_comment' => $data['review_comment'] ?? null,
            ]);
            $logger->record($request, 'updated', $locked, $oldValues, $locked->getAttributes());
        });

        $grade->loadMissing('student.user', 'teacher.user', 'course');
        $grade->teacher->user?->notify(new CourseGradeStatusNotification(
            $data['decision'] === 'published' ? 'Note publiée' : 'Note à corriger',
            $data['decision'] === 'published'
                ? "La note de {$grade->student->first_name} {$grade->student->last_name} pour {$grade->course->name} a été publiée."
                : "La note de {$grade->student->first_name} {$grade->student->last_name} pour {$grade->course->name} doit être corrigée. Motif : {$data['review_comment']}"
        ));
        if ($data['decision'] === 'published') {
            $grade->student->user?->notify(new CourseGradeStatusNotification(
                'Nouvelle note publiée',
                "Une nouvelle note est disponible pour le cours {$grade->course->name}."
            ));
        }

        return back()->with(
            'success',
            $data['decision'] === 'published' ? 'La note a été publiée à l’étudiant.' : 'La note a été refusée et renvoyée à l’enseignant.'
        );
    }
}
