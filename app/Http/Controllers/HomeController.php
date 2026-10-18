<?php

namespace App\Http\Controllers;

use App\Models\AcademicEvent;
use App\Models\Course;
use App\Models\Department;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('welcome', [
            'figures' => [
                'students' => Student::where('status', 'active')->count(),
                'teachers' => Teacher::where('is_active', true)->count(),
                'courses' => Course::where('is_active', true)->count(),
                'departments' => Department::where('is_active', true)->count(),
            ],
            'events' => AcademicEvent::query()
                ->where('ends_at', '>=', now())
                ->orderBy('starts_at')
                ->limit(3)
                ->get(),
        ]);
    }
}
