@extends('layouts.app')
@section('title', 'Nouvelle invitation')
@section('page-title', 'Invitations')
@section('content')
<div class="page-heading"><div><h1>Inviter un membre</h1><p>La personne recevra un lien privé pour saisir ses informations et choisir son mot de passe.</p></div></div>
<form class="card p-4" method="POST" action="{{ route('admin.invitations.store') }}">
    @csrf
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="email">Adresse électronique de la personne invitée</label><input class="form-control" type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="Ex. prenom.nom@universite.edu">@error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label" for="role">Rôle à attribuer</label><select class="form-select" id="role" name="role" required>@foreach($roles as $value=>$label)<option value="{{ $value }}" @selected(old('role')===$value)>{{ $label }}</option>@endforeach</select>@error('role')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label" for="department_id">Département</label><select class="form-select" id="department_id" name="department_id"><option value="">Choisir un département si le rôle l’exige</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>@endforeach</select><small class="text-secondary">Obligatoire pour un Responsable de Département.</small>@error('department_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
    </div>
    <div class="alert alert-info mt-4 mb-0"><i class="bi bi-shield-lock me-2"></i>Le lien est à usage unique et expire après 7 jours. Ne le partagez qu’avec la personne associée à cette adresse électronique.</div>
    <div class="d-flex justify-content-end gap-2 mt-4"><a class="btn btn-light" href="{{ route('admin.invitations.index') }}">Annuler</a><button class="btn btn-primary">Créer l’invitation</button></div>
</form>
@endsection
