@extends('layouts.app')
@section('title', 'Invitations')
@section('page-title', 'Invitations')
@section('content')
<div class="page-heading">
    <div><h1>Invitations privées</h1><p>Invitez chaque membre du personnel à créer son compte avec son propre nom et mot de passe.</p></div>
    <a class="btn btn-primary" href="{{ route('admin.invitations.create') }}"><i class="bi bi-person-plus me-2"></i>Nouvelle invitation</a>
</div>
@if(session('invitation_url'))
    <div class="card border-warning mb-4"><div class="card-body">
        <h2 class="h6">Lien privé pour {{ session('invitation_email') }}</h2>
        <p class="small text-secondary">Transmettez ce lien directement à la personne invitée. Il expire dans 7 jours et ne peut être utilisé qu’une seule fois.</p>
        <input class="form-control" value="{{ session('invitation_url') }}" readonly onclick="this.select()">
    </div></div>
@endif
<div class="card"><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Adresse électronique</th><th>Rôle</th><th>Expire le</th><th>Créée par</th><th></th></tr></thead>
    <tbody>
    @forelse($invitations as $invitation)
        <tr><td>{{ $invitation->email }}</td><td>{{ str($invitation->role)->replace('_', ' ')->title() }}@if($invitation->department)<br><small class="text-secondary">{{ $invitation->department->name }}</small>@endif</td><td>{{ $invitation->expires_at->locale('fr')->translatedFormat('d F Y à H:i') }}</td><td>{{ $invitation->inviter?->prenom }} {{ $invitation->inviter?->name }}</td><td class="text-end">
            <form method="POST" action="{{ route('admin.invitations.destroy', $invitation) }}" onsubmit="return confirm('Révoquer cette invitation ?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-secondary">Révoquer</button></form>
        </td></tr>
    @empty
        <tr><td colspan="5"><div class="empty-state"><i class="bi bi-envelope-check"></i>Aucune invitation en attente.</div></td></tr>
    @endforelse
    </tbody>
</table></div><div class="p-3">{{ $invitations->links() }}</div></div>
@endsection
