@extends('layouts.email')

@section('message')
    <p>
        Trooper {{ $trooper->display_name }},
    </p>

    <p>
        Your 6-month visitor access to <em>{{ config('app.name') }}</em> officially
        expired on <strong>{{ $trooper->visitor_expires_at->format('F j, Y') }}</strong>.
        The system held a brief moment of silence. Very brief.
    </p>

    <p>
        To continue participating, you'll need to log in and submit a renewal request.
        Command Staff will review it and determine whether your continued presence
        is a blessing, a burden, or merely tolerable.
    </p>

    <p>
        <a href="{{ route('account.visitor-renew') }}">Request Renewal</a>
    </p>

    @include('emails.inc.signature')
@endsection