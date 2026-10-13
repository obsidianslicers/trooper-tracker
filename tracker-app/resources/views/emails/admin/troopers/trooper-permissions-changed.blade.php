@extends('layouts.email')

@section('message')
    <p>
        Hello {{ $trooper->display_name }},
    </p>

    <p>
        An administrator has updated your permissions.
    </p>

    @if(count($changes) > 0)
        <p>
            @foreach($changes as $change)
                {{ $change }}<br>
            @endforeach
        </p>
    @endif

    <p>
        If you have questions about this change, please contact your command staff.
    </p>

    @include('emails.inc.signature')

@endsection
