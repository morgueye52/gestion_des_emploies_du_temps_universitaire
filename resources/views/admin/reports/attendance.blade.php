@extends('layouts.app')
@section('title', 'Rapport des présences')
@section('page-title', 'Rapport des présences')
@section('content')
<div class="page-heading">
    <div>
        <h1>Rapport des présences</h1>
        <p>Analysez l’assiduité par étudiant, classe, cours et période.</p>
    </div>
    <button class="btn btn-outline-secondary no-print" type="button" onclick="window.print()">
        <i class="bi bi-printer me-2"></i>Imprimer / PDF
    </button>
</div>

<div class="card mb-4 no-print">
    <div class="card-body">
        <form class="row g-3 align-items-end" method="GET" action="{{ route('admin.reports.attendance') }}">
            <div class="col-md-3">
                <label class="form-label" for="from_date">Du</label>
                <input class="form-control" type="date" id="from_date" name="from_date" value="{{ $filters['from_date'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="to_date">Au</label>
                <input class="form-control" type="date" id="to_date" name="to_date" value="{{ $filters['to_date'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="academic_class_id">Classe</label>
                <select class="form-select" id="academic_class_id" name="academic_class_id">
                    <option value="">Toutes les classes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected(($filters['academic_class_id'] ?? '') == $class->id)>
                            {{ $class->name }} · {{ $class->formation?->name ?? 'Formation non définie' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="course_id">Cours</label>
                <select class="form-select" id="course_id" name="course_id">
                    <option value="">Tous les cours</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" @selected(($filters['course_id'] ?? '') == $course->id)>
                            {{ $course->code }} · {{ $course->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('admin.reports.attendance') }}">Effacer les filtres</a>
                <button class="btn btn-primary" type="submit">Afficher le rapport</button>
            </div>
        </form>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-secondary mb-0">
        Période :
        {{ $filters['from_date'] ?? 'Depuis le début des enregistrements' }}
        —
        {{ $filters['to_date'] ?? 'Jusqu’à aujourd’hui' }}
    </p>
    <p class="small text-secondary mb-0">Les absences justifiées sont présentées séparément des absences non justifiées.</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl">
        <div class="card h-100"><div class="card-body"><div class="small text-secondary">Appels enregistrés</div><div class="fs-3 fw-bold">{{ $summary['total'] }}</div></div></div>
    </div>
    <div class="col-6 col-xl">
        <div class="card h-100"><div class="card-body"><div class="small text-secondary">Présences</div><div class="fs-3 fw-bold text-success">{{ $summary['present'] }}</div></div></div>
    </div>
    <div class="col-6 col-xl">
        <div class="card h-100"><div class="card-body"><div class="small text-secondary">Absences non justifiées</div><div class="fs-3 fw-bold text-danger">{{ $summary['absent'] }}</div></div></div>
    </div>
    <div class="col-6 col-xl">
        <div class="card h-100"><div class="card-body"><div class="small text-secondary">Absences justifiées</div><div class="fs-3 fw-bold">{{ $summary['excused'] }}</div></div></div>
    </div>
    <div class="col-6 col-xl">
        <div class="card h-100"><div class="card-body"><div class="small text-secondary">En retard</div><div class="fs-3 fw-bold text-warning">{{ $summary['late'] }}</div></div></div>
    </div>
    <div class="col-6 col-xl">
        <div class="card h-100"><div class="card-body"><div class="small text-secondary">Taux d’absence non justifiée</div><div class="fs-3 fw-bold">{{ number_format($summary['absence_rate'], 1, ',', ' ') }} %</div><div class="small text-secondary">Absences non justifiées / appels</div></div></div>
    </div>
</div>

<div class="card">
    <div class="card-header py-3 px-4">Détail par étudiant</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr><th>Étudiant</th><th>Classe</th><th>Appels</th><th>Présences</th><th>Absences non justifiées</th><th>Justifiées</th><th>Retards</th><th>Taux d’absence</th></tr>
            </thead>
            <tbody>
            @forelse($studentReports as $report)
                <tr>
                    <td>{{ $report->student->first_name }} {{ $report->student->last_name }}<div class="small text-secondary">{{ $report->student->matricule }}</div></td>
                    <td>{{ $report->student->academicClass?->name ?? 'Non affecté' }}</td>
                    <td>{{ $report->total }}</td>
                    <td>{{ $report->present }}</td>
                    <td>{{ $report->absent }}</td>
                    <td>{{ $report->excused }}</td>
                    <td>{{ $report->late }}</td>
                    <td>{{ $report->total > 0 ? number_format($report->absent / $report->total * 100, 1, ',', ' ') : '0,0' }} %</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center py-4 text-secondary">Aucune présence ne correspond aux filtres sélectionnés.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body no-print">{{ $studentReports->links() }}</div>
</div>
@endsection
