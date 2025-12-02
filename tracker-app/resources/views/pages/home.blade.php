@extends('layouts.base')

<<<<<<< HEAD
@section('page-title', setting('site_name'))

@section('content')

=======
@section('content')

<x-page-title>
  Home TODO
</x-page-title>

>>>>>>> b60e060 (feature: add notice board)
<div class="row p-3"
     hx-get="{{ route('widgets.notices-htmx') }}"
     hx-trigger="load"
     hx-swap="outerHTML">
  <div class="col text-center">
    <x-spinner />
  </div>
</div>

<x-card :label="'Support'">
  <div hx-get="{{ route('widgets.support-htmx') }}"
       hx-trigger="load"
       hx-swap="outerHTML">
    <x-loading />
  </div>
</x-card>

@endsection