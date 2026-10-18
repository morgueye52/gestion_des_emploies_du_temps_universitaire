@extends('layouts.app')
@section('title', 'Gestion des présences')
@section('page-title', 'Gestion des présences')
@section('content')
<div class="page-heading">
    <div>
        <h1>Faire l’appel</h1>
        <p>Enregistrez la présence des étudiants inscrits à vos cours, séance par séance.</p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form class="row g-3 align-items-end" method="GET" action="{{ route('teacher.attendance.index') }}">
            <div class="col-md-5">
                <label class="form-label" for="date">Date de la séance</label>
                <input class="form-control" type="date" name="date" id="date" value="{{ $date }}" max="{{ now()->toDateString() }}" required>
            </div>
            <div class="col-md-5">
                <label class="form-label" for="session_id">Séance à prendre</label>
                <select class="form-select" name="session_id" id="session_id" required>
                    <option value="">Choisir une séance</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" @selected($selectedSession?->id === $session->id)>
                            {{ $session->course->code }} · {{ $session->course->name }} — {{ substr($session->starts_at, 0, 5) }}–{{ substr($session->ends_at, 0, 5) }}{{ $session->group ? ' · '.$session->group->name : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Afficher</button></div>
        </form>
    </div>
</div>

@if($sessions->isEmpty())
    <div class="alert alert-info">Aucune séance ne vous est affectée à cette date. Vérifiez le jour sélectionné ou contactez l’administration.</div>
@elseif(!$selectedSession)
    <div class="alert alert-info">Choisissez la séance concernée pour afficher la liste des étudiants inscrits.</div>
@else
    <div class="card">
        <div class="card-header py-3 px-4">
            <strong>{{ $selectedSession->course->name }}</strong>
            <span class="text-secondary"> · {{ $selectedSession->course->code }} · {{ substr($selectedSession->starts_at, 0, 5) }}–{{ substr($selectedSession->ends_at, 0, 5) }}</span>
        </div>
        <div class="card-body">
            @if($students->isEmpty())
                <p class="text-secondary mb-0">Aucun étudiant actif inscrit à ce cours{{ $selectedSession->group ? ' dans ce groupe' : '' }}. La scolarité doit vérifier les inscriptions et les affectations.</p>
            @else
                <form method="POST" action="{{ route('teacher.attendance.store', $selectedSession) }}">
                    @csrf
                    <input type="hidden" name="attendance_date" value="{{ $date }}">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Matricule</th><th>Étudiant</th><th>Statut *</th><th>Commentaire</th></tr></thead>
                            <tbody>
                            @foreach($students as $student)
                                @php($record = $attendanceByStudent->get($student->id))
                                <tr>
                                    <td>{{ $student->matricule }}</td>
                                    <td>{{ $student->first_name }} {{ $student->last_name }}</td>
                                    <td>
                                        <select class="form-select" name="attendance[{{ $student->id }}][status]" required>
                                            <option value="">Choisir un statut</option>
                                            @foreach(['present'=>'Présent','absent'=>'Absent','late'=>'En retard','excused'=>'Absence justifiée'] as $value=>$label)
                                                <option value="{{ $value }}" @selected(old("attendance.{$student->id}.status", $record?->status) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input class="form-control" name="attendance[{{ $student->id }}][comment]" value="{{ old("attendance.{$student->id}.comment", $record?->comment) }}" maxlength="500" placeholder="Facultatif"></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3"><button class="btn btn-primary" type="submit">Enregistrer les présences</button></div>
                </form>
            @endif
        </div>
    </div>
@endif
@endsection
