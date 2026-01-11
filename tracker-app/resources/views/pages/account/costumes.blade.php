@extends('layouts.base')

@section('page-title', 'Trooper Costumes')

@section('content')

    @include('pages.account.tabs')

    <x-slim-container>

        <!-- Trooper Costumes -->
        <x-card>
            @include('pages.account.costume-selector')
        </x-card>

    </x-slim-container>

@endsection