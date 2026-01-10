@extends('layouts.base')

@section('page-title', 'Registered Successfully')

@section('content')

    <x-slim-container class="mt-4">

        <x-message>
            Thank You for Registering!
            <br />
            <p>
                Congratulations - you've successfully navigated the perilous journey of filling out
                the registration form. That alone proves you've got the patience required to march with
                the <b>{{ config('app.name') }}</b>. Your application has been received and is
                now in the hands of our approval team. They'll review it, check your details, and make
                sure you're ready to join the ranks.
            </p>
        </x-message>

    </x-slim-container>

@endsection