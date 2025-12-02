@extends('layouts.base')

<<<<<<< HEAD
@section('page-title', 'Trooper Notices')

=======
>>>>>>> b60e060 (feature: add notice board)
@section('content')

<x-transmission-bar :id="'notices'" />

@foreach($notices as $notice)
@include('partials.notice', ['notice', $notice])
@endforeach

@endsection