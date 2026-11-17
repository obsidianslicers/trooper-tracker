@extends('layouts.email')

@section('message')

    <h1 style="margin-top: 0;">⚠️ Queue Worker Down</h1>

    <p>Hi {{ $trooper->first_name }},</p>

    <p>
        The queue worker process has not reported a heartbeat in
        <strong>{{ $minutes_since_last_heartbeat }} minutes</strong>. This usually means
        Supervisor has stopped keeping <code>queue:work</code> running, and background
        jobs (notifications, sync jobs, event reminders) are not being processed.
    </p>

    <p>Please check the worker process on the server, e.g. <code>supervisorctl status</code>.</p>

    <p>You'll receive a follow-up email once the heartbeat recovers.</p>
@endsection
