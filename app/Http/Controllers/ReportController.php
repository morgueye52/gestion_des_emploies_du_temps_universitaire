<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\ActivityLog;
use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Services\DepartmentScope;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function activity(Request $request): View
    {
        $activities = ActivityLog::query()
            ->with('user')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';
                $query->where(fn ($filter) => $filter->where('action', 'like', $term)
                    ->orWhere('subject_type', 'like', $term)
                    ->orWhereHas('user', fn ($users) => $users->where('name', 'like', $term)));
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.reports.activity', compact('activities'));
    }

    public function attendance(Request $request, DepartmentScope $scope): View
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d'],
            'academic_class_id' => ['nullable', 'integer', 'exists:academic_classes,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
        ]);

        if (! empty($filters['from_date']) && ! empty($filters['to_date']) && $filters['from_date'] > $filters['to_date']) {
            throw ValidationException::withMessages([
                'from_date' => 'La date de début doit précéder ou être égale à la date de fin.',
            ]);
        }

        $user = $request->user();
        $classQuery = $scope->query(AcademicClass::query()->with('formation'), 'classes', $user);
        $courseQuery = $scope->query(Course::query(), 'courses', $user);
        $classes = $classQuery->orderBy('name')->get();
        $courses = $courseQuery->orderBy('name')->get();

        if (! empty($filters['academic_class_id'])) {
            abort_unless($classQuery->whereKey($filters['academic_class_id'])->exists(), 404);
        }
        if (! empty($filters['course_id'])) {
            abort_unless($courseQuery->whereKey($filters['course_id'])->exists(), 404);
        }

        $courseIds = $courseQuery->select('courses.id')->pluck('courses.id');
        $attendance = AttendanceRecord::query()
            ->whereHas('session', fn ($sessions) => $sessions->whereIn('course_id', $courseIds))
            ->when($filters['from_date'] ?? null, fn ($query, string $date) => $query->whereDate('attendance_date', '>=', $date))
            ->when($filters['to_date'] ?? null, fn ($query, string $date) => $query->whereDate('attendance_date', '<=', $date))
            ->when($filters['course_id'] ?? null, fn ($query, int $courseId) => $query->whereHas(
                'session',
                fn ($sessions) => $sessions->where('course_id', $courseId)
            ))
            ->when($filters['academic_class_id'] ?? null, fn ($query, int $classId) => $query->whereHas(
                'session',
                fn ($sessions) => $sessions->where('academic_class_id', $classId)
                    ->orWhereHas('group', fn ($groups) => $groups->where('academic_class_id', $classId))
            ));

        $totals = (clone $attendance)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused
            ")
            ->first();
        $studentReports = (clone $attendance)
            ->select('student_id')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused
            ")
            ->with('student.academicClass')
            ->groupBy('student_id')
            ->orderBy('student_id')
            ->paginate(25)
            ->withQueryString();

        $summary = [
            'total' => (int) $totals->total,
            'present' => (int) $totals->present,
            'absent' => (int) $totals->absent,
            'late' => (int) $totals->late,
            'excused' => (int) $totals->excused,
        ];
        $summary['absence_rate'] = $summary['total'] > 0
            ? round($summary['absent'] / $summary['total'] * 100, 1)
            : 0;

        return view('admin.reports.attendance', compact('filters', 'classes', 'courses', 'summary', 'studentReports'));
    }
}
