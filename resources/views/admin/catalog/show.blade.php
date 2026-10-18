@extends('layouts.app')
@section('title', $item->name)
@section('page-title', $definition['title'])
@section('content')
<div class="page-heading"><div><h1>{{ $item->name }}</h1><p>{{ $definition['singular'] }} · Performics University</p></div>@if($canManage)<a class="btn btn-primary" href="{{ route("admin.academic.{$entity}.edit",$item->id) }}">Modifier</a>@endif</div>
<div class="card"><div class="card-body row g-4">
    @foreach($definition['fields'] as $field)
        <div class="col-md-6"><small class="text-secondary">{{ $labels[$field] ?? str($field)->replace('_',' ')->title() }}</small><div>
            @if(str_ends_with($field,'_id'))
                @php($relation = str($field)->beforeLast('_id')->camel()->toString())
                {{ $item->{$relation}?->name ?? '—' }}
            @elseif($field==='is_active')
                {{ $item->is_active ? 'Actif' : 'Inactif' }}
            @elseif(in_array($field,['starts_on','ends_on'],true))
                {{ $item->{$field}?->format('d/m/Y') ?? '—' }}
            @else
                {{ $item->{$field} ?: '—' }}
            @endif
        </div></div>
    @endforeach
</div></div>
@endsection
