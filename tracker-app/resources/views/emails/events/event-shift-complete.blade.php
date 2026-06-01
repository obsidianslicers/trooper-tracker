@extends('layouts.email')

@section('message')
    <p>
        Mission Debrief Required, Trooper!
    </p>

    <p>
        <b>{{ $event->name }}</b>
    </p>

    <p>
        <b>Shift</b>: {{ $event_shift->full_date_display }} {{ $event_shift->compact_time_display }}
    </p>

    <p>
        Our sensors indicate you were present, though their accuracy drops in
        real-world conditions. Your confirmation will settle the matter.
    </p>

    <p>
        Now comes the part that separates the true servants of the Empire from the ones who
        mysteriously "forget" things. Failure to do so will result in your troop credit remaining
        in a state of bureaucratic limbo in an uncharted region feared even by Imperial auditors.
        And trust us, you do not want Imperial auditors involved in your life.
    </p>

    <br />

    <table width="100%"
           cellpadding="0"
           cellspacing="0">
        <tbody>
            <tr>
                <td class="content-block">
                    <a href="{{ $event_trooper->getAttendanceUrl(\App\Enums\EventTrooperStatus::ATTENDED) }}"
                       class="btn-success">
                        I Served the Empire
                    </a>
                </td>
            </tr>
        </tbody>
    </table>

    <br />

    <table width="100%"
           cellpadding="0"
           cellspacing="0">
        <tbody>
            <tr>
                <td class="content-block">
                    <a href="{{ $event_trooper->getAttendanceUrl(\App\Enums\EventTrooperStatus::UNABLE_TO_ATTEND) }}"
                       class="btn-danger">
                        I Failed the Empire (Sorry)
                    </a>
                </td>
            </tr>
        </tbody>
    </table>

    @include('emails.inc.signature')

@endsection