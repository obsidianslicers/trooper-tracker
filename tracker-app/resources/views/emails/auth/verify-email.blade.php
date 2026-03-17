@extends('layouts.email')

@section('message')
    <p>
        Recruit.
    </p>

    <p>
        Our systems have detected a new transmission claiming to be from you. Before we let you anywhere
        near the ranks of <b>{{ config('app.name') }}</b>, Imperial protocol demands one thing:
        verify that this email channel actually belongs to you.
    </p>

    <p>
        In less dramatic terms: click the verification link we've sent to this address. No click, no access.
        The Emperor is strangely firm about this.
    </p>

    <p>
        Once your email is verified, your registration will move through the approval queue.
        If everything checks out, you'll be cleared for duty and notified faster than a stormtrooper
        can miss a target.
    </p>

    <p>
        Until then, stand by. Do not reply to this message with "Did you get my application?"
        We did. That's why you're reading this.
    </p>

    <p>
        Proceed to verification, Recruit. The Empire does not appreciate hesitation.
    </p>


    <table width="100%"
           cellpadding="0"
           cellspacing="0">
        <tbody>
            <tr>
                <td class="content-block">
                    <a href="{{ $url }}"
                       class="btn-success">
                        Verify Email Address
                    </a>
                </td>
            </tr>
        </tbody>
    </table>

    @include('emails.inc.signature')

@endsection