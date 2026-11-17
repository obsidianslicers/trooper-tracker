@extends('layouts.base')

@section('page-title', __('account.Trooper Notices'))

@section('content')

    <x-transmission-bar :id="'notices'" />

    @foreach($notices as $notice)
        @include('partials.notice', ['notice', $notice])
    @endforeach

@endsection