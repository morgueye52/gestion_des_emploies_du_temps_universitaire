<?php

namespace App\Http\Controllers;

use App\Models\CourseGrade;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentGradeController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user()->student;
        $grades = $student
            ? CourseGrade::query()
                ->with(['course.semester', 'teacher'])
                ->where('student_id', $student->id)
                ->orderByDesc('assessed_on')
                ->orderBy('course_id')
                ->get()
            : collect();

        return view('student.grades.index', compact('student', 'grades'));
    }
}
