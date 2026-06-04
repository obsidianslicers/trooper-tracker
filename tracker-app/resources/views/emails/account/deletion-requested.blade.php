@extends('layouts.email')

@section('message')
    <p>
        Trooper.
    </p>

    <p>
        We have received your request to permanently delete your
        <b>{{ config('app.name') }}</b> account and associated personal data.
    </p>

    <p>
        Your account will be permanently anonymized on <b>{{ $deletion_date }}</b>.
        Until then, you may log in and cancel this request at any time.
    </p>

    <p>
        <b>What will be removed:</b> your name, email address, phone number,
        login credentials, and linked OAuth accounts.
    </p>

    <p>
        <b>What will be retained:</b> anonymized event participation records and
        achievement history, as permitted under legitimate organizational interest.
        These records will no longer be linked to your identity.
    </p>

    <p>
        If you did not request this, log in immediately and cancel the deletion
        from your account settings.
    </p>

    <table width="100%"
           cellpadding="0"
           cellspacing="0">
        <tbody>
            <tr>
                <td class="content-block">
                    <a href="{{ route('auth.login') }}"
                       class="btn-warning">
                        Log In to Cancel
                    </a>
                </td>
            </tr>
        </tbody>
    </table>

    @include('emails.inc.signature')

@endsection
