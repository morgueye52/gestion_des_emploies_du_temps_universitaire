@extends('layouts.app')
@section('title', 'Mes présences')
@section('page-title', 'Mes présences')
@section('content')
<div class="page-heading">
    <div>
        <h1>Mes présences</h1>
        <p>Consultez votre historique d’assiduité aux séances.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('student.dashboard') }}"><i class="bi bi-arrow-left me-2"></i>Mon espace</a>
</div>
@if(!$student)
    <div class="alert alert-info">Votre compte n’est pas encore associé à un dossier étudiant. Contactez le service de scolarité.</div>
@else
    <div class="card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Date</th><th>Cours</th><th>Horaire</th><th>Enseignant</th><th>Statut</th><th>Commentaire</th></tr></thead>
                <tbody>
                @forelse($records as $record)
                    <tr>
                        <td>{{ $record->attendance_date->locale('fr')->translatedFormat('d F Y') }}</td>
                        <td>{{ $record->session->course->code }}<br>{{ $record->session->course->name }}</td>
                        <td>{{ substr($record->session->starts_at, 0, 5) }}–{{ substr($record->session->ends_at, 0, 5) }}</td>
                        <td>{{ $record->teacher->first_name }} {{ $record->teacher->last_name }}</td>
                        <td><span class="badge text-bg-{{ $record->status === 'present' ? 'success' : ($record->status === 'late' ? 'warning' : ($record->status === 'absent' ? 'danger' : 'secondary')) }}">{{ ['present'=>'Présent','absent'=>'Absent','late'=>'En retard','excused'=>'Absence justifiée'][$record->status] ?? $record->status }}</span></td>
                        <td>{{ $record->comment ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4 text-secondary">Aucune présence n’a encore été enregistrée par vos enseignants.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
