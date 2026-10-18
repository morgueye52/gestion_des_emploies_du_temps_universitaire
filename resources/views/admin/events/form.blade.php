@extends('layouts.app')
@section('title',$event ? 'Modifier un événement' : 'Ajouter un événement')
@section('page-title','Calendrier académique')
@section('content')
<div class="page-heading"><div><h1>{{ $event ? 'Modifier' : 'Ajouter' }} un événement</h1><p>Ajoutez une date importante au calendrier de l’établissement.</p></div></div>
<form class="card p-4" method="POST" action="{{ $event ? route('admin.events.update',$event) : route('admin.events.store') }}">@csrf @if($event) @method('PUT') @endif
<div class="row g-3">
<div class="col-md-8"><label class="form-label">Titre *</label><input required class="form-control" name="title" value="{{ old('title',$event?->title) }}" placeholder="Ex. Rentrée universitaire"></div>
<div class="col-md-4"><label class="form-label">Catégorie *</label><select class="form-select" name="type">@foreach(['course_start'=>'Début des cours','course_end'=>'Fin des cours','exam'=>'Examens','holiday'=>'Vacances / jour férié','registration'=>'Inscriptions','defense'=>'Soutenances','event'=>'Événement universitaire'] as $value=>$label)<option value="{{ $value }}" @selected(old('type',$event?->type ?? 'event')===$value)>{{ $label }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Début *</label><input required type="datetime-local" class="form-control" name="starts_at" value="{{ old('starts_at',$event?->starts_at?->format('Y-m-d\TH:i')) }}"></div>
<div class="col-md-6"><label class="form-label">Fin *</label><input required type="datetime-local" class="form-control" name="ends_at" value="{{ old('ends_at',$event?->ends_at?->format('Y-m-d\TH:i')) }}"></div>
<div class="col-12"><label class="form-label">Description</label><textarea class="form-control" rows="4" name="description" placeholder="Précisez le contexte ou l’objectif de cet événement.">{{ old('description',$event?->description) }}</textarea></div>
</div><div class="d-flex justify-content-end gap-2 mt-4"><a class="btn btn-light" href="{{ route('admin.events.index') }}">Annuler</a><button class="btn btn-primary">Enregistrer</button></div></form>
@endsection
