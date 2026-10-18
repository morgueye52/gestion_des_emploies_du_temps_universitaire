@extends('layouts.app')
@section('title', 'Mes notes')
@section('page-title', 'Mes notes')
@section('content')
<div class="page-heading">
    <div>
        <h1>Mes notes</h1>
        <p>Consultez les évaluations publiées pour les cours auxquels vous êtes inscrit.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('student.dashboard') }}"><i class="bi bi-arrow-left me-2"></i>Mon espace</a>
</div>
@if(!$student)
    <div class="alert alert-info">Votre compte n’est pas encore associé à un dossier étudiant. Contactez le service de scolarité.</div>
@else
    <div class="card">
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
                    <tr><td colspan="8" class="text-center py-4 text-secondary">Aucune note n’est disponible pour le moment.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
