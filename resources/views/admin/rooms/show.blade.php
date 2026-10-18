@extends('layouts.app')
@section('title','Fiche salle')
@section('page-title','Salles')
@section('content')
<div class="page-heading"><div><h1>{{ $room->name }}</h1><p>{{ $room->code }}</p></div>@unless(auth()->user()->hasRole(['director_general', 'academic_direction', 'studies_manager', 'department_manager']))<a class="btn btn-primary" href="{{ route('admin.rooms.edit',$room) }}">Modifier</a>@endunless</div>
<div class="card"><div class="card-body row g-4"><div class="col-md-4"><small class="text-secondary">Bâtiment</small><div>{{ $room->building ?: '—' }}</div></div><div class="col-md-4"><small class="text-secondary">Étage</small><div>{{ $room->floor ?: '—' }}</div></div><div class="col-md-4"><small class="text-secondary">Capacité</small><div>{{ $room->capacity }} places</div></div><div class="col-md-4"><small class="text-secondary">Type</small><div>{{ str_replace('_',' ',$room->type) }}</div></div><div class="col-md-4"><small class="text-secondary">Statut</small><div>{{ $room->status }}</div></div></div></div>
<section class="card mt-3"><div class="card-header py-3 px-4">Séances planifiées</div><div class="card-body">@forelse($room->sessions as $session)<div>{{ $session->course?->name }} · {{ substr($session->starts_at,0,5) }}–{{ substr($session->ends_at,0,5) }}</div>@empty<div class="text-secondary">Aucune séance planifiée.</div>@endforelse</div></section>
@endsection
