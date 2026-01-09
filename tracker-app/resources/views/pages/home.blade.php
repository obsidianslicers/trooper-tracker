@extends('layouts.base')

@section('page-title', config('app.name'))

@section('content')
    <div class="row">
        <div class="col text-center">
            <img src="{{ url('img/logo.jpg') }}"
                 class="img-fluid" />
        </div>
    </div>
@endsection