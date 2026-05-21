@extends('layouts.email')

@section('message')
    <p>
        Trooper {{ $trooper->display_name }},
    </p>
    <p>
        Your 6-month visitor access to <em>{{ config('app.name') }}</em> expired on
        <strong>{{ $trooper->visitor_expires_at->format('F j, Y') }}</strong>.
    </p>
    <p>
        To continue participating, log in and submit a renewal request. Command Staff will
        review your request and restore your access if approved.
    </p>
    <p>
        <a href="{{ route('account.visitor-renew') }}">Request Renewal</a>
    </p>

    @include('emails.inc.signature')
@endsection
