<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user()->student;
        $records = $student
            ? AttendanceRecord::query()
                ->with(['session.course', 'session.room', 'teacher'])
                ->where('student_id', $student->id)
                ->orderByDesc('attendance_date')
                ->orderByDesc('id')
                ->get()
            : collect();

        return view('student.attendance.index', compact('student', 'records'));
    }
}
