@extends('layouts.base')

@section('page-title', 'Visitor Access Expired')

@section('content')
    <x-slim-container class="mt-4">
        <x-card>
            <h2 class="h4 mb-3">Your visitor access has expired</h2>

            <x-message type="warning"
                       icon="fa-solid fa-clock">
                Your 6-month visitor access window ended on
                <strong>{{ $trooper->visitor_expires_at->format('F j, Y') }}</strong>.
            </x-message>

            <p class="mt-3">
                To continue using {{ config('app.name') }}, submit a renewal request below.
                Command Staff will review your request and restore your access if approved.
            </p>

            <form action="{{ route('account.visitor-renew-submit') }}"
                  method="POST">
                @csrf
                <x-submit-container>
                    <x-submit-button>
                        Request Renewal
                    </x-submit-button>
                </x-submit-container>
            </form>
        </x-card>
    </x-slim-container>
@endsection
