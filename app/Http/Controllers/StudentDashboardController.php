<?php

namespace App\Http\Controllers;

use App\Models\AcademicEvent;
use App\Models\Exam;
use App\Models\Seance;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $student = $request->user()->student;
        $validated = $request->validate([
            'view' => ['sometimes', 'in:day,week,month'],
            'weekday' => ['sometimes', 'integer', 'between:1,6'],
            'month' => ['sometimes', 'date_format:Y-m'],
        ]);
        $view = $validated['view'] ?? 'week';
        $weekday = max(1, min(6, $request->integer('weekday', now()->dayOfWeekIso)));
        $month = $request->date('month')?->startOfMonth() ?? now()->startOfMonth();
        $weeklySessions = $student?->group_id
            ? Seance::query()
                ->with(['course', 'teacher', 'room'])
                ->where('group_id', $student->group_id)
                ->orderBy('weekday')
                ->orderBy('starts_at')
                ->get()
            : collect();
        $sessions = $view === 'day'
            ? $weeklySessions->where('weekday', $weekday)
            : $weeklySessions;

        $exams = $student?->group_id
            ? Exam::query()->with(['course', 'room', 'teacher'])
                ->where('group_id', $student->group_id)
                ->when($view === 'month', fn ($query) => $query->whereBetween('starts_at', [$month, $month->copy()->endOfMonth()]))
                ->when($view !== 'month', fn ($query) => $query->where('starts_at', '>=', now()))
                ->orderBy('starts_at')->get()
            : collect();
        $events = $view === 'month'
            ? AcademicEvent::query()->whereBetween('starts_at', [$month, $month->copy()->endOfMonth()])->orderBy('starts_at')->get()
            : collect();

        return view('student.dashboard', compact('student', 'sessions', 'weeklySessions', 'exams', 'events', 'view', 'weekday', 'month'));
    }
}
