@extends('layouts.app')
@section('title', 'Validation des notes')
@section('page-title', 'Validation des notes')
@section('content')
<div class="page-heading">
    <div>
        <h1>Validation des notes</h1>
        <p>Vérifiez les notes saisies par les enseignants avant leur publication aux étudiants.</p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form class="row g-3 align-items-end" method="GET" action="{{ route('admin.grades.validation') }}">
            <div class="col-md-4">
                <label class="form-label" for="status">État</label>
                <select class="form-select" id="status" name="status">
                    <option value="pending" @selected($status === 'pending')>En attente</option>
                    <option value="published" @selected($status === 'published')>Publiées</option>
                    <option value="rejected" @selected($status === 'rejected')>Refusées</option>
                    <option value="all" @selected($status === 'all')>Toutes</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label" for="course_id">Cours</label>
                <select class="form-select" id="course_id" name="course_id">
                    <option value="">Tous les cours accessibles</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" @selected(($filters['course_id'] ?? '') == $course->id)>
                            {{ $course->code }} · {{ $course->name }}
                        </option>
                    @endforeach
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
                <tr><th>Étudiant</th><th>Cours / évaluation</th><th>Note</th><th>Enseignant</th><th>État</th><th>Décision</th></tr>
            </thead>
            <tbody>
            @forelse($grades as $grade)
                <tr>
                    <td>{{ $grade->student->first_name }} {{ $grade->student->last_name }}<div class="small text-secondary">{{ $grade->student->matricule }}</div></td>
                    <td>
                        <strong>{{ $grade->course->code }} · {{ $grade->course->name }}</strong>
                        <div>{{ $grade->assessment_name }} · {{ ['controle'=>'Contrôle continu','examen'=>'Examen','devoir'=>'Devoir','tp'=>'Travaux pratiques','autre'=>'Autre'][$grade->assessment_type] ?? $grade->assessment_type }}</div>
                        <div class="small text-secondary">{{ $grade->assessed_on->locale('fr')->translatedFormat('d F Y') }}</div>
                        @if($grade->comment)
                            <div class="small mt-1">Commentaire : {{ $grade->comment }}</div>
                        @endif
                    </td>
                    <td class="text-nowrap"><strong>{{ rtrim(rtrim(number_format((float) $grade->score, 2, ',', ''), '0'), ',') }}/20</strong></td>
                    <td>{{ $grade->teacher->first_name }} {{ $grade->teacher->last_name }}</td>
                    <td>
                        <span class="badge text-bg-{{ $grade->status === 'pending' ? 'warning' : ($grade->status === 'published' ? 'success' : 'danger') }}">
                            {{ ['pending'=>'En attente','published'=>'Publiée','rejected'=>'À corriger'][$grade->status] ?? $grade->status }}
                        </span>
                        @if($grade->review_comment)
                            <div class="small text-secondary mt-1">{{ $grade->review_comment }}</div>
                        @endif
                        @if($grade->reviewer)
                            <div class="small text-secondary">{{ $grade->reviewer->prenom }} {{ $grade->reviewer->name }}</div>
                        @endif
                    </td>
                    <td>
                        @if($grade->status === 'pending')
                            <form method="POST" action="{{ route('admin.grades.review', $grade) }}">
                                @csrf
                                <label class="visually-hidden" for="review-comment-{{ $grade->id }}">Motif du refus</label>
                                <textarea class="form-control form-control-sm mb-2" id="review-comment-{{ $grade->id }}" name="review_comment" rows="2" maxlength="1000" placeholder="Commentaire ; obligatoire en cas de refus">{{ old('review_comment') }}</textarea>
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-sm btn-success" name="decision" value="published" type="submit">Publier</button>
                                    <button class="btn btn-sm btn-outline-danger" name="decision" value="rejected" type="submit">Renvoyer à l’enseignant</button>
                                </div>
                            </form>
                        @else
                            <span class="small text-secondary">
                                @if($grade->reviewed_at)
                                    {{ $grade->reviewed_at->locale('fr')->translatedFormat('d F Y à H:i') }}
                                @else
                                    Décision enregistrée
                                @endif
                            </span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-4 text-secondary">Aucune note ne correspond à ces filtres.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $grades->links() }}</div>
</div>
@endsection
