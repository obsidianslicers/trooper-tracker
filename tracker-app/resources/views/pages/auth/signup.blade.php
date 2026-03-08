@extends('layouts.base')

@section('page-title', 'Sign Up')

@section('content')

    <x-slim-container class="mt-4">

        <x-card>

            @if(TroopTracker::isXenforoOAuthRequired())
                <x-message type="info"
                           icon="fa-circle-info">
                    You must use Xenforo to sign up.
                </x-message>

                @if(!TroopTracker::isXenforoOAuthConfigured())
                    <x-message type="danger"
                               icon="fa-circle-xmark">
                        XenForo OAuth is required, but it is not configured. Please contact an administrator.
                    </x-message>
                @endif
            @endif

            <div class="row mb-3">
                @if(TroopTracker::isEmailPasswordAuthEnabled())
                    <div class="col-12 mb-3">
                        <a href="{{ route('auth.signup-email') }}"
                           class="btn btn-outline-secondary w-100 d-flex justify-content-center align-items-center gap-2">
                            <i class="fa fw fa-envelope me-3"></i>
                            <span>Sign Up with Email</span>
                        </a>
                    </div>
                @endif

                @if(TroopTracker::isXenforoOAuthConfigured())
                    <div class="col-12 mb-3">
                        <a href="{{ route('auth.oauth-redirect', 'xenforo') }}"
                           class="btn btn-outline-secondary w-100 d-flex justify-content-center align-items-center gap-2">
                            <img src="https://xenforo.com/community/styles/default/xenforo/xenforo-favicon.png"
                                 alt="XenForo Logo"
                                 style="width: 18px; height: 18px;"
                                 class="me-3">
                            <span>Sign Up with {{ config('services.xenforo.name') }}</span>
                        </a>
                    </div>
                @endif

                @if(TroopTracker::isGoogleOAuthEnabled())
                    <div class="col-12 mb-3">
                        <a href="{{ route('auth.oauth-redirect', 'google') }}"
                           class="btn btn-outline-secondary w-100 d-flex justify-content-center align-items-center gap-2">
                            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
                                 alt="Google Logo"
                                 style="width: 18px; height: 18px;"
                                 class="me-3">
                            <span>Sign Up with Google</span>
                        </a>
                    </div>
                @endif
            </div>

        </x-card>
    </x-slim-container>

@endsection