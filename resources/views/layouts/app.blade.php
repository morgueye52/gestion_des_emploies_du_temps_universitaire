<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Performics University') · Performics University</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="university-app">
    @auth
        <div class="app-shell">
            <aside class="sidebar" id="appSidebar">
                <a class="brand" href="{{ route('dashboard') }}">
                    <span class="brand-mark"><i class="bi bi-mortarboard-fill"></i></span>
                    <span><strong>PERFORMICS</strong><small>UNIVERSITY</small></span>
                </a>
                @if(auth()->user()->hasRole(['admin', 'super_admin', 'director_general', 'academic_direction', 'studies_manager', 'department_manager', 'registrar', 'hr_admin', 'it_admin']))
                    <div class="sidebar-label">ESPACE ACADÉMIQUE</div>
                    <nav class="sidebar-nav">
                        <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="bi bi-grid-1x2"></i> Tableau de bord</a>
                    </nav>
                    @if(auth()->user()->hasRole(['admin','super_admin','director_general','academic_direction','studies_manager','department_manager','registrar']))
                        <div class="sidebar-label mt-3">UNIVERSITÉ</div>
                        <nav class="sidebar-nav">
                        <a class="{{ request()->routeIs('admin.academic.faculties.*') ? 'active' : '' }}" href="{{ route('admin.academic.faculties.index') }}"><i class="bi bi-bank"></i> Facultés</a>
                        <a class="{{ request()->routeIs('admin.academic.departments.*') ? 'active' : '' }}" href="{{ route('admin.academic.departments.index') }}"><i class="bi bi-diagram-3"></i> Départements</a>
                        <a class="{{ request()->routeIs('admin.academic.formations.*') ? 'active' : '' }}" href="{{ route('admin.academic.formations.index') }}"><i class="bi bi-mortarboard"></i> Formations</a>
                        <a class="{{ request()->routeIs('admin.academic.levels.*') ? 'active' : '' }}" href="{{ route('admin.academic.levels.index') }}"><i class="bi bi-bar-chart-steps"></i> Niveaux</a>
                        <a class="{{ request()->routeIs('admin.academic.classes.*') ? 'active' : '' }}" href="{{ route('admin.academic.classes.index') }}"><i class="bi bi-people"></i> Classes</a>
                        <a class="{{ request()->routeIs('admin.academic.groups.*') ? 'active' : '' }}" href="{{ route('admin.academic.groups.index') }}"><i class="bi bi-collection"></i> Groupes</a>
                        <a class="{{ request()->routeIs('admin.academic.academic-years.*') ? 'active' : '' }}" href="{{ route('admin.academic.academic-years.index') }}"><i class="bi bi-calendar-range"></i> Années universitaires</a>
                        <a class="{{ request()->routeIs('admin.academic.semesters.*') ? 'active' : '' }}" href="{{ route('admin.academic.semesters.index') }}"><i class="bi bi-calendar3"></i> Semestres</a>
                        </nav>
                    @endif
                    <div class="sidebar-label mt-4">ENSEIGNEMENT</div>
                    <nav class="sidebar-nav">
                        @if(auth()->user()->hasRole(['admin','super_admin','director_general','academic_direction','studies_manager','department_manager','registrar']))
                        <a class="{{ request()->routeIs('admin.students.*') ? 'active' : '' }}" href="{{ route('admin.students.index') }}"><i class="bi bi-people"></i> Étudiants</a>
                        @endif
                        @if(auth()->user()->hasRole(['admin','super_admin','director_general','academic_direction','studies_manager','hr_admin']))
                        <a class="{{ request()->routeIs('admin.teachers.*') ? 'active' : '' }}" href="{{ route('admin.teachers.index') }}"><i class="bi bi-person-workspace"></i> Enseignants</a>
                        @endif
                        @if(auth()->user()->hasRole('hr_admin'))
                        <a class="{{ request()->routeIs('admin.courses.*') ? 'active' : '' }}" href="{{ route('admin.courses.index') }}"><i class="bi bi-journal-bookmark"></i> Cours</a>
                        <a class="{{ request()->routeIs('admin.timetable') ? 'active' : '' }}" href="{{ route('admin.timetable') }}"><i class="bi bi-calendar3"></i> Emplois du temps</a>
                        <a class="{{ request()->routeIs('admin.seances.*') ? 'active' : '' }}" href="{{ route('admin.seances.index') }}"><i class="bi bi-clock-history"></i> Séances</a>
                        @endif
                        @if(auth()->user()->hasRole('it_admin'))
                        <a class="{{ request()->routeIs('admin.courses.*') ? 'active' : '' }}" href="{{ route('admin.courses.index') }}"><i class="bi bi-journal-bookmark"></i> Cours</a>
                        <a class="{{ request()->routeIs('admin.rooms.*') ? 'active' : '' }}" href="{{ route('admin.rooms.index') }}"><i class="bi bi-building"></i> Salles</a>
                        <a class="{{ request()->routeIs('admin.timetable') ? 'active' : '' }}" href="{{ route('admin.timetable') }}"><i class="bi bi-calendar3"></i> Emplois du temps</a>
                        <a class="{{ request()->routeIs('admin.seances.*') ? 'active' : '' }}" href="{{ route('admin.seances.index') }}"><i class="bi bi-clock-history"></i> Séances</a>
                        @endif
                        @if(auth()->user()->hasRole(['admin','super_admin','director_general','academic_direction','studies_manager','department_manager','registrar']))
                        <a class="{{ request()->routeIs('admin.courses.*') ? 'active' : '' }}" href="{{ route('admin.courses.index') }}"><i class="bi bi-journal-bookmark"></i> Cours</a>
                        <a class="{{ request()->routeIs('admin.rooms.*') ? 'active' : '' }}" href="{{ route('admin.rooms.index') }}"><i class="bi bi-building"></i> Salles</a>
                        <a class="{{ request()->routeIs('admin.timetable') ? 'active' : '' }}" href="{{ route('admin.timetable') }}"><i class="bi bi-calendar3"></i> Emplois du temps</a>
                        <a class="{{ request()->routeIs('admin.seances.*') ? 'active' : '' }}" href="{{ route('admin.seances.index') }}"><i class="bi bi-clock-history"></i> Séances</a>
                        <a class="{{ request()->routeIs('admin.exams.*') ? 'active' : '' }}" href="{{ route('admin.exams.index') }}"><i class="bi bi-journal-check"></i> Examens</a>
                        <a class="{{ request()->routeIs('admin.events.*') ? 'active' : '' }}" href="{{ route('admin.events.index') }}"><i class="bi bi-calendar-event"></i> Calendrier</a>
                        @unless(auth()->user()->hasRole(['department_manager', 'registrar']))
                            <a class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.activity') }}"><i class="bi bi-clock-history"></i> Historique</a>
                        @endunless
                        @endif
                    </nav>
                    <div class="sidebar-label mt-4">COMPTE</div>
                    <nav class="sidebar-nav">
                        @if(auth()->user()->hasRole('super_admin'))
                            <a class="{{ request()->routeIs('admin.invitations.*') ? 'active' : '' }}" href="{{ route('admin.invitations.index') }}"><i class="bi bi-envelope-plus"></i> Invitations du personnel</a>
                        @endif
                        <a class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}"><i class="bi bi-bell"></i> Notifications <span class="ms-auto badge text-bg-primary">{{ auth()->user()->unreadNotifications()->count() }}</span></a>
                        <a href="{{ route('profile.edit') }}"><i class="bi bi-person-circle"></i> Mon profil</a>
                    </nav>
                @else
                    <div class="sidebar-label">MON ESPACE</div>
                    <nav class="sidebar-nav">
                        <a class="{{ request()->routeIs('student.dashboard', 'teacher.dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-grid-1x2"></i> Tableau de bord</a>
                        @if(auth()->user()->hasRole('student'))
                            <a class="{{ request()->routeIs('student.enrollments.*') ? 'active' : '' }}" href="{{ route('student.enrollments.index') }}"><i class="bi bi-journal-plus"></i> Inscriptions aux cours</a>
                            <a class="{{ request()->routeIs('student.grades.*') ? 'active' : '' }}" href="{{ route('student.grades.index') }}"><i class="bi bi-award"></i> Mes notes</a>
                            <a class="{{ request()->routeIs('student.attendance.*') ? 'active' : '' }}" href="{{ route('student.attendance.index') }}"><i class="bi bi-calendar2-check"></i> Mes présences</a>
                        @elseif(auth()->user()->hasRole('teacher'))
                            <a class="{{ request()->routeIs('teacher.grades.*') ? 'active' : '' }}" href="{{ route('teacher.grades.index') }}"><i class="bi bi-journal-check"></i> Gestion des notes</a>
                            <a class="{{ request()->routeIs('teacher.attendance.*') ? 'active' : '' }}" href="{{ route('teacher.attendance.index') }}"><i class="bi bi-calendar2-check"></i> Faire l’appel</a>
                        @endif
                        <a href="{{ route('notifications.index') }}"><i class="bi bi-bell"></i> Notifications <span class="ms-auto badge text-bg-primary">{{ auth()->user()->unreadNotifications()->count() }}</span></a>
                        <a href="{{ route('profile.edit') }}"><i class="bi bi-person-circle"></i> Mon profil</a>
                    </nav>
                @endif
                <div class="sidebar-footer">
                    <div class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>
                    <div class="user-summary"><strong>{{ auth()->user()->prenom }} {{ auth()->user()->name }}</strong><small>{{ ['admin' => 'Administrateur', 'super_admin' => 'Super administrateur', 'director_general' => 'Directeur général', 'academic_direction' => 'Direction académique', 'studies_manager' => 'Responsable des études', 'department_manager' => 'Responsable de département', 'registrar' => 'Administrateur de la scolarité', 'hr_admin' => 'Administrateur RH', 'it_admin' => 'Administrateur informatique', 'teacher' => 'Enseignant', 'student' => 'Étudiant'][auth()->user()->role] ?? str_replace('_', ' ', auth()->user()->role) }}</small></div>
                </div>
            </aside>
            <div class="app-main">
                <header class="topbar">
                    <button class="btn btn-light sidebar-toggle d-lg-none" type="button" aria-label="Ouvrir le menu" onclick="document.getElementById('appSidebar').classList.toggle('open')"><i class="bi bi-list"></i></button>
                    <div class="topbar-title">@yield('page-title', 'Espace académique')</div>
                    <div class="d-flex align-items-center gap-3">
                        <a class="btn btn-light btn-sm" href="{{ route('dashboard') }}"><i class="bi bi-house-door me-1"></i><span class="d-none d-sm-inline">Tableau de bord</span></a>
                        <span class="topbar-date d-none d-md-inline">{{ now()->locale('fr')->translatedFormat('l d F Y') }}</span>
                        <a class="btn btn-light btn-sm position-relative" href="{{ route('notifications.index') }}" aria-label="Notifications"><i class="bi bi-bell"></i>@if(auth()->user()->unreadNotifications()->count())<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-primary">{{ auth()->user()->unreadNotifications()->count() }}</span>@endif</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline-secondary btn-sm" type="submit"><i class="bi bi-box-arrow-right me-1"></i>Déconnexion</button></form>
                    </div>
                </header>
                <main class="page-content">
                    @if(session('success'))<div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button></div>@endif
                    @if($errors->any())<div class="alert alert-danger" role="alert"><strong>Vérifiez les champs suivants :</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                    @yield('content')
                    @isset($slot){{ $slot }}@endisset
                </main>
            </div>
        </div>
    @else
        <main class="guest-shell">@yield('content')@isset($slot){{ $slot }}@endisset</main>
    @endauth
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
