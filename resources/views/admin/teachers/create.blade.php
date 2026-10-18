@extends('layouts.app')
@section('title','Ajouter un enseignant')
@section('page-title','Enseignants')
@section('content')<div class="page-heading"><div><h1>Ajouter un enseignant</h1><p>Créez un profil enseignant.</p></div></div>@include('admin.teachers.form',['teacher'=>null])@endsection
