@extends('layouts.base')

@section('page-title', config('app.name'))

@section('content')
    <div class="row justify-content-center gy-4">
        {{-- Phones only, where Login is hidden in the collapsed navbar; above the image so it's on screen --}}
        <div class="col-12 col-sm-10 d-grid d-md-none">
            <a href="{{ $login_url }}"
               class="btn btn-primary btn-lg py-3 fs-4"
               data-test="home-login">
                <i class="fa fa-fw fa-right-to-bracket me-2"></i>{{ $login_label }}
            </a>
        </div>
        <div class="col-12 text-center">
            <img src="{{ url('img/logo.jpg') }}"
                 class="img-fluid" />
        </div>
    </div>
@endsection
