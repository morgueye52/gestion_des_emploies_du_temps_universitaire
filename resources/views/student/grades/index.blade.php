@extends('layouts.app')
@section('title', 'Mes notes')
@section('page-title', 'Mes notes')
@section('content')
<div class="page-heading">
    <div>
        <h1>Mes notes</h1>
        <p>Consultez vos évaluations, vos moyennes par cours et votre relevé par semestre.</p>
    </div>
    <div class="d-flex gap-2 no-print">
        <button class="btn btn-outline-secondary" type="button" onclick="window.print()"><i class="bi bi-printer me-2"></i>Imprimer / PDF</button>
        <a class="btn btn-outline-secondary" href="{{ route('student.dashboard') }}"><i class="bi bi-arrow-left me-2"></i>Mon espace</a>
    </div>
</div>
@if(!$student)
    <div class="alert alert-info">Votre compte n’est pas encore associé à un dossier étudiant. Contactez le service de scolarité.</div>
@else
    @forelse($semesterReports as $report)
        <section class="card mb-4">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-3 px-4">
                <div>
                    <strong>{{ $report['semester']?->name ?? 'Semestre non défini' }}</strong>
                    @if($report['semester']?->academicYear)
                        <span class="text-secondary"> · {{ $report['semester']->academicYear->name }}</span>
                    @endif
                </div>
                <div class="text-end">
                    <span class="small text-secondary">Moyenne semestrielle pondérée par crédits</span>
                    <div class="fs-5 fw-bold">{{ $report['average'] === null ? 'En attente des notes' : number_format($report['average'], 2, ',', ' ').' / 20' }}</div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Code</th><th>Cours</th><th>Crédits</th><th>Notes reçues</th><th>Moyenne du cours</th><th>Résultat</th></tr></thead>
                    <tbody>
                    @foreach($report['courses'] as $courseReport)
                        <tr>
                            <td>{{ $courseReport['course']->code }}</td>
                            <td>{{ $courseReport['course']->name }}</td>
                            <td>{{ $courseReport['course']->credits }}</td>
                            <td>{{ $courseReport['grades']->count() }}</td>
                            <td>{{ $courseReport['average'] === null ? '—' : number_format($courseReport['average'], 2, ',', ' ').' / 20' }}</td>
                            <td>
                                @if($courseReport['average'] === null)
                                    <span class="badge text-bg-secondary">En attente</span>
                                @elseif($courseReport['average'] >= 10)
                                    <span class="badge text-bg-success">Validé</span>
                                @else
                                    <span class="badge text-bg-danger">À améliorer</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr><td colspan="6" class="small text-secondary px-4">Moyenne d’un cours : moyenne simple des évaluations disponibles. Moyenne semestrielle : moyenne pondérée par crédits des cours notés. Les cours sans note ne sont pas inclus.</td></tr>
                    </tfoot>
                </table>
            </div>
        </section>
    @empty
        <div class="alert alert-info">Aucune inscription à un cours n’est encore associée à votre dossier étudiant.</div>
    @endforelse

    <div class="card">
        <div class="card-header py-3 px-4">Détail des évaluations</div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Cours</th><th>Semestre</th><th>Évaluation</th><th>Type</th><th>Note</th><th>Date</th><th>Enseignant</th><th>Commentaire</th></tr></thead>
                <tbody>
                @forelse($grades as $grade)
                    <tr>
                        <td>{{ $grade->course->code }}<br>{{ $grade->course->name }}</td>
                        <td>{{ $grade->course->semester?->name ?? '—' }}</td>
                        <td>{{ $grade->assessment_name }}</td>
                        <td>{{ ['controle'=>'Contrôle continu','examen'=>'Examen','devoir'=>'Devoir','tp'=>'Travaux pratiques','autre'=>'Autre'][$grade->assessment_type] ?? $grade->assessment_type }}</td>
                        <td><strong>{{ rtrim(rtrim(number_format((float)$grade->score, 2, ',', ''), '0'), ',') }}/20</strong></td>
                        <td>{{ $grade->assessed_on->locale('fr')->translatedFormat('d F Y') }}</td>
                        <td>{{ $grade->teacher->first_name }} {{ $grade->teacher->last_name }}</td>
                        <td>{{ $grade->comment ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-4 text-secondary">Aucune note n’a encore été publiée pour le moment.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
