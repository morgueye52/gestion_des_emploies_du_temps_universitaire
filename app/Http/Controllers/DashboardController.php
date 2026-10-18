<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\Course;
use App\Models\Department;
use App\Models\Exam;
use App\Models\Formation;
use App\Models\Room;
use App\Models\Seance;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\DepartmentScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DepartmentScope $scope): View
    {
        $today = Carbon::today();
        if ($request->user()->hasRole(['hr_admin', 'it_admin'])) {
            $sessionsToday = $scope->query(Seance::query(), 'seances', $request->user())
                ->with(['course', 'teacher', 'room', 'academicClass', 'group.academicClass'])
                ->where(function ($query) use ($today) {
                    $query->whereDate('session_date', $today)
                        ->orWhere(function ($recurring) use ($today) {
                            $recurring->whereNull('session_date')
                                ->where('weekday', $today->dayOfWeekIso);
                        });
                })
                ->orderBy('starts_at')
                ->get();

            $isHrAdmin = $request->user()->hasRole('hr_admin');
            $isItAdmin = $request->user()->hasRole('it_admin');
            $stats = [
                'courses' => $scope->query(Course::query(), 'courses', $request->user())->count(),
                'sessions' => $scope->query(Seance::query(), 'seances', $request->user())->count(),
            ];

            if ($isHrAdmin) {
                $stats['teachers'] = $scope->query(Teacher::query(), 'teachers', $request->user())->count();
            }

            if ($isItAdmin) {
                $stats['rooms'] = Room::count();
            }

            return view('admin.dashboard', [
                'isHrAdmin' => $isHrAdmin,
                'isItAdmin' => $isItAdmin,
                'stats' => $stats,
                'sessionsToday' => $sessionsToday,
            ]);
        }

        $sessionsToday = $scope->query(Seance::query(), 'seances', $request->user())
            ->with(['course', 'teacher', 'room', 'academicClass', 'group.academicClass'])
            ->where(function ($query) use ($today) {
                $query->whereDate('session_date', $today)
                    ->orWhere(function ($recurring) use ($today) {
                        $recurring->whereNull('session_date')
                            ->where('weekday', $today->dayOfWeekIso);
                    });
            })
            ->orderBy('starts_at')
            ->get();

        $students = $scope->query(Student::query(), 'students', $request->user())
            ->with('academicClass.formation')
            ->get();
        $studentsByFormation = $students
            ->filter(fn (Student $student) => $student->academicClass?->formation)
            ->groupBy(fn (Student $student) => $student->academicClass->formation->name)
            ->map(fn ($records) => $records->count())
            ->sortDesc()
            ->take(6);
        $sessionsByWeekday = $scope->query(Seance::query(), 'seances', $request->user())
            ->selectRaw('weekday, count(*) as total')
            ->whereBetween('weekday', [1, 6])
            ->groupBy('weekday')
            ->pluck('total', 'weekday');
        $upcomingExams = $scope->query(Exam::query(), 'exams', $request->user())
            ->with(['course', 'room', 'group'])
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'isHrAdmin' => false,
            'isItAdmin' => false,
            'stats' => [
                'students' => $scope->query(Student::query(), 'students', $request->user())->count(),
                'teachers' => $scope->query(Teacher::query(), 'teachers', $request->user())->count(),
                'courses' => $scope->query(Course::query(), 'courses', $request->user())->count(),
                'departments' => $scope->query(Department::query(), 'departments', $request->user())->count(),
                'formations' => $scope->query(Formation::query(), 'formations', $request->user())->count(),
                'classes' => $scope->query(AcademicClass::query(), 'classes', $request->user())->count(),
                'rooms' => Room::count(),
                'sessions' => $scope->query(Seance::query(), 'seances', $request->user())->count(),
                'exams' => $scope->query(Exam::query(), 'exams', $request->user())->where('starts_at', '>=', now())->count(),
            ],
            'sessionsToday' => $sessionsToday,
            'activeRooms' => $sessionsToday->pluck('room_id')->unique()->count(),
            'studentsByFormation' => $studentsByFormation,
            'sessionsByWeekday' => $sessionsByWeekday,
            'upcomingExams' => $upcomingExams,
        ]);
    }
}
