@extends('layouts.email')

@section('message')
    <p>
        Trooper!
    </p>
    @if($event_trooper->is_stand_by)
        <p>
            <b>AT FULL CAPACITY:</b> This deployment is currently at full capacity, you have been
            placed on <b>stand-by</b>. Should a trooper ahead of you mysteriously "reconsider their availability,"
            you will be automatically promoted to <b>GOING</b> status. No action IS required on your part - Imperial
            logistics will handle the reshuffling with their usual blend of efficiency and mild intimidation.
        </p>
    @endif
    @if($event_trooper->added_by_trooper_id)
        <p>
            Another trooper has volunteered you for this deployment. Whether this reflects their confidence
            in your abilities or their talent for delegation is, frankly, unclear.
        </p>
    @else
        <p>
            Your intent to deploy has been recorded by Imperial command. The event organizers have been
            notified of your <b>impressive enthusiasm</b>
            (or at least your inability to resist clicking shiny buttons).
        </p>
    @endif
    <p>
        Consider this your official notice to prepare accordingly. Ensure your armor is polished,
        your posture is intimidating, and your excuses - should you need them - are more convincing than
        the last trooper who blamed "unexpected Jawa interference."
    </p>

    @include('emails.events.inc.details', compact('event', 'event_shift', 'link'))

    @include('emails.inc.signature')

@endsection