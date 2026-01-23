@extends('layouts.email')

@section('message')
    <p>
        Esteemed Moderators,
    </p>

    <p>
        Another hopeful recruit has dared to submit themselves to the
        <b>merciless gears of Imperial administration</b>.
        Against all odds, their registration has successfully landed in your queue—
        which means it's time for you to perform your sacred duty:
        <i>deciding whether this brave soul is worthy of joining our ranks</i>.
    </p>

    <p>
        When your schedule allows (preferably before the next galactic cycle),
        you may proceed to the official
        <a href="{{ route('admin.troopers.approvals') }}">Approval Chamber</a>.
        There you will find this trooper's credentials awaiting your expert scrutiny.
    </p>

    <p>
        The Empire thanks you for your continued vigilance, paperwork endurance,
        and unwavering commitment to maintaining the façade of efficiency.
    </p>

    <p style="margin-top:20px; font-weight:bold; color:#333;">
        - Imperial Administration, {{ config('app.name') }}
    </p>
@endsection