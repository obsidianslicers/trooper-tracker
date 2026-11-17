@extends('layouts.email')

@section('message')
    <p>
        Hello {{ $trooper->display_name }},
    </p>

    <p>
        An administrator has updated your permissions.
    </p>

    @include(email_view('inc.permission-changes'))

    <p>
        If you have questions about this change, please contact your command staff.
    </p>

    @include(email_view('inc.signature'))

@endsection
