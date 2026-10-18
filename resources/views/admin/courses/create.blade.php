@extends('layouts.app')
@section('title','Nouveau cours')
@section('page-title','Cours')
@section('content')<div class="page-heading"><div><h1>Nouveau cours</h1><p>Définissez le cours et ses affectations.</p></div></div>@include('admin.courses.form',['course'=>null])@endsection
