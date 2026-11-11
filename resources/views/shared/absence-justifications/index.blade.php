@extends('layouts.app')
@section('title', 'Justificatifs d’absence')
@section('page-title', 'Justificatifs d’absence')
@section('content')
<div class="page-heading">
    <div>
        <h1>Justificatifs d’absence</h1>
        <p>Consultez les demandes des étudiants et traitez celles qui sont en attente.</p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form class="row g-3 align-items-end" method="GET">
            <div class="col-md-4">
                <label class="form-label" for="status">État des demandes</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Toutes les demandes</option>
                    <option value="pending" @selected(request('status') === 'pending')>En attente</option>
                    <option value="approved" @selected(request('status') === 'approved')>Acceptées</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Refusées</option>
                </select>
            </div>
            <div class="col-md-3"><button class="btn btn-outline-primary" type="submit">Filtrer</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr><th>Étudiant</th><th>Séance concernée</th><th>Motif et pièce</th><th>État</th><th>Décision</th></tr>
            </thead>
            <tbody>
            @forelse($justifications as $justification)
                @php($record = $justification->attendanceRecord)
                <tr>
                    <td>
                        <strong>{{ $justification->student->first_name }} {{ $justification->student->last_name }}</strong>
                        <div class="small text-secondary">{{ $justification->student->matricule }}</div>
                        <div class="small text-secondary">{{ $record->attendance_date->locale('fr')->translatedFormat('d F Y') }}</div>
                    </td>
                    <td>
                        {{ $record->session->course->code }} · {{ $record->session->course->name }}
                        <div class="small text-secondary">{{ substr($record->session->starts_at, 0, 5) }}–{{ substr($record->session->ends_at, 0, 5) }}</div>
                    </td>
                    <td>
                        <div class="text-break">{{ $justification->reason }}</div>
                        @if($justification->attachment_path)
                            <a class="btn btn-sm btn-outline-secondary mt-2" href="{{ route('absence-justifications.attachment', $justification) }}">
                                <i class="bi bi-paperclip me-1"></i>Télécharger la pièce
                            </a>
                        @else
                            <span class="small text-secondary">Aucune pièce jointe</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge text-bg-{{ $justification->status === 'pending' ? 'warning' : ($justification->status === 'approved' ? 'success' : 'danger') }}">
                            {{ ['pending' => 'En attente', 'approved' => 'Acceptée', 'rejected' => 'Refusée'][$justification->status] ?? $justification->status }}
                        </span>
                        @if($justification->review_comment)
                            <div class="small text-secondary mt-1">{{ $justification->review_comment }}</div>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        @if($justification->status === 'pending')
                            @php($routePrefix = auth()->user()->hasRole('teacher') ? 'teacher' : 'admin')
                            <form method="POST" action="{{ route($routePrefix.'.absence-justifications.review', $justification) }}">
                                @csrf
                                <label class="visually-hidden" for="review-comment-{{ $justification->id }}">Commentaire de décision</label>
                                <textarea class="form-control form-control-sm mb-2" id="review-comment-{{ $justification->id }}" name="review_comment" rows="2" maxlength="1000" placeholder="Commentaire facultatif">{{ old('review_comment') }}</textarea>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-success" name="decision" value="approved" type="submit">Accepter</button>
                                    <button class="btn btn-sm btn-outline-danger" name="decision" value="rejected" type="submit">Refuser</button>
                                </div>
                            </form>
                        @else
                            <span class="small text-secondary">
                                {{ $justification->reviewer ? $justification->reviewer->prenom.' '.$justification->reviewer->name : 'Décision enregistrée' }}
                                @if($justification->reviewed_at)
                                    <br>{{ $justification->reviewed_at->locale('fr')->translatedFormat('d F Y à H:i') }}
                                @endif
                            </span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-4 text-secondary">Aucune demande de justification ne correspond à ce filtre.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $justifications->links() }}</div>
</div>
@endsection
