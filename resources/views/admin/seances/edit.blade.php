@extends('layouts.app')

@section('title', 'Modifier une séance')
@section('page-title', 'Planification')

@section('content')
    <div class="page-heading"><div><h1>Modifier la séance</h1><p>Une modification est refusée si elle crée un conflit.</p></div></div>
    @include('admin.seances.form', ['seance' => $seance])
@endsection
