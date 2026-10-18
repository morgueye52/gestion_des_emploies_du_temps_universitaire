@extends('layouts.app')
@section('title','Modifier une salle')
@section('page-title','Salles')
@section('content')<div class="page-heading"><div><h1>Modifier la salle</h1><p>{{ $room->code }}</p></div></div>@include('admin.rooms.form',['room'=>$room])@endsection
