<?php

namespace App\Http\Controllers;

use App\Models\CourseEnrollment;
use App\Models\CourseGrade;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentGradeController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user()->student;
        $enrollments = $student
            ? CourseEnrollment::query()
                ->with(['course.semester.academicYear'])
                ->where('student_id', $student->id)
                ->get()
            : collect();
        $grades = $student
            ? CourseGrade::query()
                ->with(['course.semester.academicYear', 'teacher'])
                ->where('student_id', $student->id)
                ->where('status', 'published')
                ->orderByDesc('assessed_on')
                ->orderBy('course_id')
                ->get()
            : collect();
        $gradesByCourse = $grades->groupBy('course_id');
        $semesterReports = $enrollments
            ->filter(fn (CourseEnrollment $enrollment) => $enrollment->course !== null)
            ->groupBy(fn (CourseEnrollment $enrollment) => $enrollment->course->semester_id ?? 'without-semester')
            ->map(function ($semesterEnrollments) use ($gradesByCourse) {
                $courses = $semesterEnrollments->map(function (CourseEnrollment $enrollment) use ($gradesByCourse) {
                    $course = $enrollment->course;
                    $courseGrades = $gradesByCourse->get($course->id, collect());

                    return [
                        'course' => $course,
                        'grades' => $courseGrades,
                        'average' => $courseGrades->isEmpty() ? null : (float) $courseGrades->avg('score'),
                    ];
                });
                $gradedCourses = $courses->filter(
                    fn (array $courseReport) => $courseReport['average'] !== null && $courseReport['course']->credits > 0
                );
                $totalCredits = $gradedCourses->sum(fn (array $courseReport) => $courseReport['course']->credits);
                $weightedScore = $gradedCourses->sum(
                    fn (array $courseReport) => $courseReport['average'] * $courseReport['course']->credits
                );
                $semester = $courses->first()['course']->semester;

                return [
                    'semester' => $semester,
                    'courses' => $courses,
                    'average' => $totalCredits > 0 ? $weightedScore / $totalCredits : null,
                    'graded_credits' => $totalCredits,
                ];
            })
            ->sortBy(fn (array $report) => $report['semester']?->starts_on?->timestamp ?? PHP_INT_MAX)
            ->values();

        return view('student.grades.index', compact('student', 'grades', 'semesterReports'));
    }
}
