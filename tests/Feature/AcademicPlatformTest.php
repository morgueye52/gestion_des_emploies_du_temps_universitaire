<?php

use App\Models\AcademicClass;
use App\Models\AcademicEvent;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\Exam;
use App\Models\Faculty;
use App\Models\Formation;
use App\Models\Level;
use App\Models\Room;
use App\Models\Seance;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGroup;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\User;
use App\Notifications\SessionScheduleChanged;

test('students cannot access administrative resources', function () {
    $student = User::factory()->create(['role' => 'student']);

    $this->actingAs($student)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('a room cannot be assigned to overlapping sessions', function () {
    $administrator = User::factory()->create(['role' => 'super_admin']);
    $department = Department::create(['name' => 'Département test', 'code' => 'DPT', 'is_active' => true]);
    $formation = Formation::create([
        'department_id' => $department->id,
        'name' => 'Informatique',
        'code' => 'INFO',
        'is_active' => true,
    ]);
    $academicClass = AcademicClass::create([
        'formation_id' => $formation->id,
        'name' => 'Licence 1',
        'code' => 'L1',
        'capacity' => 30,
        'is_active' => true,
    ]);
    $course = Course::create([
        'department_id' => $department->id,
        'code' => 'CS101',
        'name' => 'Programmation',
        'credits' => 5,
        'weekly_hours' => 3,
    ]);
    $room = Room::create([
        'name' => 'Salle 101',
        'code' => 'A101',
        'capacity' => 40,
        'type' => 'classroom',
        'status' => 'available',
    ]);

    Seance::create([
        'course_id' => $course->id,
        'room_id' => $room->id,
        'academic_class_id' => $academicClass->id,
        'weekday' => 1,
        'starts_at' => '08:00',
        'ends_at' => '10:00',
        'type' => 'lecture',
    ]);

    $this->actingAs($administrator)
        ->post(route('admin.seances.store'), [
            'course_id' => $course->id,
            'room_id' => $room->id,
            'academic_class_id' => $academicClass->id,
            'weekday' => 1,
            'starts_at' => '09:00',
            'ends_at' => '11:00',
            'type' => 'lecture',
        ])
        ->assertSessionHasErrors('room_id');

    expect(Seance::count())->toBe(1);
});

test('creating a session inherits the course semester and tolerates a teacher without a user account', function () {
    $administrator = User::factory()->create(['role' => 'super_admin']);
    $department = Department::create(['name' => 'Département test', 'code' => 'DPT', 'is_active' => true]);
    $formation = Formation::create([
        'department_id' => $department->id,
        'name' => 'Informatique',
        'code' => 'INFO',
        'is_active' => true,
    ]);
    $academicClass = AcademicClass::create([
        'formation_id' => $formation->id,
        'name' => 'Licence 1',
        'code' => 'L1',
        'capacity' => 30,
        'is_active' => true,
    ]);
    $academicYear = AcademicYear::create([
        'name' => '2026-2027',
        'starts_on' => '2026-08-01',
        'ends_on' => '2027-07-31',
        'is_active' => true,
    ]);
    $semester = Semester::create([
        'academic_year_id' => $academicYear->id,
        'name' => 'Semestre 2',
        'starts_on' => '2027-01-01',
        'ends_on' => '2027-07-31',
        'is_active' => true,
    ]);
    $otherSemester = Semester::create([
        'academic_year_id' => $academicYear->id,
        'name' => 'Semestre 1',
        'starts_on' => '2026-08-01',
        'ends_on' => '2026-12-31',
        'is_active' => false,
    ]);
    $course = Course::create([
        'department_id' => $department->id,
        'formation_id' => $formation->id,
        'semester_id' => $semester->id,
        'code' => 'CS201',
        'name' => 'Programmation avancée',
        'credits' => 4,
        'weekly_hours' => 3,
    ]);
    $teacher = Teacher::create([
        'department_id' => $department->id,
        'first_name' => 'Awa',
        'last_name' => 'Fall',
        'email' => 'awa.fall@example.com',
        'weekly_hours' => 10,
        'is_active' => true,
    ]);
    $room = Room::create([
        'name' => 'Salle 101',
        'code' => 'A101',
        'capacity' => 40,
        'type' => 'classroom',
        'status' => 'available',
    ]);

    $this->actingAs($administrator)
        ->post(route('admin.seances.store'), [
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'room_id' => $room->id,
            'academic_class_id' => $academicClass->id,
            'weekday' => 1,
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'type' => 'lecture',
        ])
        ->assertRedirect(route('admin.seances.index'));

    $session = Seance::query()->firstOrFail();
    expect($session->semester_id)->toBe($semester->id)
        ->and($administrator->notifications()->count())->toBe(1);

    $this->post(route('admin.seances.store'), [
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'room_id' => $room->id,
        'academic_class_id' => $academicClass->id,
        'semester_id' => $otherSemester->id,
        'weekday' => 2,
        'starts_at' => '08:00',
        'ends_at' => '10:00',
        'type' => 'lecture',
    ])->assertSessionHasErrors('semester_id');

    expect(Seance::count())->toBe(1);
});

test('an administrator can open the academic dashboard', function () {
    $administrator = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($administrator)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Vue d’ensemble');
});

test('students see only their student dashboard', function () {
    $student = User::factory()->create(['role' => 'student']);

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('Mon espace étudiant');

    $this->actingAs($student)
        ->get(route('admin.students.index'))
        ->assertForbidden();
});

test('students can enroll only in active courses for their formation and active semester', function () {
    $user = User::factory()->create(['role' => 'student']);
    $department = Department::create(['name' => 'Département étudiant', 'code' => 'DETU', 'is_active' => true]);
    $formation = Formation::create([
        'department_id' => $department->id,
        'name' => 'Formation étudiante',
        'code' => 'FETU',
        'is_active' => true,
    ]);
    $otherDepartment = Department::create(['name' => 'Autre département', 'code' => 'AUTR', 'is_active' => true]);
    $otherFormation = Formation::create([
        'department_id' => $otherDepartment->id,
        'name' => 'Autre formation',
        'code' => 'FAUT',
        'is_active' => true,
    ]);
    $academicClass = AcademicClass::create([
        'formation_id' => $formation->id,
        'name' => 'Licence étudiante',
        'code' => 'L-ETU',
        'capacity' => 30,
        'is_active' => true,
    ]);
    $student = Student::create([
        'user_id' => $user->id,
        'academic_class_id' => $academicClass->id,
        'matricule' => 'ETU-INS-001',
        'first_name' => 'Awa',
        'last_name' => 'Fall',
        'status' => 'active',
    ]);
    $academicYear = AcademicYear::create([
        'name' => '2026-2027',
        'starts_on' => '2026-08-01',
        'ends_on' => '2027-07-31',
        'is_active' => true,
    ]);
    $activeSemester = Semester::create([
        'academic_year_id' => $academicYear->id,
        'name' => 'Semestre actif',
        'starts_on' => '2026-08-01',
        'ends_on' => '2026-12-31',
        'is_active' => true,
    ]);
    $inactiveSemester = Semester::create([
        'academic_year_id' => $academicYear->id,
        'name' => 'Semestre inactif',
        'starts_on' => '2027-01-01',
        'ends_on' => '2027-07-31',
        'is_active' => false,
    ]);
    $availableCourse = Course::create([
        'department_id' => $department->id,
        'formation_id' => $formation->id,
        'semester_id' => $activeSemester->id,
        'code' => 'INS101',
        'name' => 'Cours disponible',
        'credits' => 4,
        'weekly_hours' => 3,
        'is_active' => true,
    ]);
    Course::create([
        'department_id' => $department->id,
        'formation_id' => $formation->id,
        'semester_id' => $activeSemester->id,
        'code' => 'INS102',
        'name' => 'Cours désactivé',
        'credits' => 3,
        'weekly_hours' => 2,
        'is_active' => false,
    ]);
    Course::create([
        'department_id' => $otherDepartment->id,
        'formation_id' => $otherFormation->id,
        'semester_id' => $activeSemester->id,
        'code' => 'INS201',
        'name' => 'Cours d’une autre formation',
        'credits' => 3,
        'weekly_hours' => 2,
        'is_active' => true,
    ]);
    Course::create([
        'department_id' => $department->id,
        'formation_id' => $formation->id,
        'semester_id' => $inactiveSemester->id,
        'code' => 'INS103',
        'name' => 'Cours de semestre inactif',
        'credits' => 3,
        'weekly_hours' => 2,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('student.enrollments.index'))
        ->assertOk()
        ->assertSee('Cours disponible')
        ->assertDontSee('Cours désactivé')
        ->assertDontSee('Cours d’une autre formation')
        ->assertDontSee('Cours de semestre inactif');

    $this->post(route('student.enrollments.store'), ['course_id' => $availableCourse->id])
        ->assertRedirect(route('student.enrollments.index'))
        ->assertSessionHas('success');

    expect($student->courseEnrollments()->count())->toBe(1);

    $this->post(route('student.enrollments.store'), ['course_id' => $availableCourse->id])
        ->assertSessionHasErrors('course_id');

    expect($student->courseEnrollments()->count())->toBe(1);
});

test('students cannot enroll in courses outside their formation or in inactive courses', function () {
    $user = User::factory()->create(['role' => 'student']);
    $department = Department::create(['name' => 'Département inscription', 'code' => 'DINS', 'is_active' => true]);
    $formation = Formation::create([
        'department_id' => $department->id,
        'name' => 'Formation inscription',
        'code' => 'FINS',
        'is_active' => true,
    ]);
    $academicClass = AcademicClass::create([
        'formation_id' => $formation->id,
        'name' => 'Classe inscription',
        'code' => 'CINS',
        'capacity' => 25,
        'is_active' => true,
    ]);
    Student::create([
        'user_id' => $user->id,
        'academic_class_id' => $academicClass->id,
        'matricule' => 'ETU-INS-002',
        'first_name' => 'Moussa',
        'last_name' => 'Ba',
        'status' => 'active',
    ]);
    $course = Course::create([
        'formation_id' => $formation->id,
        'code' => 'INS301',
        'name' => 'Cours inactif',
        'credits' => 2,
        'weekly_hours' => 1,
        'is_active' => false,
    ]);

    $this->actingAs($user)
        ->post(route('student.enrollments.store'), ['course_id' => $course->id])
        ->assertSessionHasErrors('course_id');
});

test('teachers can manage grades only for students enrolled in their assigned courses and students see only their own grades', function () {
    $teacherUser = User::factory()->create(['role' => 'teacher']);
    $otherTeacherUser = User::factory()->create(['role' => 'teacher']);
    $studentUser = User::factory()->create(['role' => 'student']);
    $otherStudentUser = User::factory()->create(['role' => 'student']);
    $department = Department::create(['name' => 'Département des notes', 'code' => 'DNOT', 'is_active' => true]);
    $formation = Formation::create([
        'department_id' => $department->id,
        'name' => 'Formation des notes',
        'code' => 'FNOT',
        'is_active' => true,
    ]);
    $academicClass = AcademicClass::create([
        'formation_id' => $formation->id,
        'name' => 'Classe des notes',
        'code' => 'CNOT',
        'capacity' => 30,
        'is_active' => true,
    ]);
    $teacher = Teacher::create([
        'user_id' => $teacherUser->id,
        'department_id' => $department->id,
        'first_name' => 'Mariam',
        'last_name' => 'Enseignante',
        'email' => 'mariam.notes@example.com',
        'weekly_hours' => 10,
        'is_active' => true,
    ]);
    $otherTeacher = Teacher::create([
        'user_id' => $otherTeacherUser->id,
        'department_id' => $department->id,
        'first_name' => 'Ibrahima',
        'last_name' => 'Autre',
        'email' => 'ibrahima.notes@example.com',
        'weekly_hours' => 10,
        'is_active' => true,
    ]);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'academic_class_id' => $academicClass->id,
        'matricule' => 'NOTE-ETU-001',
        'first_name' => 'Awa',
        'last_name' => 'Étudiante',
        'status' => 'active',
    ]);
    $otherStudent = Student::create([
        'user_id' => $otherStudentUser->id,
        'academic_class_id' => $academicClass->id,
        'matricule' => 'NOTE-ETU-002',
        'first_name' => 'Fatou',
        'last_name' => 'Autre',
        'status' => 'active',
    ]);
    $course = Course::create([
        'department_id' => $department->id,
        'formation_id' => $formation->id,
        'code' => 'NOT101',
        'name' => 'Évaluation des notes',
        'credits' => 3,
        'weekly_hours' => 2,
        'is_active' => true,
    ]);
    $unassignedCourse = Course::create([
        'department_id' => $department->id,
        'formation_id' => $formation->id,
        'code' => 'NOT102',
        'name' => 'Cours non affecté',
        'credits' => 2,
        'weekly_hours' => 1,
        'is_active' => true,
    ]);
    $teacher->courses()->attach($course);
    $student->courseEnrollments()->create(['course_id' => $course->id]);

    $this->actingAs($teacherUser)
        ->get(route('teacher.grades.index', ['course_id' => $course->id]))
        ->assertOk()
        ->assertSee('Awa Étudiante')
        ->assertDontSee('Fatou Autre');

    $this->post(route('teacher.grades.store'), [
        'course_id' => $course->id,
        'student_id' => $student->id,
        'assessment_name' => 'Contrôle continu 1',
        'assessment_type' => 'controle',
        'score' => '12.5',
        'assessed_on' => now()->toDateString(),
        'comment' => 'Bon travail.',
    ])->assertRedirect(route('teacher.grades.index', ['course_id' => $course->id]));

    $grade = \App\Models\CourseGrade::query()->firstOrFail();
    expect((float) $grade->score)->toBe(12.5)
        ->and($grade->teacher_id)->toBe($teacher->id);

    $this->actingAs($studentUser)
        ->get(route('student.grades.index'))
        ->assertOk()
        ->assertSee('Contrôle continu 1')
        ->assertSee('12,5/20')
        ->assertSee('Bon travail.');

    $this->actingAs($otherStudentUser)
        ->get(route('student.grades.index'))
        ->assertOk()
        ->assertDontSee('Contrôle continu 1')
        ->assertDontSee('Bon travail.');

    $this->actingAs($teacherUser)
        ->put(route('teacher.grades.update', $grade), [
            'course_id' => $course->id,
            'student_id' => $student->id,
            'assessment_name' => 'Contrôle continu 1',
            'assessment_type' => 'controle',
            'score' => '14',
            'assessed_on' => now()->toDateString(),
            'comment' => 'Progrès constatés.',
        ])
        ->assertRedirect(route('teacher.grades.index', ['course_id' => $course->id]));

    expect((float) $grade->fresh()->score)->toBe(14.0);

    $this->actingAs($otherTeacherUser)
        ->get(route('teacher.grades.edit', $grade))
        ->assertNotFound();

    $this->actingAs($teacherUser)
        ->post(route('teacher.grades.store'), [
            'course_id' => $unassignedCourse->id,
            'student_id' => $student->id,
            'assessment_name' => 'Note interdite',
            'assessment_type' => 'examen',
            'score' => '10',
            'assessed_on' => now()->toDateString(),
        ])
        ->assertSessionHasErrors('course_id');

    $this->post(route('teacher.grades.store'), [
        'course_id' => $course->id,
        'student_id' => $student->id,
        'assessment_name' => 'Note hors barème',
        'assessment_type' => 'examen',
        'score' => '21',
        'assessed_on' => now()->toDateString(),
    ])->assertSessionHasErrors('score');

    $this->delete(route('teacher.grades.destroy', $grade))
        ->assertRedirect(route('teacher.grades.index', ['course_id' => $course->id]));

    expect(\App\Models\CourseGrade::count())->toBe(0);
});

test('teachers can record dated attendance only for their session roster and students see only their own records', function () {
    $teacherUser = User::factory()->create(['role' => 'teacher']);
    $otherTeacherUser = User::factory()->create(['role' => 'teacher']);
    $studentUser = User::factory()->create(['role' => 'student']);
    $otherStudentUser = User::factory()->create(['role' => 'student']);
    $department = Department::create(['name' => 'Département présence', 'code' => 'DPRE', 'is_active' => true]);
    $formation = Formation::create([
        'department_id' => $department->id,
        'name' => 'Formation présence',
        'code' => 'FPRE',
        'is_active' => true,
    ]);
    $academicClass = AcademicClass::create([
        'formation_id' => $formation->id,
        'name' => 'Classe présence',
        'code' => 'CPRE',
        'capacity' => 30,
        'is_active' => true,
    ]);
    $group = StudentGroup::create([
        'academic_class_id' => $academicClass->id,
        'name' => 'Groupe présence',
        'code' => 'GPRE',
        'capacity' => 30,
    ]);
    $teacher = Teacher::create([
        'user_id' => $teacherUser->id,
        'department_id' => $department->id,
        'first_name' => 'Mariam',
        'last_name' => 'Présence',
        'email' => 'mariam.presence@example.com',
        'weekly_hours' => 10,
        'is_active' => true,
    ]);
    Teacher::create([
        'user_id' => $otherTeacherUser->id,
        'department_id' => $department->id,
        'first_name' => 'Ibrahima',
        'last_name' => 'Autre',
        'email' => 'ibrahima.presence@example.com',
        'weekly_hours' => 10,
        'is_active' => true,
    ]);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'academic_class_id' => $academicClass->id,
        'group_id' => $group->id,
        'matricule' => 'PRES-ETU-001',
        'first_name' => 'Awa',
        'last_name' => 'Présente',
        'status' => 'active',
    ]);
    $otherStudent = Student::create([
        'user_id' => $otherStudentUser->id,
        'academic_class_id' => $academicClass->id,
        'group_id' => $group->id,
        'matricule' => 'PRES-ETU-002',
        'first_name' => 'Fatou',
        'last_name' => 'Autre',
        'status' => 'active',
    ]);
    $course = Course::create([
        'department_id' => $department->id,
        'formation_id' => $formation->id,
        'code' => 'PRE101',
        'name' => 'Cours avec appel',
        'credits' => 3,
        'weekly_hours' => 2,
        'is_active' => true,
    ]);
    $teacher->courses()->attach($course);
    $student->courseEnrollments()->create(['course_id' => $course->id]);
    $otherStudent->courseEnrollments()->create(['course_id' => $course->id]);
    $session = Seance::create([
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'room_id' => Room::create([
            'name' => 'Salle présence',
            'code' => 'SPRE',
            'capacity' => 30,
            'type' => 'classroom',
            'status' => 'available',
        ])->id,
        'academic_class_id' => $academicClass->id,
        'group_id' => $group->id,
        'weekday' => now()->dayOfWeekIso,
        'starts_at' => '08:00',
        'ends_at' => '10:00',
        'type' => 'lecture',
    ]);

    $date = now()->toDateString();
    $this->actingAs($teacherUser)
        ->get(route('teacher.attendance.index', ['date' => $date]))
        ->assertOk()
        ->assertSee('Cours avec appel');

    $attendanceInput = [
        'attendance_date' => $date,
        'attendance' => [
            $student->id => ['status' => 'present', 'comment' => 'À l’heure.'],
            $otherStudent->id => ['status' => 'late', 'comment' => 'Arrivée tardive.'],
        ],
    ];
    $this->post(route('teacher.attendance.store', $session), $attendanceInput)
        ->assertRedirect(route('teacher.attendance.index', ['date' => $date, 'session_id' => $session->id]));

    expect(\App\Models\AttendanceRecord::count())->toBe(2);

    $this->post(route('teacher.attendance.store', $session), [
        ...$attendanceInput,
        'attendance' => [
            $student->id => ['status' => 'absent'],
            $otherStudent->id => ['status' => 'excused'],
        ],
    ])->assertRedirect(route('teacher.attendance.index', ['date' => $date, 'session_id' => $session->id]));

    expect(\App\Models\AttendanceRecord::count())->toBe(2)
        ->and(\App\Models\AttendanceRecord::where('student_id', $student->id)->value('status'))->toBe('absent');

    $this->actingAs($studentUser)
        ->get(route('student.attendance.index'))
        ->assertOk()
        ->assertSee('Absent')
        ->assertDontSee('Arrivée tardive.');

    $this->actingAs($otherStudentUser)
        ->get(route('student.attendance.index'))
        ->assertOk()
        ->assertSee('Absence justifiée')
        ->assertDontSee('À l’heure.');

    $this->actingAs($otherTeacherUser)
        ->post(route('teacher.attendance.store', $session), $attendanceInput)
        ->assertNotFound();

    $this->actingAs($teacherUser)
        ->post(route('teacher.attendance.store', $session), [
            'attendance_date' => now()->subDay()->toDateString(),
            'attendance' => [
                $student->id => ['status' => 'present'],
                $otherStudent->id => ['status' => 'present'],
            ],
        ])->assertSessionHasErrors('attendance_date');
});

test('student dashboards can display the monthly academic agenda', function () {
    $student = User::factory()->create(['role' => 'student']);
    Student::create([
        'user_id' => $student->id,
        'matricule' => 'ETU-2026-001',
        'first_name' => 'Awa',
        'last_name' => 'Fall',
    ]);
    AcademicEvent::create([
        'title' => 'Rentrée universitaire',
        'type' => 'academic',
        'description' => 'Accueil des étudiants',
        'starts_at' => '2026-10-15 09:00:00',
        'ends_at' => '2026-10-15 12:00:00',
    ]);

    $this->actingAs($student)
        ->get(route('student.dashboard', ['view' => 'month', 'month' => '2026-10']))
        ->assertOk()
        ->assertSee('Agenda')
        ->assertSee('Rentrée universitaire');
});

test('dashboards reject unsupported calendar views', function () {
    $student = User::factory()->create(['role' => 'student']);

    $this->actingAs($student)
        ->get(route('student.dashboard', ['view' => 'year']))
        ->assertSessionHasErrors('view');
});

test('administrators can open the core management forms', function () {
    $administrator = User::factory()->create(['role' => 'super_admin']);
    $this->actingAs($administrator);

    foreach ([
        'admin.students.create',
        'admin.teachers.create',
        'admin.courses.create',
        'admin.rooms.create',
        'admin.seances.create',
        'admin.timetable',
        'admin.academic.faculties.create',
        'admin.academic.departments.create',
        'admin.academic.formations.create',
        'admin.academic.levels.create',
        'admin.academic.academic-years.create',
        'admin.academic.semesters.create',
        'admin.academic.classes.create',
        'admin.academic.classes.create',
        'admin.academic.groups.create',
        'admin.exams.create',
        'admin.events.create',
        'notifications.index',
        'admin.reports.activity',
    ] as $route) {
        $this->get(route($route))->assertOk();
    }
});

test('the university homepage shows the public presentation', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Le savoir ouvre')
        ->assertSee('Nos domaines d’apprentissage')
        ->assertSee('Vie universitaire');
});

test('academic catalog entries can be created and shown', function () {
    $administrator = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($administrator)
        ->post(route('admin.academic.faculties.store'), [
            'name' => 'Faculté des sciences',
            'code' => 'SCI',
            'description' => 'Sciences et technologies',
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.academic.faculties.index'));

    $this->get(route('admin.academic.faculties.index'))
        ->assertOk()
        ->assertSee('Faculté des sciences');
});

test('department managers can open the group form with only classes in their department', function () {
    $department = Department::create(['name' => 'Département test', 'code' => 'DPT', 'is_active' => true]);
    $formation = Formation::create([
        'department_id' => $department->id,
        'name' => 'Informatique',
        'code' => 'INFO',
        'is_active' => true,
    ]);
    AcademicClass::create([
        'formation_id' => $formation->id,
        'name' => 'Licence 1',
        'code' => 'L1',
        'capacity' => 30,
        'is_active' => true,
    ]);
    $manager = User::factory()->create([
        'role' => 'department_manager',
        'department_id' => $department->id,
    ]);

    $this->actingAs($manager)
        ->get(route('admin.academic.groups.create'))
        ->assertOk()
        ->assertSee('Licence 1');
});

test('timetable can be filtered by class and semester', function () {
    $department = Department::create(['name' => 'Département test', 'code' => 'DPT', 'is_active' => true]);
    $formation = Formation::create([
        'department_id' => $department->id,
        'name' => 'Informatique',
        'code' => 'INFO',
        'is_active' => true,
    ]);
    $classOne = AcademicClass::create([
        'formation_id' => $formation->id,
        'name' => 'Licence 1',
        'code' => 'L1',
        'capacity' => 30,
        'is_active' => true,
    ]);
    $classTwo = AcademicClass::create([
        'formation_id' => $formation->id,
        'name' => 'Licence 2',
        'code' => 'L2',
        'capacity' => 30,
        'is_active' => true,
    ]);
    $academicYear = AcademicYear::create([
        'name' => '2026-2027',
        'starts_on' => '2026-08-01',
        'ends_on' => '2027-07-31',
        'is_active' => true,
    ]);
    $semesterOne = Semester::create([
        'academic_year_id' => $academicYear->id,
        'name' => 'Semestre 1',
        'starts_on' => '2026-08-01',
        'ends_on' => '2026-12-31',
        'is_active' => true,
    ]);
    $semesterTwo = Semester::create([
        'academic_year_id' => $academicYear->id,
        'name' => 'Semestre 2',
        'starts_on' => '2027-01-01',
        'ends_on' => '2027-07-31',
        'is_active' => true,
    ]);
    $groupOne = StudentGroup::create(['academic_class_id' => $classOne->id, 'name' => 'Groupe A', 'code' => 'A', 'capacity' => 30]);
    $groupTwo = StudentGroup::create(['academic_class_id' => $classTwo->id, 'name' => 'Groupe B', 'code' => 'B', 'capacity' => 30]);
    $room = Room::create(['name' => 'Salle 1', 'code' => 'S1', 'capacity' => 40, 'type' => 'classroom', 'status' => 'available']);
    $matchingCourse = Course::create(['semester_id' => $semesterOne->id, 'code' => 'C1', 'name' => 'Cours attendu', 'credits' => 3, 'weekly_hours' => 2]);
    $otherClassCourse = Course::create(['semester_id' => $semesterOne->id, 'code' => 'C2', 'name' => 'Autre classe', 'credits' => 3, 'weekly_hours' => 2]);
    $otherSemesterCourse = Course::create(['semester_id' => $semesterTwo->id, 'code' => 'C3', 'name' => 'Autre semestre', 'credits' => 3, 'weekly_hours' => 2]);

    foreach ([
        [$matchingCourse, null, $classOne, $semesterOne],
        [$otherClassCourse, $groupTwo, $classTwo, $semesterOne],
        [$otherSemesterCourse, $groupOne, $classOne, $semesterTwo],
    ] as [$course, $group, $class, $semester]) {
        Seance::create([
            'course_id' => $course->id,
            'room_id' => $room->id,
            'group_id' => $group?->id,
            'academic_class_id' => $class->id,
            'semester_id' => $semester->id,
            'weekday' => 1,
            'starts_at' => '09:00',
            'ends_at' => '10:00',
            'type' => 'lecture',
        ]);
    }

    $administrator = User::factory()->create(['role' => 'super_admin']);
    $this->actingAs($administrator)
        ->get(route('admin.timetable', ['academic_class_id' => $classOne->id, 'semester_id' => $semesterOne->id]))
        ->assertOk()
        ->assertSee('Cours attendu')
        ->assertDontSee('Autre classe')
        ->assertDontSee('Autre semestre');

    $this->get(route('admin.timetable'))
        ->assertOk()
        ->assertSee('Autre semestre')
        ->assertDontSee('Cours attendu');
});

test('session conflicts link to teacher availability and room reservations', function () {
    $department = Department::create(['name' => 'Département test', 'code' => 'DPT', 'is_active' => true]);
    $formation = Formation::create([
        'department_id' => $department->id,
        'name' => 'Informatique',
        'code' => 'INFO',
        'is_active' => true,
    ]);
    $academicClass = AcademicClass::create([
        'formation_id' => $formation->id,
        'name' => 'Licence 1',
        'code' => 'L1',
        'capacity' => 30,
        'is_active' => true,
    ]);
    $academicYear = AcademicYear::create([
        'name' => '2026-2027',
        'starts_on' => now()->startOfMonth()->toDateString(),
        'ends_on' => now()->addMonths(10)->toDateString(),
        'is_active' => true,
    ]);
    $semester = Semester::create([
        'academic_year_id' => $academicYear->id,
        'name' => 'Semestre de test',
        'starts_on' => now()->startOfMonth()->toDateString(),
        'ends_on' => now()->addMonth()->endOfMonth()->toDateString(),
        'is_active' => true,
    ]);
    $course = Course::create([
        'department_id' => $department->id,
        'formation_id' => $formation->id,
        'semester_id' => $semester->id,
        'code' => 'C101',
        'name' => 'Cours de test',
        'credits' => 3,
        'weekly_hours' => 2,
    ]);
    $teacher = Teacher::create([
        'department_id' => $department->id,
        'first_name' => 'Awa',
        'last_name' => 'Fall',
        'weekly_hours' => 4,
    ]);
    TeacherAvailability::create([
        'teacher_id' => $teacher->id,
        'weekday' => 1,
        'starts_at' => '10:00',
        'ends_at' => '12:00',
    ]);
    $room = Room::create([
        'name' => 'Salle réservée',
        'code' => 'R-101',
        'capacity' => 40,
        'type' => 'classroom',
        'status' => 'available',
    ]);
    $examDate = now()->next(\Carbon\Carbon::MONDAY);
    Exam::create([
        'course_id' => $course->id,
        'room_id' => $room->id,
        'name' => 'Examen de contrôle',
        'type' => 'exam',
        'starts_at' => $examDate->copy()->setTime(9, 0),
        'ends_at' => $examDate->copy()->setTime(10, 0),
        'expected_students' => 20,
    ]);
    $administrator = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($administrator)
        ->from(route('admin.seances.create'))
        ->post(route('admin.seances.store'), [
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'room_id' => $room->id,
            'academic_class_id' => $academicClass->id,
            'semester_id' => $semester->id,
            'weekday' => 1,
            'starts_at' => '09:00',
            'ends_at' => '10:00',
            'type' => 'lecture',
        ])
        ->assertRedirect(route('admin.seances.create'))
        ->assertSessionHasErrors(['teacher_id', 'room_id']);

    $this->get(route('admin.seances.create'))
        ->assertOk()
        ->assertSee(route('admin.timetable', [
            'teacher_id' => $teacher->id,
            'semester_id' => $semester->id,
            'weekday' => 1,
        ]))
        ->assertSee(route('admin.timetable', [
            'room_id' => $room->id,
            'semester_id' => $semester->id,
            'weekday' => 1,
        ]));

    $this->get(route('admin.timetable', [
        'teacher_id' => $teacher->id,
        'semester_id' => $semester->id,
        'weekday' => 1,
    ]))
        ->assertOk()
        ->assertSee('Disponibilités de Awa Fall')
        ->assertSee('10:00');

    $this->get(route('admin.timetable', [
        'room_id' => $room->id,
        'semester_id' => $semester->id,
        'weekday' => 1,
    ]))
        ->assertOk()
        ->assertSee('Examen de contrôle')
        ->assertSee('R-101');
});

test('session schedule changes notify the authorized administrators in the affected department', function () {
    $department = Department::create(['name' => 'Département test', 'code' => 'DPT', 'is_active' => true]);
    $otherDepartment = Department::create(['name' => 'Autre département', 'code' => 'AUT', 'is_active' => true]);
    $formation = Formation::create([
        'department_id' => $department->id,
        'name' => 'Informatique',
        'code' => 'INFO',
        'is_active' => true,
    ]);
    $academicClass = AcademicClass::create([
        'formation_id' => $formation->id,
        'name' => 'Licence 1',
        'code' => 'L1',
        'capacity' => 30,
        'is_active' => true,
    ]);
    $administrator = User::factory()->create(['role' => 'super_admin']);
    $globalAdministrators = collect(['admin', 'academic_direction', 'studies_manager'])
        ->map(fn ($role) => User::factory()->create(['role' => $role]));
    $departmentManager = User::factory()->create([
        'role' => 'department_manager',
        'department_id' => $department->id,
    ]);
    $otherDepartmentManager = User::factory()->create([
        'role' => 'department_manager',
        'department_id' => $otherDepartment->id,
    ]);
    $hrAdministrator = User::factory()->create(['role' => 'hr_admin']);
    $itAdministrator = User::factory()->create(['role' => 'it_admin']);
    $teacherUser = User::factory()->create(['role' => 'teacher']);
    $teacher = Teacher::create([
        'user_id' => $teacherUser->id,
        'department_id' => $department->id,
        'first_name' => 'Awa',
        'last_name' => 'Fall',
        'weekly_hours' => 4,
    ]);
    $course = Course::create([
        'department_id' => $department->id,
        'code' => 'DPT101',
        'name' => 'Cours départemental',
        'credits' => 3,
        'weekly_hours' => 2,
    ]);
    $room = Room::create(['name' => 'Salle 1', 'code' => 'S1', 'capacity' => 40, 'type' => 'classroom', 'status' => 'available']);

    $this->actingAs($administrator)
        ->post(route('admin.seances.store'), [
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'room_id' => $room->id,
            'academic_class_id' => $academicClass->id,
            'weekday' => 1,
            'starts_at' => '09:00',
            'ends_at' => '10:00',
            'type' => 'lecture',
        ])
        ->assertRedirect(route('admin.seances.index'));

    foreach ([$administrator, ...$globalAdministrators, $departmentManager, $teacherUser] as $recipient) {
        expect($recipient->notifications()->count())->toBe(1);
    }
    expect($otherDepartmentManager->notifications()->count())->toBe(0)
        ->and($hrAdministrator->notifications()->count())->toBe(0)
        ->and($itAdministrator->notifications()->count())->toBe(0);
});

test('administrators can mark a notification as read', function () {
    $administrator = User::factory()->create(['role' => 'super_admin']);
    $administrator->notify(new SessionScheduleChanged('Une séance a été modifiée.'));
    $notification = $administrator->notifications()->firstOrFail();

    $this->actingAs($administrator)
        ->post(route('notifications.read', $notification->id))
        ->assertRedirect(route('notifications.index'))
        ->assertSessionHas('success');

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('administrators can manage records while the director general has read-only access', function () {
    $administrator = User::factory()->create(['role' => 'super_admin']);
    $faculty = Faculty::create(['name' => 'Faculté de test', 'code' => 'FDT', 'is_active' => true]);
    $department = Department::create(['faculty_id' => $faculty->id, 'name' => 'Département de test', 'code' => 'DDT', 'is_active' => true]);
    $formation = Formation::create(['department_id' => $department->id, 'name' => 'Formation de test', 'code' => 'FDT-1', 'is_active' => true]);
    $level = Level::create(['formation_id' => $formation->id, 'name' => 'Licence 1', 'position' => 1]);
    $academicYear = AcademicYear::create([
        'name' => '2026-2027',
        'starts_on' => '2026-08-01',
        'ends_on' => '2027-07-31',
        'is_active' => true,
    ]);
    $semester = Semester::create([
        'academic_year_id' => $academicYear->id,
        'name' => 'Semestre 1',
        'starts_on' => '2026-08-01',
        'ends_on' => '2026-12-31',
        'is_active' => true,
    ]);
    $class = AcademicClass::create([
        'formation_id' => $formation->id,
        'level_id' => $level->id,
        'name' => 'Classe de test',
        'code' => 'CDT',
        'capacity' => 30,
        'is_active' => true,
    ]);
    $group = StudentGroup::create([
        'academic_class_id' => $class->id,
        'name' => 'Groupe A',
        'code' => 'GA',
        'capacity' => 30,
    ]);
    $teacher = Teacher::create([
        'first_name' => 'Aminata',
        'last_name' => 'Test',
        'email' => 'aminata.test@example.com',
        'weekly_hours' => 10,
        'is_active' => true,
    ]);
    $student = Student::create([
        'matricule' => 'TEST-2026-001',
        'first_name' => 'Moussa',
        'last_name' => 'Test',
        'status' => 'active',
    ]);
    $course = Course::create([
        'department_id' => $department->id,
        'formation_id' => $formation->id,
        'semester_id' => $semester->id,
        'code' => 'CDT101',
        'name' => 'Cours de test',
        'credits' => 3,
        'weekly_hours' => 2,
    ]);
    $room = Room::create([
        'name' => 'Salle de test',
        'code' => 'SDT',
        'capacity' => 30,
        'type' => 'classroom',
        'status' => 'available',
    ]);
    $session = Seance::create([
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'room_id' => $room->id,
        'group_id' => $group->id,
        'semester_id' => $semester->id,
        'weekday' => 1,
        'starts_at' => '08:00',
        'ends_at' => '10:00',
        'type' => 'lecture',
    ]);
    $exam = Exam::create([
        'course_id' => $course->id,
        'room_id' => $room->id,
        'name' => 'Examen de test',
        'type' => 'exam',
        'starts_at' => '2026-11-01 08:00:00',
        'ends_at' => '2026-11-01 10:00:00',
        'expected_students' => 30,
    ]);
    $event = AcademicEvent::create([
        'title' => 'Événement de test',
        'type' => 'event',
        'starts_at' => '2026-10-15 09:00:00',
        'ends_at' => '2026-10-15 10:00:00',
    ]);

    $this->actingAs($administrator);

    foreach ([
        'faculties' => $faculty,
        'departments' => $department,
        'formations' => $formation,
        'levels' => $level,
        'academic-years' => $academicYear,
        'semesters' => $semester,
        'classes' => $class,
        'groups' => $group,
    ] as $entity => $record) {
        $this->get(route("admin.academic.{$entity}.show", $record->id))->assertOk();
        $this->get(route("admin.academic.{$entity}.edit", $record->id))->assertOk();
    }

    foreach ([
        ['admin.students.show', $student],
        ['admin.students.edit', $student],
        ['admin.teachers.show', $teacher],
        ['admin.teachers.edit', $teacher],
        ['admin.courses.show', $course],
        ['admin.courses.edit', $course],
        ['admin.rooms.show', $room],
        ['admin.rooms.edit', $room],
        ['admin.seances.show', $session],
        ['admin.seances.edit', $session],
        ['admin.exams.edit', $exam],
        ['admin.events.edit', $event],
    ] as [$route, $record]) {
        $this->get(route($route, $record))->assertOk();
    }

    $director = User::factory()->create(['role' => 'director_general']);
    $this->actingAs($director);

    foreach ([
        'admin.dashboard',
        'admin.academic.faculties.index',
        'admin.academic.departments.index',
        'admin.academic.formations.index',
        'admin.academic.levels.index',
        'admin.academic.academic-years.index',
        'admin.academic.semesters.index',
        'admin.academic.classes.index',
        'admin.academic.groups.index',
        'admin.students.index',
        'admin.teachers.index',
        'admin.courses.index',
        'admin.rooms.index',
        'admin.seances.index',
        'admin.exams.index',
        'admin.events.index',
        'admin.timetable',
        'admin.reports.activity',
    ] as $route) {
        $this->get(route($route))->assertOk();
    }

    foreach ([
        ['admin.academic.faculties.show', $faculty],
        ['admin.academic.departments.show', $department],
        ['admin.academic.formations.show', $formation],
        ['admin.academic.levels.show', $level],
        ['admin.academic.academic-years.show', $academicYear],
        ['admin.academic.semesters.show', $semester],
        ['admin.academic.classes.show', $class],
        ['admin.academic.groups.show', $group],
        ['admin.students.show', $student],
        ['admin.teachers.show', $teacher],
        ['admin.courses.show', $course],
        ['admin.rooms.show', $room],
        ['admin.seances.show', $session],
    ] as [$route, $record]) {
        $this->get(route($route, $record))->assertOk();
    }

    foreach ([
        ['admin.academic.faculties.index', 'admin.academic.faculties.create', 'admin.academic.faculties.edit', $faculty],
        ['admin.students.index', 'admin.students.create', 'admin.students.edit', $student],
        ['admin.teachers.index', 'admin.teachers.create', 'admin.teachers.edit', $teacher],
        ['admin.courses.index', 'admin.courses.create', 'admin.courses.edit', $course],
        ['admin.rooms.index', 'admin.rooms.create', 'admin.rooms.edit', $room],
        ['admin.seances.index', 'admin.seances.create', 'admin.seances.edit', $session],
        ['admin.exams.index', 'admin.exams.create', 'admin.exams.edit', $exam],
        ['admin.events.index', 'admin.events.create', 'admin.events.edit', $event],
    ] as [$indexRoute, $createRoute, $editRoute, $record]) {
        $this->get(route($indexRoute))
            ->assertOk()
            ->assertDontSee(route($createRoute))
            ->assertDontSee(route($editRoute, $record));
    }

    foreach ([
        ['GET', 'admin.academic.faculties.create', null],
        ['GET', 'admin.academic.faculties.edit', $faculty],
        ['GET', 'admin.students.create', null],
        ['GET', 'admin.students.edit', $student],
        ['GET', 'admin.teachers.create', null],
        ['GET', 'admin.teachers.edit', $teacher],
        ['GET', 'admin.courses.create', null],
        ['GET', 'admin.courses.edit', $course],
        ['GET', 'admin.rooms.create', null],
        ['GET', 'admin.rooms.edit', $room],
        ['GET', 'admin.seances.create', null],
        ['GET', 'admin.seances.edit', $session],
        ['GET', 'admin.exams.create', null],
        ['GET', 'admin.exams.edit', $exam],
        ['GET', 'admin.events.create', null],
        ['GET', 'admin.events.edit', $event],
    ] as [$method, $route, $record]) {
        $response = $this->call($method, route($route, $record));
        $this->assertSame(403, $response->status(), $route.': '.$response->getContent());
    }

    $this->post(route('admin.academic.faculties.store'))->assertForbidden();
    $this->put(route('admin.academic.faculties.update', $faculty))->assertForbidden();
    $this->delete(route('admin.academic.faculties.destroy', $faculty))->assertForbidden();
    $this->post(route('admin.students.store'))->assertForbidden();
    $this->delete(route('admin.students.destroy', $student))->assertForbidden();
    $this->post(route('admin.teachers.store'))->assertForbidden();
    $this->put(route('admin.teachers.update', $teacher))->assertForbidden();
    $this->post(route('admin.courses.store'))->assertForbidden();
    $this->put(route('admin.rooms.update', $room))->assertForbidden();
    $this->post(route('admin.seances.store'))->assertForbidden();
    $this->delete(route('admin.exams.destroy', $exam))->assertForbidden();
    $this->post(route('admin.events.store'))->assertForbidden();
    $this->post(route('admin.timetable.generate'))->assertForbidden();
});

test('academic direction can manage academic operations but only view rooms', function () {
    $academicDirector = User::factory()->create(['role' => 'academic_direction']);
    $faculty = Faculty::create(['name' => 'Faculté de consultation', 'code' => 'FC', 'is_active' => true]);
    $room = Room::create([
        'name' => 'Salle consultable',
        'code' => 'SC-01',
        'capacity' => 30,
        'type' => 'classroom',
        'status' => 'available',
    ]);

    $this->actingAs($academicDirector);

    foreach ([
        'admin.dashboard',
        'admin.academic.faculties.index',
        'admin.academic.faculties.create',
        'admin.academic.faculties.edit',
        'admin.students.index',
        'admin.students.create',
        'admin.teachers.index',
        'admin.teachers.create',
        'admin.courses.index',
        'admin.courses.create',
        'admin.seances.index',
        'admin.seances.create',
        'admin.exams.index',
        'admin.exams.create',
        'admin.events.index',
        'admin.events.create',
        'admin.timetable',
        'admin.reports.activity',
    ] as $route) {
        $parameters = $route === 'admin.academic.faculties.edit' ? [$faculty->id] : [];
        $response = $this->get(route($route, ...$parameters));
        $this->assertSame(200, $response->status(), $route.': '.$response->getContent());
    }

    $this->get(route('admin.rooms.index'))
        ->assertOk()
        ->assertDontSee(route('admin.rooms.create'))
        ->assertDontSee(route('admin.rooms.edit', $room));
    $this->get(route('admin.rooms.show', $room))->assertOk();

    $this->post(route('admin.academic.faculties.store'), [
        'name' => 'Faculté créée par la direction',
        'code' => 'FCD',
        'is_active' => '1',
    ])->assertRedirect(route('admin.academic.faculties.index'));

    $this->get(route('admin.rooms.create'))->assertForbidden();
    $this->get(route('admin.rooms.edit', $room))->assertForbidden();
    $this->post(route('admin.rooms.store'))->assertForbidden();
    $this->put(route('admin.rooms.update', $room))->assertForbidden();
    $this->delete(route('admin.rooms.destroy', $room))->assertForbidden();
    $this->get(route('admin.invitations.index'))->assertForbidden();
});

test('studies managers can manage students, courses, sessions and exams with read-only access elsewhere', function () {
    $manager = User::factory()->create(['role' => 'studies_manager']);
    $faculty = Faculty::create(['name' => 'Faculté consultable', 'code' => 'FCM', 'is_active' => true]);
    $teacher = Teacher::create([
        'first_name' => 'Fatou',
        'last_name' => 'Consultable',
        'email' => 'fatou.consultable@example.com',
        'weekly_hours' => 8,
        'is_active' => true,
    ]);
    $room = Room::create([
        'name' => 'Salle consultable études',
        'code' => 'SCE-01',
        'capacity' => 25,
        'type' => 'classroom',
        'status' => 'available',
    ]);
    $event = AcademicEvent::create([
        'title' => 'Événement consultable',
        'type' => 'event',
        'starts_at' => '2026-10-15 09:00:00',
        'ends_at' => '2026-10-15 10:00:00',
    ]);

    $this->actingAs($manager);

    foreach ([
        'admin.dashboard',
        'admin.academic.faculties.index',
        'admin.students.index',
        'admin.students.create',
        'admin.teachers.index',
        'admin.courses.index',
        'admin.courses.create',
        'admin.rooms.index',
        'admin.seances.index',
        'admin.seances.create',
        'admin.exams.index',
        'admin.exams.create',
        'admin.events.index',
        'admin.timetable',
        'admin.reports.activity',
    ] as $route) {
        $this->get(route($route))->assertOk();
    }

    $this->get(route('admin.teachers.show', $teacher))->assertOk();
    $this->get(route('admin.rooms.show', $room))->assertOk();
    $this->get(route('admin.events.index'))
        ->assertOk()
        ->assertSee($event->title)
        ->assertDontSee(route('admin.events.create'));
    $this->get(route('admin.rooms.index'))
        ->assertDontSee(route('admin.rooms.create'));
    $this->get(route('admin.teachers.index'))
        ->assertDontSee(route('admin.teachers.create'));
    $this->get(route('admin.academic.faculties.index'))
        ->assertDontSee(route('admin.academic.faculties.create'));

    $this->post(route('admin.students.store'), [
        'matricule' => 'ETU-2026-ETU',
        'first_name' => 'Aminata',
        'last_name' => 'Études',
        'status' => 'active',
    ])->assertRedirect(route('admin.students.index'));

    $this->post(route('admin.courses.store'), [
        'code' => 'ETU101',
        'name' => 'Cours géré par les études',
        'credits' => 3,
        'weekly_hours' => 2,
    ])->assertRedirect(route('admin.courses.index'));

    $this->get(route('admin.academic.faculties.create'))->assertForbidden();
    $this->get(route('admin.academic.faculties.edit', $faculty))->assertForbidden();
    $this->get(route('admin.teachers.create'))->assertForbidden();
    $this->get(route('admin.rooms.create'))->assertForbidden();
    $this->get(route('admin.rooms.edit', $room))->assertForbidden();
    $this->post(route('admin.rooms.store'))->assertForbidden();
    $this->delete(route('admin.rooms.destroy', $room))->assertForbidden();
    $this->get(route('admin.events.create'))->assertForbidden();
    $this->get(route('admin.invitations.index'))->assertForbidden();
});

test('department managers are restricted to their assigned department records', function () {
    $faculty = Faculty::create(['name' => 'Faculté commune', 'code' => 'FCM', 'is_active' => true]);
    $firstDepartment = Department::create(['faculty_id' => $faculty->id, 'name' => 'Département A', 'code' => 'DPA', 'is_active' => true]);
    $secondDepartment = Department::create(['faculty_id' => $faculty->id, 'name' => 'Département B', 'code' => 'DPB', 'is_active' => true]);
    $firstFormation = Formation::create(['department_id' => $firstDepartment->id, 'name' => 'Formation A', 'code' => 'FA', 'is_active' => true]);
    $secondFormation = Formation::create(['department_id' => $secondDepartment->id, 'name' => 'Formation B', 'code' => 'FB', 'is_active' => true]);
    $firstClass = AcademicClass::create([
        'formation_id' => $firstFormation->id,
        'name' => 'Classe A',
        'code' => 'CLA',
        'capacity' => 30,
        'is_active' => true,
    ]);
    $secondClass = AcademicClass::create([
        'formation_id' => $secondFormation->id,
        'name' => 'Classe B',
        'code' => 'CLB',
        'capacity' => 30,
        'is_active' => true,
    ]);
    $firstGroup = StudentGroup::create(['academic_class_id' => $firstClass->id, 'name' => 'Groupe A', 'code' => 'GA', 'capacity' => 30]);
    $secondGroup = StudentGroup::create(['academic_class_id' => $secondClass->id, 'name' => 'Groupe B', 'code' => 'GB', 'capacity' => 30]);
    $firstTeacher = Teacher::create([
        'department_id' => $firstDepartment->id,
        'first_name' => 'Enseignant',
        'last_name' => 'A',
        'email' => 'enseignant.a@example.com',
        'weekly_hours' => 10,
        'is_active' => true,
    ]);
    $secondTeacher = Teacher::create([
        'department_id' => $secondDepartment->id,
        'first_name' => 'Enseignant',
        'last_name' => 'B',
        'email' => 'enseignant.b@example.com',
        'weekly_hours' => 10,
        'is_active' => true,
    ]);
    $firstStudent = Student::create([
        'academic_class_id' => $firstClass->id,
        'group_id' => $firstGroup->id,
        'matricule' => 'DPA-ETU-1',
        'first_name' => 'Étudiant',
        'last_name' => 'A',
        'status' => 'active',
    ]);
    $secondStudent = Student::create([
        'academic_class_id' => $secondClass->id,
        'group_id' => $secondGroup->id,
        'matricule' => 'DPB-ETU-1',
        'first_name' => 'Étudiant',
        'last_name' => 'B',
        'status' => 'active',
    ]);
    $firstCourse = Course::create([
        'department_id' => $firstDepartment->id,
        'formation_id' => $firstFormation->id,
        'code' => 'DPA101',
        'name' => 'Cours département A',
        'credits' => 3,
        'weekly_hours' => 2,
    ]);
    $secondCourse = Course::create([
        'department_id' => $secondDepartment->id,
        'formation_id' => $secondFormation->id,
        'code' => 'DPB101',
        'name' => 'Cours département B',
        'credits' => 3,
        'weekly_hours' => 2,
    ]);
    $room = Room::create([
        'name' => 'Salle commune',
        'code' => 'SCOM',
        'capacity' => 40,
        'type' => 'classroom',
        'status' => 'available',
    ]);
    $firstSession = Seance::create([
        'course_id' => $firstCourse->id,
        'teacher_id' => $firstTeacher->id,
        'room_id' => $room->id,
        'group_id' => $firstGroup->id,
        'weekday' => 1,
        'starts_at' => '08:00',
        'ends_at' => '10:00',
        'type' => 'lecture',
    ]);
    $secondSession = Seance::create([
        'course_id' => $secondCourse->id,
        'teacher_id' => $secondTeacher->id,
        'room_id' => $room->id,
        'group_id' => $secondGroup->id,
        'weekday' => 2,
        'starts_at' => '08:00',
        'ends_at' => '10:00',
        'type' => 'lecture',
    ]);
    $firstExam = Exam::create([
        'course_id' => $firstCourse->id,
        'room_id' => $room->id,
        'group_id' => $firstGroup->id,
        'name' => 'Examen département A',
        'type' => 'exam',
        'starts_at' => now()->addDays(10),
        'ends_at' => now()->addDays(10)->addHours(2),
        'expected_students' => 25,
    ]);
    Exam::create([
        'course_id' => $secondCourse->id,
        'room_id' => $room->id,
        'group_id' => $secondGroup->id,
        'name' => 'Examen département B',
        'type' => 'exam',
        'starts_at' => now()->addDays(11),
        'ends_at' => now()->addDays(11)->addHours(2),
        'expected_students' => 25,
    ]);
    $manager = User::factory()->create([
        'role' => 'department_manager',
        'department_id' => $firstDepartment->id,
    ]);

    $this->actingAs($manager);

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(route('admin.students.index'))
        ->assertSee('Étudiants');

    $this->get(route('admin.academic.departments.index'))
        ->assertOk()
        ->assertSee('Département A')
        ->assertDontSee('Département B');
    $this->get(route('admin.academic.formations.index'))
        ->assertOk()
        ->assertSee('Formation A')
        ->assertDontSee('Formation B');
    $this->get(route('admin.students.index'))
        ->assertOk()
        ->assertSee($firstStudent->matricule)
        ->assertDontSee($secondStudent->matricule);
    $this->get(route('admin.teachers.index'))
        ->assertOk()
        ->assertSee('enseignant.a@example.com')
        ->assertDontSee('enseignant.b@example.com');
    $this->get(route('admin.courses.index'))
        ->assertOk()
        ->assertSee('Cours département A')
        ->assertDontSee('Cours département B');
    $this->get(route('admin.seances.index'))
        ->assertOk()
        ->assertSee('Cours département A')
        ->assertDontSee('Cours département B')
        ->assertSee('class="active" href="'.route('admin.seances.index').'"', false)
        ->assertSee('class="" href="'.route('admin.timetable').'"', false);
    $this->get(route('admin.exams.index'))
        ->assertOk()
        ->assertSee('Examen département A')
        ->assertDontSee('Examen département B');
    $this->get(route('admin.timetable'))
        ->assertOk()
        ->assertSee('Cours département A')
        ->assertDontSee('Cours département B');

    $this->get(route('admin.students.show', $secondStudent))->assertNotFound();
    $this->get(route('admin.teachers.show', $secondTeacher))->assertNotFound();
    $this->get(route('admin.courses.show', $secondCourse))->assertNotFound();
    $this->get(route('admin.seances.show', $secondSession))->assertNotFound();

    $this->get(route('admin.rooms.index'))->assertOk()->assertSee('Salle commune');
    $this->get(route('admin.rooms.edit', $room))->assertForbidden();
    $this->get(route('admin.events.create'))->assertForbidden();
    $this->get(route('admin.reports.activity'))->assertForbidden();
    $this->get(route('admin.invitations.index'))->assertForbidden();
    $this->post(route('admin.timetable.generate'))->assertForbidden();

    $this->post(route('admin.courses.store'), [
        'department_id' => $secondDepartment->id,
        'formation_id' => $secondFormation->id,
        'code' => 'CROSS-01',
        'name' => 'Cours hors département',
        'credits' => 3,
        'weekly_hours' => 2,
    ])->assertForbidden();

    expect($firstSession->fresh())->not->toBeNull();
});

test('human resources administrators can manage teachers and consult courses and schedules only', function () {
    $hr = User::factory()->create(['role' => 'hr_admin']);

    $this->actingAs($hr)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(route('admin.teachers.index'))
        ->assertSee(route('admin.courses.index'))
        ->assertSee(route('admin.timetable'))
        ->assertDontSee(route('admin.students.index'))
        ->assertDontSee(route('admin.rooms.index'))
        ->assertDontSee(route('admin.exams.index'));

    $this->get(route('admin.teachers.index'))->assertOk();
    $this->get(route('admin.teachers.create'))->assertOk();
    $this->get(route('admin.courses.index'))->assertOk();
    $this->get(route('admin.seances.index'))->assertOk();
    $this->get(route('admin.timetable'))->assertOk();

    $this->get(route('admin.students.index'))
        ->assertForbidden();
    $this->get(route('admin.rooms.index'))->assertForbidden();
    $this->get(route('admin.exams.index'))->assertForbidden();
    $this->get(route('admin.events.index'))->assertForbidden();
    $this->get(route('admin.courses.create'))->assertForbidden();
    $this->get(route('admin.seances.create'))->assertForbidden();
    $this->post(route('admin.timetable.generate'))->assertForbidden();
});

test('information technology administrators can manage rooms and consult courses and schedules only', function () {
    $itAdmin = User::factory()->create(['role' => 'it_admin']);

    $this->actingAs($itAdmin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(route('admin.rooms.index'))
        ->assertSee(route('admin.courses.index'))
        ->assertSee(route('admin.timetable'))
        ->assertDontSee(route('admin.students.index'))
        ->assertDontSee(route('admin.teachers.index'))
        ->assertDontSee(route('admin.exams.index'));

    $this->get(route('admin.rooms.index'))->assertOk();
    $this->get(route('admin.rooms.create'))->assertOk();
    $this->get(route('admin.courses.index'))->assertOk();
    $this->get(route('admin.seances.index'))->assertOk();
    $this->get(route('admin.timetable'))->assertOk();

    $this->get(route('admin.students.index'))->assertForbidden();
    $this->get(route('admin.teachers.index'))->assertForbidden();
    $this->get(route('admin.exams.index'))->assertForbidden();
    $this->get(route('admin.events.index'))->assertForbidden();
    $this->get(route('admin.courses.create'))->assertForbidden();
    $this->get(route('admin.seances.create'))->assertForbidden();
    $this->post(route('admin.timetable.generate'))->assertForbidden();
});

test('registrar can manage students and only consult the permitted academic modules', function () {
    $registrar = User::factory()->create(['role' => 'registrar']);
    $this->actingAs($registrar);

    foreach ([
        'admin.dashboard',
        'admin.academic.faculties.index',
        'admin.academic.departments.index',
        'admin.academic.formations.index',
        'admin.academic.levels.index',
        'admin.academic.academic-years.index',
        'admin.academic.semesters.index',
        'admin.academic.classes.index',
        'admin.academic.groups.index',
        'admin.students.index',
        'admin.courses.index',
        'admin.rooms.index',
        'admin.seances.index',
        'admin.exams.index',
        'admin.events.index',
        'admin.timetable',
    ] as $route) {
        $this->get(route($route))->assertOk();
    }

    $this->get(route('admin.students.create'))->assertOk();
    $this->post(route('admin.students.store'), [
        'matricule' => 'REG-2026-001',
        'first_name' => 'Aminata',
        'last_name' => 'Fall',
        'status' => 'active',
    ])->assertRedirect(route('admin.students.index'));

    $student = Student::where('matricule', 'REG-2026-001')->firstOrFail();
    $this->get(route('admin.students.edit', $student))->assertOk();
    $this->put(route('admin.students.update', $student), [
        'matricule' => 'REG-2026-001',
        'first_name' => 'Aminata',
        'last_name' => 'Ndiaye',
        'status' => 'active',
    ])->assertRedirect(route('admin.students.index'));
    $this->delete(route('admin.students.destroy', $student))
        ->assertRedirect(route('admin.students.index'));
    expect(Student::find($student->id))->toBeNull();

    foreach ([
        'admin.academic.faculties.create',
        'admin.academic.departments.create',
        'admin.academic.formations.create',
        'admin.academic.levels.create',
        'admin.academic.academic-years.create',
        'admin.academic.semesters.create',
        'admin.academic.classes.create',
        'admin.academic.groups.create',
        'admin.courses.create',
        'admin.seances.create',
        'admin.exams.create',
        'admin.rooms.create',
        'admin.events.create',
    ] as $route) {
        $this->get(route($route))->assertForbidden();
    }

    $this->post(route('admin.timetable.generate'))->assertForbidden();
    $this->get(route('admin.reports.activity'))->assertForbidden();
    $this->get(route('admin.teachers.index'))->assertForbidden();

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee(route('admin.seances.create'))
        ->assertDontSee(route('admin.reports.activity'));
    $this->get(route('admin.academic.faculties.index'))
        ->assertDontSee(route('admin.academic.faculties.create'));
    $this->get(route('admin.courses.index'))->assertDontSee(route('admin.courses.create'));
});

test('administrators can create a teacher login account with the teacher profile', function () {
    $administrator = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($administrator)
        ->post(route('admin.teachers.store'), [
            'first_name' => 'Mariam',
            'last_name' => 'Diallo',
            'email' => 'mariam.diallo@example.com',
            'weekly_hours' => 12,
            'is_active' => '1',
            'create_account' => '1',
            'account_password' => 'Enseignant-2026!',
            'account_password_confirmation' => 'Enseignant-2026!',
        ])
        ->assertRedirect(route('admin.teachers.index'));

    $teacherUser = User::where('email', 'mariam.diallo@example.com')->firstOrFail();
    expect($teacherUser->role)->toBe('teacher')
        ->and($teacherUser->teacher)->not->toBeNull();

    $this->post(route('logout'));
    $this->post(route('login'), [
        'email' => $teacherUser->email,
        'password' => 'Enseignant-2026!',
    ])->assertRedirect(route('teacher.dashboard'));

    $this->get(route('teacher.dashboard'))->assertOk()->assertSee('Mon espace enseignant');
});

test('inactive accounts cannot authenticate', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');
});

test('the automatic timetable generator assigns a course to a valid room and teacher', function () {
    $administrator = User::factory()->create(['role' => 'super_admin']);
    $faculty = Faculty::create(['name' => 'Sciences', 'code' => 'SCI', 'is_active' => true]);
    $department = Department::create(['faculty_id' => $faculty->id, 'name' => 'Informatique', 'code' => 'INFO', 'is_active' => true]);
    $formation = Formation::create(['department_id' => $department->id, 'name' => 'Informatique', 'code' => 'LIC-INFO', 'is_active' => true]);
    $academicYear = AcademicYear::create([
        'name' => '2026-2027',
        'starts_on' => '2026-08-01',
        'ends_on' => '2027-07-31',
        'is_active' => true,
    ]);
    $semester = Semester::create([
        'academic_year_id' => $academicYear->id,
        'name' => 'Semestre 1',
        'starts_on' => '2026-08-01',
        'ends_on' => '2026-12-31',
        'is_active' => true,
    ]);
    $academicClass = AcademicClass::create([
        'formation_id' => $formation->id,
        'name' => 'Licence 1',
        'code' => 'L1-INFO',
        'capacity' => 20,
        'is_active' => true,
    ]);
    $group = StudentGroup::create([
        'academic_class_id' => $academicClass->id,
        'name' => 'Groupe A',
        'code' => 'A',
        'capacity' => 20,
    ]);
    $teacherUser = User::factory()->create(['role' => 'teacher']);
    $teacher = Teacher::create([
        'user_id' => $teacherUser->id,
        'department_id' => $department->id,
        'employee_number' => 'ENS-001',
        'first_name' => 'Awa',
        'last_name' => 'Fall',
        'weekly_hours' => 4,
        'is_active' => true,
    ]);
    $course = Course::create([
        'department_id' => $department->id,
        'formation_id' => $formation->id,
        'semester_id' => $semester->id,
        'code' => 'WEB101',
        'name' => 'Programmation Web',
        'credits' => 4,
        'weekly_hours' => 2,
        'is_active' => true,
    ]);
    $course->teachers()->attach($teacher);
    Room::create([
        'name' => 'Salle A204',
        'code' => 'A204',
        'capacity' => 30,
        'type' => 'classroom',
        'status' => 'available',
    ]);

    $this->actingAs($administrator)
        ->post(route('admin.timetable.generate'), ['semester_id' => $semester->id])
        ->assertRedirect(route('admin.timetable'))
        ->assertSessionHas('success');

    expect(Seance::query()->where('group_id', $group->id)->count())->toBe(1)
        ->and($teacherUser->notifications()->count())->toBe(1)
        ->and($administrator->notifications()->count())->toBe(1);
});
