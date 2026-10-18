@extends('layouts.app')

@section('title', 'Tableau de bord')
@section('page-title', 'Vue d’ensemble')

@section('content')
    @if($isHrAdmin || $isItAdmin)
        <div class="page-heading">
            <div>
                <h1>Bonjour, {{ auth()->user()->prenom ?: auth()->user()->name }} 👋</h1>
                <p>{{ $isHrAdmin ? 'Suivi des enseignants, des cours et des emplois du temps.' : 'Gestion des salles et consultation des cours et emplois du temps.' }}</p>
            </div>
            <a href="{{ route($isHrAdmin ? 'admin.teachers.index' : 'admin.rooms.index') }}" class="btn btn-primary">
                <i class="bi {{ $isHrAdmin ? 'bi-person-workspace' : 'bi-building' }} me-2"></i>{{ $isHrAdmin ? 'Gérer les enseignants' : 'Gérer les salles' }}
            </a>
        </div>

        @php
            $roleStats = [];
            if ($isHrAdmin) {
                $roleStats[] = ['Enseignants', $stats['teachers'], 'bi-person-workspace', 'admin.teachers.index'];
            }
            if ($isItAdmin) {
                $roleStats[] = ['Salles', $stats['rooms'], 'bi-building', 'admin.rooms.index'];
            }
            $roleStats[] = ['Cours', $stats['courses'], 'bi-journal-bookmark', 'admin.courses.index'];
            $roleStats[] = ['Séances planifiées', $stats['sessions'], 'bi-calendar-week', 'admin.timetable'];
        @endphp
        <div class="row g-3 mb-4">
            @foreach($roleStats as [$label, $value, $icon, $route])
                <div class="col-6 col-xl">
                    <a class="text-decoration-none text-reset" href="{{ route($route) }}">
                        <div class="card stat-card">
                            <div class="stat-top"><span>{{ $label }}</span><span class="stat-icon"><i class="bi {{ $icon }}"></i></span></div>
                            <div class="stat-value">{{ number_format($value, 0, ',', ' ') }}</div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        <section class="card">
            <div class="card-header d-flex align-items-center justify-content-between py-3 px-4">
                <span>Planning du jour</span>
                <a href="{{ route('admin.timetable') }}" class="small text-decoration-none">Ouvrir le calendrier <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Horaire</th><th>Cours</th><th>Enseignant</th><th>Salle</th><th>Groupe</th></tr></thead>
                    <tbody>
                    @forelse($sessionsToday as $session)
                        <tr>
                            <td class="fw-semibold">{{ substr($session->starts_at, 0, 5) }}–{{ substr($session->ends_at, 0, 5) }}</td>
                            <td>{{ $session->course?->name ?? 'Cours supprimé' }}</td>
                            <td>{{ $session->teacher ? $session->teacher->first_name.' '.$session->teacher->last_name : '—' }}</td>
                            <td>{{ $session->room?->code }}</td>
                            <td>{{ $session->academicClass?->name ?? $session->group?->academicClass?->name ?? '—' }}{{ $session->group?->name ? ' · '.$session->group->name : '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty-state"><i class="bi bi-calendar2-check"></i>Aucune séance programmée aujourd’hui.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @else
    <div class="page-heading">
        <div>
            <h1>Bonjour, {{ auth()->user()->prenom ?: auth()->user()->name }} 👋</h1>
            <p>{{ auth()->user()->hasRole('director_general') ? 'Voici la vue de pilotage académique de votre établissement.' : 'Voici la situation académique de votre établissement aujourd’hui.' }}</p>
        </div>
        <a href="{{ route('admin.timetable') }}" class="btn btn-primary"><i class="bi bi-calendar-plus me-2"></i>Voir le planning</a>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['Étudiants', $stats['students'], 'bi-people', 'admin.students.index'],
            ['Enseignants', $stats['teachers'], 'bi-person-workspace', 'admin.teachers.index'],
            ['Cours', $stats['courses'], 'bi-journal-bookmark', 'admin.courses.index'],
            ['Départements', $stats['departments'], 'bi-diagram-3', null],
            ['Classes', $stats['classes'], 'bi-people-fill', null],
            ['Salles', $stats['rooms'], 'bi-building', 'admin.rooms.index'],
            ['Séances planifiées', $stats['sessions'], 'bi-calendar-week', 'admin.seances.index'],
            ['Examens à venir', $stats['exams'], 'bi-journal-check', 'admin.exams.index'],
        ] as [$label, $value, $icon, $route])
            <div class="col-6 col-xl">
                <a class="text-decoration-none text-reset" @if($route) href="{{ route($route) }}" @endif>
                    <div class="card stat-card">
                        <div class="stat-top"><span>{{ $label }}</span><span class="stat-icon"><i class="bi {{ $icon }}"></i></span></div>
                        <div class="stat-value">{{ number_format($value, 0, ',', ' ') }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <section class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between py-3 px-4">
                    <span>Planning du jour</span>
                    <a href="{{ route('admin.timetable') }}" class="small text-decoration-none">Ouvrir le calendrier <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Horaire</th><th>Cours</th><th>Enseignant</th><th>Salle</th><th>Groupe</th></tr></thead>
                        <tbody>
                        @forelse($sessionsToday as $session)
                            <tr>
                                <td class="fw-semibold">{{ substr($session->starts_at, 0, 5) }}–{{ substr($session->ends_at, 0, 5) }}</td>
                                <td>{{ $session->course?->name ?? 'Cours supprimé' }}</td>
                                <td>{{ $session->teacher ? $session->teacher->first_name.' '.$session->teacher->last_name : '—' }}</td>
                                <td>{{ $session->room?->code }}</td>
                                <td>{{ $session->academicClass?->name ?? $session->group?->academicClass?->name ?? '—' }}{{ $session->group?->name ? ' · '.$session->group->name : '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-state"><i class="bi bi-calendar2-check"></i>Aucune séance programmée aujourd’hui.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        <div class="col-xl-4">
            <section class="card h-100">
                <div class="card-header py-3 px-4">Ressources aujourd’hui</div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small">Salles utilisées</span>
                        <strong>{{ $activeRooms }} / {{ $stats['rooms'] }}</strong>
                    </div>
                    <div class="progress" role="progressbar" aria-label="Taux d’occupation des salles">
                        <div class="progress-bar" style="width: {{ $stats['rooms'] ? min(100, round($activeRooms / $stats['rooms'] * 100)) : 0 }}%"></div>
                    </div>
                    <hr class="my-4">
                    <a class="btn btn-outline-primary w-100 mb-2" href="{{ route('admin.students.index') }}"><i class="bi bi-people me-2"></i>{{ auth()->user()->hasRole(['director_general', 'registrar']) ? 'Consulter les étudiants' : 'Gérer les étudiants' }}</a>
                    @unless(auth()->user()->hasRole(['director_general', 'registrar']))
                        <a class="btn btn-outline-primary w-100" href="{{ route('admin.seances.create') }}"><i class="bi bi-plus-circle me-2"></i>Planifier une séance</a>
                    @endunless
                </div>
            </section>
        </div>
    </div>
    <div class="row g-3 mt-1">
        <div class="col-xl-6">
            <section class="card h-100">
                <div class="card-header py-3 px-4">Étudiants par formation</div>
                <div class="card-body p-4">
                    @forelse($studentsByFormation as $formation=>$total)
                        <div class="chart-row"><div class="d-flex justify-content-between gap-2 mb-1"><span>{{ $formation }}</span><strong>{{ $total }}</strong></div><div class="chart-track"><span style="width: {{ $studentsByFormation->max() ? max(4,round($total / $studentsByFormation->max() * 100)) : 0 }}%"></span></div></div>
                    @empty<div class="empty-state">Les effectifs apparaîtront après l’inscription des étudiants dans les classes.</div>@endforelse
                </div>
            </section>
        </div>
        <div class="col-xl-6">
            <section class="card h-100">
                <div class="card-header py-3 px-4">Séances par jour de la semaine</div>
                <div class="card-body p-4">
                    @foreach([1=>'Lundi',2=>'Mardi',3=>'Mercredi',4=>'Jeudi',5=>'Vendredi',6=>'Samedi'] as $day=>$label)
                        @php($count=(int)($sessionsByWeekday[$day] ?? 0))
                        <div class="chart-row"><div class="d-flex justify-content-between gap-2 mb-1"><span>{{ $label }}</span><strong>{{ $count }}</strong></div><div class="chart-track"><span style="width: {{ $stats['sessions'] ? max(2,round($count / $stats['sessions'] * 100)) : 0 }}%"></span></div></div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
    <section class="card mt-3">
        <div class="card-header d-flex justify-content-between py-3 px-4"><span>Examens à venir</span><a href="{{ route('admin.exams.index') }}" class="small text-decoration-none">Tous les examens <i class="bi bi-arrow-right"></i></a></div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Évaluation</th><th>Cours</th><th>Date</th><th>Salle</th><th>Groupe</th></tr></thead><tbody>
        @forelse($upcomingExams as $exam)<tr><td>{{ $exam->name }}</td><td>{{ $exam->course?->name }}</td><td>{{ $exam->starts_at->locale('fr')->translatedFormat('d F · H:i') }}</td><td>{{ $exam->room?->code }}</td><td>{{ $exam->group?->name ?? '—' }}</td></tr>@empty<tr><td colspan="5"><div class="empty-state">Aucun examen à venir.</div></td></tr>@endforelse
        </tbody></table></div>
    </section>
    @endif
@endsection
