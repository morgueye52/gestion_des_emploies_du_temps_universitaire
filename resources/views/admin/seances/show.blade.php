@extends('layouts.app')
@section('title','Détail de la séance')
@section('page-title','Séances')
@section('content')
<div class="page-heading"><div><h1>{{ $seance->course?->name }}</h1><p>Détail de la séance planifiée</p></div>@unless(auth()->user()->hasRole('director_general'))<a class="btn btn-primary" href="{{ route('admin.seances.edit',$seance) }}">Modifier</a>@endunless</div>
<div class="card"><div class="card-body row g-4">
    <div class="col-md-6"><small class="text-secondary">Jour / horaire</small><div>{{ [1=>'Lundi',2=>'Mardi',3=>'Mercredi',4=>'Jeudi',5=>'Vendredi',6=>'Samedi'][$seance->weekday] ?? '—' }} · {{ substr($seance->starts_at,0,5) }}–{{ substr($seance->ends_at,0,5) }}</div></div>
    <div class="col-md-6"><small class="text-secondary">Cours</small><div>{{ $seance->course?->code }} · {{ $seance->course?->name }}</div></div>
    <div class="col-md-6"><small class="text-secondary">Enseignant</small><div>{{ $seance->teacher ? $seance->teacher->first_name.' '.$seance->teacher->last_name : '—' }}</div></div>
    <div class="col-md-6"><small class="text-secondary">Salle</small><div>{{ $seance->room?->code }} · {{ $seance->room?->name }}</div></div>
    <div class="col-md-6"><small class="text-secondary">Classe / groupe</small><div>{{ $seance->academicClass?->name ?? $seance->group?->academicClass?->name ?? '—' }}{{ $seance->group ? ' · '.$seance->group->name : ' · Toute la classe' }}</div></div>
    <div class="col-md-6"><small class="text-secondary">Type</small><div>{{ $seance->type }}</div></div>
</div></div>
@endsection
