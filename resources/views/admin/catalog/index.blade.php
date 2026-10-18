@extends('layouts.app')
@section('title', $definition['title'])
@section('page-title', $definition['title'])
@section('content')
<div class="page-heading">
    <div><h1>{{ $definition['title'] }}</h1><p>Gérez la structure académique de l’établissement.</p></div>
    @if($canManage)
        <a class="btn btn-primary" href="{{ route("admin.academic.{$entity}.create") }}"><i class="bi bi-plus-lg me-2"></i>Ajouter</a>
    @endif
</div>
<div class="card">
    <div class="card-body border-bottom">
        <form class="row g-2" method="GET">
            <div class="col-md-6"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Rechercher par nom ou code"></div>
            <div class="col-auto"><button class="btn btn-outline-primary">Rechercher</button></div>
            @if(request('search'))<div class="col-auto"><a class="btn btn-light" href="{{ route("admin.academic.{$entity}.index") }}">Effacer</a></div>@endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Nom</th>@if(in_array('code',$definition['fields'],true))<th>Code</th>@endif @foreach($definition['fields'] as $field) @if(str_ends_with($field,'_id'))<th>{{ str($field)->beforeLast('_id')->replace('_',' ')->title() }}</th>@endif @endforeach <th>Statut</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse($records as $item)
                <tr>
                    <td><strong>{{ $item->name }}</strong></td>
                    @if(in_array('code',$definition['fields'],true))<td>{{ $item->code }}</td>@endif
                    @foreach($definition['fields'] as $field)
                        @if(str_ends_with($field,'_id'))
                            @php($relation = str($field)->beforeLast('_id')->camel()->toString())
                            <td>{{ $item->{$relation}?->name ?? '—' }}</td>
                        @endif
                    @endforeach
                    <td>@if(in_array('is_active',$definition['fields'],true))<span class="badge text-bg-{{ $item->is_active?'success':'secondary' }}">{{ $item->is_active?'Actif':'Inactif' }}</span>@else<span class="text-secondary">Actif</span>@endif</td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-light" href="{{ route("admin.academic.{$entity}.show",$item->id) }}" aria-label="Voir"><i class="bi bi-eye"></i></a>
                        @if($canManage)
                            <a class="btn btn-sm btn-light" href="{{ route("admin.academic.{$entity}.edit",$item->id) }}" aria-label="Modifier"><i class="bi bi-pencil"></i></a>
                            <form class="d-inline" method="POST" action="{{ route("admin.academic.{$entity}.destroy",$item->id) }}" onsubmit="return confirm('Supprimer cet élément ?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger" aria-label="Supprimer"><i class="bi bi-trash"></i></button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="10"><div class="empty-state"><i class="bi bi-diagram-3"></i>Aucun élément enregistré.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $records->links() }}</div>
</div>
@endsection
