@extends('layouts.email')

@section('message')
    <p>
        Hello {{ $trooper->display_name }},
    </p>

    <p>
        An administrator has updated your permissions.
    </p>

    @include('emails.inc.permission-changes')

    <p>
        If you have questions about this change, please contact your command staff.
    </p>

    @include('emails.inc.signature')

@endsection
