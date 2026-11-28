@extends('layouts.email')

@section('message')
    @if($is_reminder)
        <p>
            Reminder: this request is still waiting for you. If you don't answer in
            {{ $days_left }} days, the moderators will decide it instead.
        </p>
    @endif

    <p>
        <strong>{{ $trooper_name }}</strong> says they trooped at your event but never signed up
        in the tracker:
    </p>

    <p>
        <b>{{ $event_name }}</b><br />
        {{ $shift->full_date_display }} {{ $shift->compact_time_display }}
    </p>

    <p>
        What they told us:
    </p>

    <blockquote>{{ $details }}</blockquote>

    <p>
        Were they there? Confirm and the troop counts. Deny and the moderators take a look.
    </p>

    <br />

    <table width="100%"
           cellpadding="0"
           cellspacing="0">
        <tbody>
            <tr>
                <td class="content-block">
                    <a href="{{ $review_url }}"
                       class="btn-success">
                        Confirm or Deny
                    </a>
                </td>
            </tr>
        </tbody>
    </table>

    @include(email_view('inc.signature'))

@endsection
