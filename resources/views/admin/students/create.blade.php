@extends('layouts.app')
@section('title', 'Ajouter un étudiant')
@section('page-title', 'Étudiants')
@section('content')<div class="page-heading"><div><h1>Ajouter un étudiant</h1><p>Renseignez les informations et son inscription.</p></div></div>@include('admin.students.form',['student'=>null])@endsection
