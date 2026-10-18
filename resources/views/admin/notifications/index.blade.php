@extends('layouts.app')
@section('title','Notifications')
@section('page-title','Notifications')
@section('content')
<div class="page-heading"><div><h1>Notifications</h1><p>Suivez les changements de votre emploi du temps et de vos activités.</p></div></div>
<div class="card"><div class="list-group list-group-flush">
@forelse($notifications as $notification)<div class="list-group-item d-flex align-items-start justify-content-between gap-3 py-3"><div><div class="fw-semibold">{{ $notification->data['title'] ?? 'Notification' }}</div><div class="small mt-1">{{ $notification->data['message'] ?? '' }}</div><small class="text-secondary">{{ $notification->created_at->locale('fr')->diffForHumans() }}</small></div>@if(!$notification->read_at)<form method="POST" action="{{ route('notifications.read',$notification->id) }}">@csrf<button class="btn btn-sm btn-outline-primary">Marquer comme lue</button></form>@else<span class="small text-secondary">Lue</span>@endif</div>@empty<div class="empty-state"><i class="bi bi-bell"></i>Vous n’avez aucune notification.</div>@endforelse
</div><div class="card-body">{{ $notifications->links() }}</div></div>
@endsection
