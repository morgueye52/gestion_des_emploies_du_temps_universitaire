<?php

use App\Http\Controllers\AcademicCatalogController;
use App\Http\Controllers\AcademicEventController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SeanceController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentEnrollmentController;
use App\Http\Controllers\StudentGradeController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherAttendanceController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\TeacherGradeController;
use App\Http\Controllers\UserInvitationController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/dashboard', function () {
    return redirect()->route(request()->user()->dashboardRouteName());
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::get('/invitations/{token}', [UserInvitationController::class, 'accept'])
    ->name('invitations.accept');
Route::post('/invitations/{token}', [UserInvitationController::class, 'register'])
    ->name('invitations.register');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/teacher/dashboard', TeacherDashboardController::class)
        ->middleware('role:teacher')
        ->name('teacher.dashboard');
    Route::middleware('role:teacher')->prefix('teacher')->name('teacher.')->group(function () {
        Route::get('/grades', [TeacherGradeController::class, 'index'])->name('grades.index');
        Route::post('/grades', [TeacherGradeController::class, 'store'])->name('grades.store');
        Route::get('/grades/{grade}/edit', [TeacherGradeController::class, 'edit'])->whereNumber('grade')->name('grades.edit');
        Route::put('/grades/{grade}', [TeacherGradeController::class, 'update'])->whereNumber('grade')->name('grades.update');
        Route::delete('/grades/{grade}', [TeacherGradeController::class, 'destroy'])->whereNumber('grade')->name('grades.destroy');
        Route::get('/attendance', [TeacherAttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance/{session}', [TeacherAttendanceController::class, 'store'])->whereNumber('session')->name('attendance.store');
    });
    Route::get('/student/dashboard', StudentDashboardController::class)
        ->middleware('role:student')
        ->name('student.dashboard');
    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
        Route::get('/enrollments', [StudentEnrollmentController::class, 'index'])->name('enrollments.index');
        Route::post('/enrollments', [StudentEnrollmentController::class, 'store'])->name('enrollments.store');
        Route::get('/grades', [StudentGradeController::class, 'index'])->name('grades.index');
        Route::get('/attendance', [StudentAttendanceController::class, 'index'])->name('attendance.index');
    });
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])
        ->whereUuid('notification')
        ->name('notifications.read');

    Route::prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::middleware('role:super_admin')
                ->resource('invitations', UserInvitationController::class)
                ->only(['index', 'create', 'store', 'destroy'])
                ->names('invitations');

            Route::middleware('role:admin,super_admin,director_general,academic_direction,studies_manager,department_manager,registrar,hr_admin,it_admin')
                ->get('/dashboard', DashboardController::class)->name('dashboard');

            $academicReadRoles = 'role:admin,super_admin,director_general,academic_direction,studies_manager,department_manager,registrar';
            $academicManageRoles = 'role:admin,super_admin,academic_direction';
            $studentManageRoles = 'role:admin,super_admin,academic_direction,studies_manager,department_manager,registrar';
            $courseScheduleReadRoles = 'role:admin,super_admin,director_general,academic_direction,studies_manager,department_manager,registrar,it_admin,hr_admin';
            $roomReadRoles = 'role:admin,super_admin,director_general,academic_direction,studies_manager,department_manager,registrar,it_admin';
            $teachingReadRoles = 'role:admin,super_admin,director_general,academic_direction,studies_manager,department_manager,registrar';
            $teachingManageRoles = 'role:admin,super_admin,academic_direction,studies_manager,department_manager';
            $courseManageRoles = 'role:admin,super_admin,academic_direction,studies_manager,department_manager';

            Route::middleware($academicReadRoles)->group(function () {
                foreach (['faculties', 'departments', 'formations', 'levels', 'academic-years', 'semesters', 'classes', 'groups'] as $entity) {
                    Route::get("/academic/{$entity}", [AcademicCatalogController::class, 'index'])
                        ->defaults('entity', $entity)->name("academic.{$entity}.index");
                    Route::get("/academic/{$entity}/{record}", [AcademicCatalogController::class, 'show'])
                        ->defaults('entity', $entity)->whereNumber('record')->name("academic.{$entity}.show");
                }

                Route::get('/students', [StudentController::class, 'index'])->name('students.index');
                Route::get('/students/{student}', [StudentController::class, 'show'])
                    ->whereNumber('student')->name('students.show');
            });

            foreach (['faculties', 'departments', 'formations', 'levels', 'academic-years', 'semesters', 'classes', 'groups'] as $entity) {
                $manageRoles = in_array($entity, ['formations', 'levels', 'classes', 'groups'], true)
                    ? 'role:admin,super_admin,academic_direction,department_manager'
                    : $academicManageRoles;

                Route::middleware($manageRoles)->group(function () use ($entity) {
                    Route::get("/academic/{$entity}/create", [AcademicCatalogController::class, 'create'])
                        ->defaults('entity', $entity)->name("academic.{$entity}.create");
                    Route::post("/academic/{$entity}", [AcademicCatalogController::class, 'store'])
                        ->defaults('entity', $entity)->name("academic.{$entity}.store");
                    Route::get("/academic/{$entity}/{record}/edit", [AcademicCatalogController::class, 'edit'])
                        ->defaults('entity', $entity)->whereNumber('record')->name("academic.{$entity}.edit");
                    Route::put("/academic/{$entity}/{record}", [AcademicCatalogController::class, 'update'])
                        ->defaults('entity', $entity)->whereNumber('record')->name("academic.{$entity}.update");
                    Route::delete("/academic/{$entity}/{record}", [AcademicCatalogController::class, 'destroy'])
                        ->defaults('entity', $entity)->whereNumber('record')->name("academic.{$entity}.destroy");
                });
            }

            Route::middleware($studentManageRoles)->group(function () {
                Route::resource('students', StudentController::class)->except(['index', 'show'])->names('students');
            });

            Route::middleware('role:admin,super_admin,director_general,academic_direction,studies_manager,department_manager,hr_admin')->group(function () {
                Route::get('/teachers', [TeacherController::class, 'index'])->name('teachers.index');
                Route::get('/teachers/{teacher}', [TeacherController::class, 'show'])
                    ->whereNumber('teacher')->name('teachers.show');
            });

            Route::middleware('role:admin,super_admin,academic_direction,department_manager,hr_admin')->group(function () {
                Route::resource('teachers', TeacherController::class)->except(['index', 'show'])->names('teachers');
            });

            Route::middleware($courseScheduleReadRoles)->group(function () {
                Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
                Route::get('/courses/{course}', [CourseController::class, 'show'])
                    ->whereNumber('course')->name('courses.show');
                Route::get('/seances', [SeanceController::class, 'index'])->name('seances.index');
                Route::get('/seances/{seance}', [SeanceController::class, 'show'])
                    ->whereNumber('seance')->name('seances.show');
                Route::get('/timetable', [SeanceController::class, 'timetable'])->name('timetable');
            });

            Route::middleware($roomReadRoles)->group(function () {
                Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
                Route::get('/rooms/{room}', [RoomController::class, 'show'])
                    ->whereNumber('room')->name('rooms.show');
            });

            Route::middleware($teachingReadRoles)->group(function () {
                Route::resource('exams', ExamController::class)->only('index')->names('exams');
                Route::resource('events', AcademicEventController::class)->only('index')->names('events');
            });

            Route::middleware('role:admin,super_admin,director_general,academic_direction,studies_manager')
                ->get('/reports/activity', [ReportController::class, 'activity'])->name('reports.activity');

            Route::middleware($courseManageRoles)->group(function () {
                Route::resource('courses', CourseController::class)->except(['index', 'show'])->names('courses');
            });

            Route::middleware($teachingManageRoles)->group(function () {
                Route::resource('seances', SeanceController::class)->except(['index', 'show'])->names('seances');
                Route::resource('exams', ExamController::class)->except(['index', 'show'])->names('exams');
            });

            Route::middleware('role:admin,super_admin,it_admin')->group(function () {
                Route::resource('rooms', RoomController::class)->except(['index', 'show'])->names('rooms');
            });

            Route::middleware('role:admin,super_admin,academic_direction')->group(function () {
                Route::resource('events', AcademicEventController::class)->except(['index', 'show'])->names('events');
            });

            Route::middleware('role:admin,super_admin,academic_direction,studies_manager')
                ->post('/timetable/generate', [SeanceController::class, 'generate'])->name('timetable.generate');
        });
});
