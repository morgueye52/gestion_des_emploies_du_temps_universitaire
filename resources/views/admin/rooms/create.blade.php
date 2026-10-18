@extends('layouts.app')
@section('title','Nouvelle salle')
@section('page-title','Salles')
@section('content')<div class="page-heading"><div><h1>Nouvelle salle</h1><p>Ajoutez un espace à votre établissement.</p></div></div>@include('admin.rooms.form',['room'=>null])@endsection
