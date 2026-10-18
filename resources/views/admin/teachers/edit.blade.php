@extends('layouts.app')
@section('title','Modifier un enseignant')
@section('page-title','Enseignants')
@section('content')<div class="page-heading"><div><h1>Modifier l’enseignant</h1><p>{{ $teacher->first_name }} {{ $teacher->last_name }}</p></div></div>@include('admin.teachers.form',['teacher'=>$teacher])@endsection
