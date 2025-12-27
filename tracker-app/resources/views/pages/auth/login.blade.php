@extends('layouts.base')

@section('page-title', 'Login')

@section('content')

    <x-slim-container class="mt-4">
        <x-card>

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

            <hr />

            <div class="row mb-3">
                <div class="col-6">
                    @if(config('services.xenforo.client_id'))
                        <a href="{{ route('auth.oauth-redirect', 'xenforo') }}"
                           class="btn btn-outline-secondary w-100 d-flex justify-content-center align-items-center gap-2">
                            <img src="https://xenforo.com/community/styles/default/xenforo/xenforo-favicon.png"
                                 alt="XenForo Logo"
                                 style="width: 18px; height: 18px;"
                                 class="me-3">
                            <span>Login with {{ config('services.xenforo.name') }}</span>
                        </a>
                    @endif
                </div>
                <div class="col-6">
                    @if(config('services.google.client_id'))
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
            </div>

        </x-card>
    </x-slim-container>

@endsection