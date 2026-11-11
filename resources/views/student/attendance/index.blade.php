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
                <thead><tr><th>Date</th><th>Cours</th><th>Horaire</th><th>Enseignant</th><th>Statut</th><th>Commentaire</th><th>Justification</th></tr></thead>
                <tbody>
                @forelse($records as $record)
                    <tr>
                        <td>{{ $record->attendance_date->locale('fr')->translatedFormat('d F Y') }}</td>
                        <td>{{ $record->session->course->code }}<br>{{ $record->session->course->name }}</td>
                        <td>{{ substr($record->session->starts_at, 0, 5) }}–{{ substr($record->session->ends_at, 0, 5) }}</td>
                        <td>{{ $record->teacher->first_name }} {{ $record->teacher->last_name }}</td>
                        <td><span class="badge text-bg-{{ $record->status === 'present' ? 'success' : ($record->status === 'late' ? 'warning' : ($record->status === 'absent' ? 'danger' : 'secondary')) }}">{{ ['present'=>'Présent','absent'=>'Absent','late'=>'En retard','excused'=>'Absence justifiée'][$record->status] ?? $record->status }}</span></td>
                        <td>{{ $record->comment ?: '—' }}</td>
                        <td class="text-nowrap">
                            @if($record->justification?->status === 'pending')
                                <span class="badge text-bg-warning">En attente</span>
                                @if($record->justification->attachment_path)
                                    <a class="d-block small mt-1" href="{{ route('absence-justifications.attachment', $record->justification) }}">Voir mon justificatif</a>
                                @endif
                            @elseif($record->justification?->status === 'approved')
                                <span class="badge text-bg-success">Acceptée</span>
                                @if($record->justification->attachment_path)
                                    <a class="d-block small mt-1" href="{{ route('absence-justifications.attachment', $record->justification) }}">Voir mon justificatif</a>
                                @endif
                            @elseif($record->justification?->status === 'rejected')
                                <span class="badge text-bg-danger">Refusée</span>
                                @if($record->justification->review_comment)
                                    <div class="small text-secondary mt-1">{{ $record->justification->review_comment }}</div>
                                @endif
                            @endif

                            @if($record->status === 'absent' && $record->justification?->status !== 'pending' && $record->justification?->status !== 'approved')
                                <details class="mt-2">
                                    <summary class="text-primary">Déposer une demande</summary>
                                    <form class="mt-2" method="POST" enctype="multipart/form-data" action="{{ route('student.attendance.justification.store', $record) }}">
                                        @csrf
                                        <label class="form-label small" for="reason-{{ $record->id }}">Motif *</label>
                                        <textarea class="form-control form-control-sm mb-2" id="reason-{{ $record->id }}" name="reason" rows="3" minlength="10" maxlength="2000" placeholder="Expliquez le motif de votre absence." required>{{ old('reason') }}</textarea>
                                        <label class="form-label small" for="attachment-{{ $record->id }}">Justificatif (PDF, JPG ou PNG, 5 Mo maximum)</label>
                                        <input class="form-control form-control-sm mb-2" type="file" id="attachment-{{ $record->id }}" name="attachment" accept=".pdf,.jpg,.jpeg,.png">
                                        <button class="btn btn-sm btn-primary" type="submit">Transmettre</button>
                                    </form>
                                </details>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-4 text-secondary">Aucune présence n’a encore été enregistrée par vos enseignants.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
