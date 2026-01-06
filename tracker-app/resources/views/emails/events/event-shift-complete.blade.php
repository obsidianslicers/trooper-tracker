@extends('layouts.email')

@section('message')
    <p>
        Mission Debrief Required, Trooper!
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
                    <a href="{{ route('events.shift-complete', ['event_trooper' => $event_trooper, 'status' => $able_status]) }}"
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
                    <a href="{{ route('events.shift-complete', ['event_trooper' => $event_trooper, 'status' => $unable_status]) }}"
                       class="btn-success">
                        I Failed the Empire (Sorry)
                    </a>
                </td>
            </tr>
        </tbody>
    </table>

    <p style="margin-top:20px; font-weight:bold; color:#333;">
        - Imperial Administration, {{ config('app.name') }}
    </p>
@endsection