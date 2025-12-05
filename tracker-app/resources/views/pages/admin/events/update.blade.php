@extends('layouts.base')

@section('page-title', 'Update Event')

@section('content')

<x-transmission-bar :id="'event'" />

@include('pages.admin.events.tabs', ['event'=>$event])

<x-slim-container>

  <x-card>
    @include('pages.admin.events.update-form', ['event'=>$event])
  </x-card>

</x-slim-container>

@endsection