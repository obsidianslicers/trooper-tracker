@extends('layouts.base')

@section('page-title', 'XenForo Account')

@section('content')
    <x-slim-container class="mt-4">
        <x-card>
            <h2 class="h4 mb-3">XenForo Account</h2>

            @if($xenforo_login)
                <p class="mb-3">
                    Your TroopTracker account is linked to XenForo
                    provider ID: <code>{{ $xenforo_login->provider_id }}</code>.
                </p>
            @else
                <p class="mb-3">
                    Your TroopTracker account is not yet linked to
                    a XenForo account.
                </p>
            @endif

            <a href="{{ route('auth.oauth-redirect', 'xenforo') }}"
               class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <img src="https://xenforo.com/community/styles/default/xenforo/xenforo-favicon.png"
                     alt="XenForo Logo"
                     style="width: 18px; height: 18px;"
                     class="me-2">
                <span>{{ $xenforo_login ? 'Relink XenForo Account' : 'Link XenForo Account' }}</span>
            </a>
        </x-card>
    </x-slim-container>
@endsection
