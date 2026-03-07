@extends('layouts.base')

@section('page-title', 'Login')

@section('content')

    <x-slim-container class="mt-4">
        <x-card>

            @if(TroopTracker::isXenforoOAuthRequired())
                <x-message type="info" icon="fa-circle-info">
                You must use Xenforo to login.
                </x-message>

                @if(!TroopTracker::isXenforoOAuthConfigured())
                    <x-message type="danger" icon="fa-circle-xmark">
                        XenForo OAuth is required, but it is not configured. Please contact an administrator.
                    </x-message>
                @endif
            @endif

            @if(TroopTracker::isEmailPasswordAuthEnabled())
                <form method="POST"
                      action="{{ route('auth.login') }}"
                      novalidate="novalidate">
                    @csrf

                    <x-input-container>
                        <x-label>
                            Email:
                        </x-label>
                        <x-input-text autofocus
                                      :property="'email'" />
                    </x-input-container>

                    <x-input-container>
                        <x-label>
                            Password:
                        </x-label>
                        <x-input-password :property="'password'" />
                    </x-input-container>

                    <x-input-container>
                        <x-input-checkbox :property="'remember_me'"
                                          :label="'Keep me logged in'"
                                          :value="'Y'" />
                    </x-input-container>

                    <x-submit-container>
                        <x-submit-button>
                            Login
                        </x-submit-button>
                    </x-submit-container>
                </form>
            @endif

            @if(TroopTracker::isXenforoOAuthConfigured() || TroopTracker::isGoogleOAuthEnabled())
                <hr />

                <div class="d-grid gap-2 mb-3">
                    @if(TroopTracker::isXenforoOAuthConfigured())
                        <a href="{{ route('auth.oauth-redirect', 'xenforo') }}"
                           class="btn btn-outline-secondary w-100 d-flex justify-content-center align-items-center gap-2">
                            <img src="https://xenforo.com/community/styles/default/xenforo/xenforo-favicon.png"
                                 alt="XenForo Logo"
                                 style="width: 18px; height: 18px;"
                                 class="me-3">
                            <span>Login with {{ config('services.xenforo.name') }}</span>
                        </a>
                    @endif

                    @if(TroopTracker::isGoogleOAuthEnabled())
                        <a href="{{ route('auth.oauth-redirect', 'google') }}"
                           class="btn btn-outline-secondary w-100 d-flex justify-content-center align-items-center gap-2">
                            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
                                 alt="Google Logo"
                                 style="width: 18px; height: 18px;"
                                 class="me-3">
                            <span>Login with Google</span>
                        </a>
                    @endif
                </div>
            @endif

        </x-card>
    </x-slim-container>

@endsection