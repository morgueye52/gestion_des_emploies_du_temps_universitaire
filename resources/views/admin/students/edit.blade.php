@extends('layouts.app')
@section('title', 'Modifier un étudiant')
@section('page-title', 'Étudiants')
@section('content')<div class="page-heading"><div><h1>Modifier l’étudiant</h1><p>{{ $student->matricule }}</p></div></div>@include('admin.students.form',['student'=>$student])@endsection
