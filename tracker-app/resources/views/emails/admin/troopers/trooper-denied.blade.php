@extends('layouts.email')

@section('message')
    <p>
        Trooper {{ $trooper->display_name }},
    </p>

    <p>
        Your registration was not approved at this time.
    </p>

    @if($denial_reason)
        <p>The reviewer provided the following feedback:</p>
        <blockquote>{{ $denial_reason }}</blockquote>
    @endif

    <p>
        If you believe this was in error or have questions, please reach out to the
        membership team for assistance.
    </p>

    @include('emails.inc.signature')

@endsection
