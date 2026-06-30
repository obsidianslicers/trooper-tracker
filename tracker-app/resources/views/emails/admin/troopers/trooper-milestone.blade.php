@extends('layouts.email')

@section('message')
    <p>
        Esteemed Command Staff,
    </p>

    <p>
        Imperial records have been updated. A trooper under your jurisdiction has distinguished
        themselves through <b>exemplary service to the Empire</b> and has earned a new milestone
        achievement.
    </p>

    <p>
        <strong>Trooper:</strong> {{ $achievement->trooper->legal_name }} ({{ $achievement->trooper->display_name }})<br>
        <strong>Achievement:</strong> {{ $achievement->type->toDescription() }}
    </p>

    <ul>
        @foreach ($achievement->trooper->trooper_assignments as $assignment)
            <li>{{ $assignment->organization->name }}</li>
        @endforeach
    </ul>

    <p>
        You may review this trooper's full service record in the
        <a href="{{ route('admin.troopers.profile', $achievement->trooper) }}">Imperial Personnel Registry</a>.
    </p>

    <p>
        The Empire commends this trooper's dedication. Glory to the Emperor.
    </p>

    @include('emails.inc.signature')

@endsection