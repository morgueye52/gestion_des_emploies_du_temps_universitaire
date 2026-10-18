@extends('layouts.app')
@section('title', 'Gestion des notes')
@section('page-title', 'Gestion des notes')
@section('content')
<div class="page-heading">
    <div>
        <h1>Gestion des notes</h1>
        <p>Saisissez les résultats des étudiants inscrits à vos cours. Les notes sont sur 20.</p>
    </div>
</div>

@if(!$teacher)
    <div class="alert alert-info">Votre compte n’est pas associé à un profil enseignant actif. Contactez l’administration.</div>
@else
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('teacher.grades.index') }}" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label" for="course_id">Cours</label>
                    <select class="form-select" id="course_id" name="course_id" required>
                        <option value="">Choisir un cours</option>
                        @foreach($courses as $option)
                            <option value="{{ $option->id }}" @selected($course?->id === $option->id)>{{ $option->code }} · {{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4"><button class="btn btn-primary" type="submit">Afficher les étudiants</button></div>
            </form>
        </div>
    </div>

    @if($course)
        <div class="card mb-4">
            <div class="card-header py-3 px-4">Saisir une note · {{ $course->code }} — {{ $course->name }}</div>
            <div class="card-body">
                @if($students->isEmpty())
                    <p class="text-secondary mb-0">Aucun étudiant actif n’est inscrit à ce cours.</p>
                @else
                    <form method="POST" action="{{ route('teacher.grades.store') }}">
                        @csrf
                        <input type="hidden" name="course_id" value="{{ $course->id }}">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="student_id">Étudiant *</label>
                                <select class="form-select" id="student_id" name="student_id" required>
                                    <option value="">Choisir un étudiant inscrit</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->matricule }} · {{ $student->first_name }} {{ $student->last_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="assessment_name">Nom de l’évaluation *</label>
                                <input class="form-control" id="assessment_name" name="assessment_name" value="{{ old('assessment_name') }}" placeholder="Ex. Contrôle continu 1" maxlength="120" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="assessment_type">Type *</label>
                                <select class="form-select" id="assessment_type" name="assessment_type" required>
                                    @foreach(['controle'=>'Contrôle continu','examen'=>'Examen','devoir'=>'Devoir','tp'=>'Travaux pratiques','autre'=>'Autre'] as $value=>$label)
                                        <option value="{{ $value }}" @selected(old('assessment_type', 'controle') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="score">Note sur 20 *</label>
                                <input class="form-control" type="number" id="score" name="score" min="0" max="20" step="0.01" value="{{ old('score') }}" placeholder="Ex. 15,5" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="assessed_on">Date de l’évaluation *</label>
                                <input class="form-control" type="date" id="assessed_on" name="assessed_on" max="{{ now()->toDateString() }}" value="{{ old('assessed_on', now()->toDateString()) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="comment">Commentaire (facultatif)</label>
                                <textarea class="form-control" id="comment" name="comment" rows="2" maxlength="2000" placeholder="Appréciation ou remarque destinée à l’étudiant.">{{ old('comment') }}</textarea>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3"><button class="btn btn-primary" type="submit">Enregistrer la note</button></div>
                    </form>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header py-3 px-4">Notes déjà saisies</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Étudiant</th><th>Évaluation</th><th>Type</th><th>Note</th><th>Date</th><th>Commentaire</th><th></th></tr></thead>
                    <tbody>
                    @forelse($grades as $grade)
                        <tr>
                            <td>{{ $grade->student->first_name }} {{ $grade->student->last_name }}<br><small class="text-secondary">{{ $grade->student->matricule }}</small></td>
                            <td>{{ $grade->assessment_name }}</td>
                            <td>{{ ['controle'=>'Contrôle continu','examen'=>'Examen','devoir'=>'Devoir','tp'=>'Travaux pratiques','autre'=>'Autre'][$grade->assessment_type] ?? $grade->assessment_type }}</td>
                            <td><strong>{{ rtrim(rtrim(number_format((float)$grade->score, 2, ',', ''), '0'), ',') }}/20</strong></td>
                            <td>{{ $grade->assessed_on->locale('fr')->translatedFormat('d F Y') }}</td>
                            <td>{{ $grade->comment ?: '—' }}</td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('teacher.grades.edit', $grade) }}">Modifier</a>
                                <form class="d-inline" method="POST" action="{{ route('teacher.grades.destroy', $grade) }}" onsubmit="return confirm('Supprimer cette note ?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-secondary">Aucune note n’a encore été saisie pour ce cours.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @elseif($courses->isEmpty())
        <div class="alert alert-info">Aucun cours actif ne vous est affecté pour le moment.</div>
    @endif
@endif
@endsection
