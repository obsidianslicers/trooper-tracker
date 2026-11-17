@extends('layouts.email')

@section('message')

    <h1 style="margin-top: 0;">✅ Queue Worker Recovered</h1>

    <p>Hi {{ $trooper->first_name }},</p>

    <p>
        The queue worker heartbeat is fresh again - Supervisor appears to be keeping
        <code>queue:work</code> running normally. No further action is needed.
    </p>
@endsection
