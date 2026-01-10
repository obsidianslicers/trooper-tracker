@extends('layouts.email')

@section('message')
    <p>
        Recruit!
    </p>
    <p>
        Congratulations - you've successfully navigated the perilous journey of filling out
        the registration form. That alone proves you've got the patience required to march with
        the <b>{{ config('app.name') }}</b>. Your application has been received and is
        now in the hands of our approval team. They'll review it, check your details, and make
        sure you're ready to join the ranks.
    </p>
    <p>
        What happens next?
    </p>
    <ul>
        <li>We'll review your registration.</li>
        <li>You'll get notified once you're approved.</li>
        <li>Then the real fun begins - armor, events, and trooping glory.</li>
    </ul>
    <p>
        Thanks again for registering. You're one step closer to being part of the <b>{{ config('app.name') }}</b>.
        Until then, keep your blaster polished and your helmet fog-free.
    </p>
    <p style="margin-top:20px; font-weight:bold; color:#333;">
        - Imperial Administration, {{ config('app.name') }}
    </p>

@endsection