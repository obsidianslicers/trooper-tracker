@extends('layouts.email')

@section('message')
<p>
    Esteemed Parent or Guardian,
</p>

<p>
    One of your young recruits has attempted to enlist with the
    <b>{{ config('app.name') }}</b>. Yes — they willingly filled out a form.
    We were impressed too.
</p>

<p>
    What does this mean for you?
</p>

<ul>
    <li>No action is required from you at this time.</li>
    <li>You're simply being informed that your youngling is eager to serve.</li>
    <li>You'll receive updates if anything requires your attention.</li>
</ul>

<p>
    As their parent or guardian, you'll be expected to sign up for events alongside them.
    Minors don't troop solo — even the Empire has limits. You'll receive the same event
    information they do, so you can coordinate, supervise, and make sure they're not
    accidentally volunteering for anything involving excessive
    glitter, heat, or Jawa-related chaos.
</p>

<p>
    If this registration was unexpected, accidental, or the result of a sibling
    prank, feel free to reach out and we'll sort it out faster than a stormtrooper
    misses a target.
</p>

@include('emails.inc.signature')
@endsection