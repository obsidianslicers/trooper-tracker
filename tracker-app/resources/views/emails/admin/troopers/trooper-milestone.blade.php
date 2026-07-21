@extends('layouts.email')

@section('message')
    <p>
        Esteemed Command Staff,
    </p>

    <p>
        Imperial records have been updated. The following troopers under your jurisdiction have
        distinguished themselves through <b>exemplary service to the Empire</b>.
    </p>

    @foreach ($achievements->groupBy('trooper_id') as $trooper_achievements)
        @php($trooper = $trooper_achievements->first()->trooper)
        <h3>
            <a href="{{ route('admin.troopers.profile', $trooper) }}">
                {{ $trooper->legal_name }} ({{ $trooper->display_name }})
            </a>
        </h3>
        <p>{{ $trooper->trooper_assignments->pluck('organization.name')->join(', ') }}</p>
        <ul>
            @foreach ($trooper_achievements as $achievement)
                <li>{{ $achievement->display_description }}</li>
            @endforeach
        </ul>
    @endforeach

    <p><a href="{{ route('service-records.achievements') }}">Review recent achievements</a>.</p>

    <p>
        The Empire commends this trooper's dedication. Glory to the Emperor.
    </p>

    @include('emails.inc.signature')

@endsection
