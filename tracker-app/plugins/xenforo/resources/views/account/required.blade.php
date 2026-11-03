@extends('layouts.base')

@section('page-title', 'Link XenForo Account')

@section('content')
    <x-slim-container class="mt-4">
        <x-card>
            <h2 class="h4 mb-3">XenForo account required</h2>

            <p class="mb-3">
                To use Troop Tracker, you must link your forum
                account.
            </p>

            <p class="mb-3">
                Click the button below to sign in with XenForo and
                connect your forum account to your Troop Tracker profile.
            </p>

            <a href="{{ route('auth.oauth-redirect', 'xenforo') }}"
               class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <img src="https://xenforo.com/community/styles/default/xenforo/xenforo-favicon.png"
                     alt="XenForo Logo"
                     style="width: 18px; height: 18px;"
                     class="me-2">
                <span>Link XenForo Account</span>
            </a>
        </x-card>
    </x-slim-container>
@endsection
