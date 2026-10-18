@extends('layouts.app')
@section('title','Modifier un cours')
@section('page-title','Cours')
@section('content')<div class="page-heading"><div><h1>Modifier le cours</h1><p>{{ $course->code }}</p></div></div>@include('admin.courses.form',['course'=>$course])@endsection
