@extends('layouts.app')
@section('title', 'Modifier une note')
@section('page-title', 'Gestion des notes')
@section('content')
<div class="page-heading">
    <div>
        <h1>Modifier une note</h1>
        <p>{{ $grade->student->first_name }} {{ $grade->student->last_name }} · {{ $grade->course->name }}</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('teacher.grades.index', ['course_id' => $grade->course_id]) }}"><i class="bi bi-arrow-left me-2"></i>Retour aux notes</a>
</div>
<form class="card p-4" method="POST" action="{{ route('teacher.grades.update', $grade) }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="course_id" value="{{ $grade->course_id }}">
    <input type="hidden" name="student_id" value="{{ $grade->student_id }}">
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="assessment_name">Nom de l’évaluation *</label><input class="form-control" id="assessment_name" name="assessment_name" value="{{ old('assessment_name', $grade->assessment_name) }}" maxlength="120" required></div>
        <div class="col-md-6">
            <label class="form-label" for="assessment_type">Type *</label>
            <select class="form-select" id="assessment_type" name="assessment_type" required>
                @foreach(['controle'=>'Contrôle continu','examen'=>'Examen','devoir'=>'Devoir','tp'=>'Travaux pratiques','autre'=>'Autre'] as $value=>$label)
                    <option value="{{ $value }}" @selected(old('assessment_type', $grade->assessment_type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6"><label class="form-label" for="score">Note sur 20 *</label><input class="form-control" type="number" id="score" name="score" min="0" max="20" step="0.01" value="{{ old('score', $grade->score) }}" required></div>
        <div class="col-md-6"><label class="form-label" for="assessed_on">Date de l’évaluation *</label><input class="form-control" type="date" id="assessed_on" name="assessed_on" max="{{ now()->toDateString() }}" value="{{ old('assessed_on', $grade->assessed_on->toDateString()) }}" required></div>
        <div class="col-12"><label class="form-label" for="comment">Commentaire (facultatif)</label><textarea class="form-control" id="comment" name="comment" rows="3" maxlength="2000" placeholder="Appréciation ou remarque destinée à l’étudiant.">{{ old('comment', $grade->comment) }}</textarea></div>
    </div>
    <div class="d-flex justify-content-end mt-4"><button class="btn btn-primary" type="submit">Enregistrer les modifications</button></div>
</form>
@endsection
