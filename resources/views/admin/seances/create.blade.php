@extends('layouts.app')

@section('title', 'Nouvelle séance')
@section('page-title', 'Planification')

@section('content')
    <div class="page-heading"><div><h1>Nouvelle séance</h1><p>Les conflits de salle, d’enseignant et de groupe sont vérifiés avant l’enregistrement.</p></div></div>
    @include('admin.seances.form', ['seance' => null])
@endsection
