@extends('layouts.app')
@section('title','Fiche cours')
@section('page-title','Cours')
@section('content')
<div class="page-heading"><div><h1>{{ $course->name }}</h1><p>{{ $course->code }}</p></div>@unless(auth()->user()->hasRole('director_general'))<a class="btn btn-primary" href="{{ route('admin.courses.edit',$course) }}">Modifier</a>@endunless</div><div class="card"><div class="card-body row g-4"><div class="col-md-6"><small class="text-secondary">Formation</small><div>{{ $course->formation?->name ?: '—' }}</div></div><div class="col-md-6"><small class="text-secondary">Département</small><div>{{ $course->department?->name ?: '—' }}</div></div><div class="col-md-6"><small class="text-secondary">Enseignants</small><div>{{ $course->teachers->map(fn($teacher)=>$teacher->first_name.' '.$teacher->last_name)->join(', ') ?: '—' }}</div></div><div class="col-md-6"><small class="text-secondary">Crédits / volume hebdomadaire</small><div>{{ $course->credits }} ECTS · {{ $course->weekly_hours }} h</div></div><div class="col-12"><small class="text-secondary">Description</small><div>{{ $course->description ?: '—' }}</div></div></div></div>
@endsection
