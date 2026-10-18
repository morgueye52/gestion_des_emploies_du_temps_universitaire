<?php

namespace App\Http\Controllers;

use App\Models\AcademicEvent;
use App\Models\Exam;
use App\Models\Seance;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $teacher = $request->user()->teacher;
        $validated = $request->validate([
            'view' => ['sometimes', 'in:day,week,month'],
            'weekday' => ['sometimes', 'integer', 'between:1,6'],
            'month' => ['sometimes', 'date_format:Y-m'],
        ]);
        $view = $validated['view'] ?? 'week';
        $weekday = max(1, min(6, $request->integer('weekday', now()->dayOfWeekIso)));
        $month = $request->date('month')?->startOfMonth() ?? now()->startOfMonth();
        $weeklySessions = $teacher
            ? Seance::query()
                ->with(['course', 'room', 'group.academicClass'])
                ->where('teacher_id', $teacher->id)
                ->orderBy('weekday')
                ->orderBy('starts_at')
                ->get()
            : collect();
        $sessions = $view === 'day'
            ? $weeklySessions->where('weekday', $weekday)
            : $weeklySessions;
        $exams = $teacher
            ? Exam::query()->with(['course', 'room', 'group'])
                ->where('teacher_id', $teacher->id)
                ->when($view === 'month', fn ($query) => $query->whereBetween('starts_at', [$month, $month->copy()->endOfMonth()]))
                ->when($view !== 'month', fn ($query) => $query->where('starts_at', '>=', now()))
                ->orderBy('starts_at')->get()
            : collect();
        $events = $view === 'month'
            ? AcademicEvent::query()->whereBetween('starts_at', [$month, $month->copy()->endOfMonth()])->orderBy('starts_at')->get()
            : collect();

        return view('teacher.dashboard', [
            'teacher' => $teacher,
            'sessions' => $sessions,
            'weeklySessions' => $weeklySessions,
            'exams' => $exams,
            'events' => $events,
            'view' => $view,
            'weekday' => $weekday,
            'month' => $month,
            'weeklyHours' => $weeklySessions->sum(function (Seance $session) {
                return (strtotime($session->ends_at) - strtotime($session->starts_at)) / 3600;
            }),
        ]);
    }
}
