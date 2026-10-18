@extends('layouts.app')
@section('title','Emploi du temps')
@section('page-title','Emplois du temps')
@section('content')
<div class="page-heading"><div><h1>Emploi du temps</h1><p>Filtrez les séances par classe, semestre, enseignant ou salle.</p></div><div class="d-flex flex-wrap gap-2 no-print"><button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-2"></i>Imprimer / PDF</button>@unless(auth()->user()->hasRole(['director_general', 'registrar', 'hr_admin', 'it_admin']))<a href="{{ route('admin.seances.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Planifier</a>@unless(auth()->user()->hasRole('department_manager'))<form method="POST" action="{{ route('admin.timetable.generate') }}">@csrf @if($selectedSemester)<input type="hidden" name="semester_id" value="{{ $selectedSemester->id }}">@endif<button class="btn btn-warning"><i class="bi bi-magic me-2"></i>Générer automatiquement</button></form>@endunless @endunless</div></div>
<div class="card mb-3 no-print"><div class="card-body"><form class="row g-2 align-items-end" method="GET">
<div class="col-md-4"><label class="form-label" for="academic_class_id">Classe</label><select class="form-select" id="academic_class_id" name="academic_class_id"><option value="">Toutes les classes</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected(request('academic_class_id')==$class->id)>{{ $class->name }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label" for="semester_id">Semestre</label><select required class="form-select" id="semester_id" name="semester_id">@forelse($semesters as $semester)<option value="{{ $semester->id }}" @selected($selectedSemester?->id === $semester->id)>{{ $semester->academicYear?->name }} · {{ $semester->name }}</option>@empty<option value="" selected disabled>Aucun semestre disponible</option>@endforelse</select></div>
<div class="col-md-4"><label class="form-label" for="teacher_id">Enseignant</label><select class="form-select" id="teacher_id" name="teacher_id"><option value="">Tous les enseignants</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected(request('teacher_id')==$teacher->id)>{{ $teacher->first_name }} {{ $teacher->last_name }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label" for="group_id">Groupe</label><select class="form-select" id="group_id" name="group_id"><option value="">Tous les groupes</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected(request('group_id')==$group->id)>{{ $group->academicClass?->name }} · {{ $group->name }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label" for="room_id">Salle</label><select class="form-select" id="room_id" name="room_id"><option value="">Toutes les salles</option>@foreach($rooms as $room)<option value="{{ $room->id }}" @selected(request('room_id')==$room->id)>{{ $room->code }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label" for="weekday">Jour</label><select class="form-select" id="weekday" name="weekday"><option value="">Toute la semaine</option>@foreach([1=>'Lundi',2=>'Mardi',3=>'Mercredi',4=>'Jeudi',5=>'Vendredi',6=>'Samedi'] as $day=>$label)<option value="{{ $day }}" @selected((string)request('weekday') === (string)$day)>{{ $label }}</option>@endforeach</select></div>
<div class="col-12 d-flex justify-content-end gap-2"><a class="btn btn-light" href="{{ route('admin.timetable') }}">Réinitialiser</a><button class="btn btn-outline-primary">Appliquer les filtres</button></div></form></div></div>
@if($selectedTeacher)
    <section class="card mb-3">
        <div class="card-header py-3 px-4">Disponibilités de {{ $selectedTeacher->first_name }} {{ $selectedTeacher->last_name }}</div>
        <div class="card-body">
            @if($selectedTeacher->availabilities->isEmpty())
                <p class="mb-0 text-secondary">Aucun créneau de disponibilité n’est enregistré. Le contrôle hebdomadaire ne bloque donc aucun créneau pour cet enseignant.</p>
            @else
                <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Jour</th><th>Disponible de</th><th>à</th></tr></thead><tbody>
                    @foreach($selectedTeacher->availabilities->sortBy(['weekday', 'starts_at']) as $availability)
                        <tr><td>{{ [1=>'Lundi',2=>'Mardi',3=>'Mercredi',4=>'Jeudi',5=>'Vendredi',6=>'Samedi'][$availability->weekday] ?? '—' }}</td><td>{{ substr($availability->starts_at, 0, 5) }}</td><td>{{ substr($availability->ends_at, 0, 5) }}</td></tr>
                    @endforeach
                </tbody></table></div>
            @endif
        </div>
    </section>
@endif
@if($selectedClass || $selectedSemester || $selectedRoom)
    <div class="alert alert-light border mb-3">
        Aperçu : {{ $selectedClass?->name ?? 'toutes les classes' }} · {{ $selectedSemester ? $selectedSemester->academicYear?->name.' · '.$selectedSemester->name : 'tous les semestres' }} · {{ $selectedRoom?->code ?? 'toutes les salles' }}
    </div>
@endif
<div class="card"><div class="card-header d-flex justify-content-between py-3 px-4"><span>Planning de la semaine{{ $selectedSemester ? ' · '.$selectedSemester->academicYear?->name.' · '.$selectedSemester->name : '' }}</span><span>{{ $sessions->count() }} séance(s)</span></div>
<div class="weekly-calendar">
    @foreach($weekdays as $day)
        <section class="weekly-day">
            <header>{{ [1=>'Lundi',2=>'Mardi',3=>'Mercredi',4=>'Jeudi',5=>'Vendredi',6=>'Samedi'][$day] }}</header>
            @forelse($sessions->where('weekday',$day) as $session)
                <article class="session-card">
                    <small class="session-time">{{ substr($session->starts_at,0,5) }}–{{ substr($session->ends_at,0,5) }}</small>
                    <strong>{{ $session->course?->name }}</strong>
                    <small>{{ $session->teacher ? $session->teacher->first_name.' '.$session->teacher->last_name : 'Enseignant à affecter' }}</small>
                    <small>{{ $session->room?->code }} · {{ $session->academicClass?->name ?? $session->group?->academicClass?->name }} {{ $session->group?->name ? '· '.$session->group->name : '· Toute la classe' }}</small>
                    @if(auth()->user()->hasRole(['director_general', 'registrar', 'hr_admin', 'it_admin']))
                        <a class="no-print" href="{{ route('admin.seances.show',$session) }}">Consulter</a>
                    @else
                        <a class="no-print" href="{{ route('admin.seances.edit',$session) }}">Modifier</a>
                    @endif
                </article>
            @empty
                <div class="weekly-empty">Pas de cours</div>
            @endforelse
            @foreach($exams->filter(fn ($exam) => $exam->starts_at->dayOfWeekIso === $day) as $exam)
                <article class="session-card exam-card">
                    <small class="session-time">{{ $exam->starts_at->locale('fr')->translatedFormat('d/m') }} · {{ $exam->starts_at->format('H:i') }}–{{ $exam->ends_at->format('H:i') }}</small>
                    <strong>Examen · {{ $exam->name }}</strong>
                    <small>{{ $exam->teacher ? $exam->teacher->first_name.' '.$exam->teacher->last_name : 'Surveillant non affecté' }}</small>
                    <small>{{ $exam->room?->code }} · {{ $exam->group?->academicClass?->name }} {{ $exam->group?->name ? '· '.$exam->group->name : '' }}</small>
                </article>
            @endforeach
        </section>
    @endforeach
</div>
</div>
@endsection
