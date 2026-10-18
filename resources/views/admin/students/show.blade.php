@extends('layouts.app')
@section('title', 'Fiche étudiant')
@section('page-title', 'Étudiants')
@section('content')
<div class="page-heading"><div><h1>{{ $student->first_name }} {{ $student->last_name }}</h1><p>Matricule {{ $student->matricule }}</p></div>@unless(auth()->user()->hasRole('director_general'))<a class="btn btn-primary" href="{{ route('admin.students.edit',$student) }}">Modifier</a>@endunless</div>
<div class="card"><div class="card-body row g-4"><div class="col-md-6"><small class="text-secondary">Email</small><div>{{ $student->email ?: '—' }}</div></div><div class="col-md-6"><small class="text-secondary">Téléphone</small><div>{{ $student->phone ?: '—' }}</div></div><div class="col-md-6"><small class="text-secondary">Classe</small><div>{{ $student->academicClass?->name ?: '—' }}</div></div><div class="col-md-6"><small class="text-secondary">Groupe</small><div>{{ $student->group?->name ?: '—' }}</div></div></div></div>
@endsection
