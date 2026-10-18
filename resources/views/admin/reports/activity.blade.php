@extends('layouts.app')
@section('title','Journal d’activité')
@section('page-title','Journal d’activité')
@section('content')
<div class="page-heading"><div><h1>Historique des activités</h1><p>Suivez les opérations effectuées dans l’espace académique.</p></div></div>
<div class="card"><div class="card-body border-bottom"><form class="row g-2" method="GET"><div class="col-md-6"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Rechercher une action ou un utilisateur"></div><div class="col-auto"><button class="btn btn-outline-primary">Rechercher</button></div></form></div>
<div class="table-responsive"><table class="table mb-0"><thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Objet</th><th>Adresse IP</th></tr></thead><tbody>@forelse($activities as $activity)<tr><td>{{ $activity->created_at->locale('fr')->translatedFormat('d/m/Y H:i') }}</td><td>{{ $activity->user?->name ?? 'Utilisateur supprimé' }}</td><td>{{ ['created'=>'Création','updated'=>'Modification','deleted'=>'Suppression'][$activity->action] ?? $activity->action }}</td><td>{{ class_basename($activity->subject_type ?? '') }} #{{ $activity->subject_id }}</td><td>{{ $activity->ip_address ?? '—' }}</td></tr>@empty<tr><td colspan="5"><div class="empty-state"><i class="bi bi-clock-history"></i>Aucune activité enregistrée.</div></td></tr>@endforelse</tbody></table></div><div class="card-body">{{ $activities->links() }}</div></div>
@endsection
