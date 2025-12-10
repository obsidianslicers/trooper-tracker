@extends('layouts.base')

@section('page-title', 'Trooper Costumes')

@section('content')

@include('pages.account.tabs')

<x-slim-container>

  <!-- Trooper Costumes -->
  <x-card>
    <div hx-get="{{ route('account.costumes-htmx') }}"
         hx-trigger="load"
         hx-swap="outerHTML">
      <x-loading />
    </div>
  </x-card>

</x-slim-container>

@endsection