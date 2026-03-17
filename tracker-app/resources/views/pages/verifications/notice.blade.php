@extends('layouts.base')

@section('page-title', 'Verify Your Email Address')

@section('content')
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 col-lg-5 text-center">

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h3 class="card-title mb-3">Check Your Inbox</h3>

                    <p class="text-muted">
                        Welcome to the {{ config('app.name') }}! We've sent a verification link to
                        your email address. Please click that link to finalize your registration, as
                        you await approval.
                    </p>

                    @if (session('status') == 'invalid')
                        <x-message type="danger">
                            The verification link has expired or is invalid. Please request a new one below.
                        </x-message>
                    @endif

                    <hr class="my-4">

                    <div class="d-grid gap-2">
                        <form method="POST"
                              novalidate="novalidate">
                            @csrf
                            <button type="submit"
                                    class="btn btn-primary w-100">
                                Resend Verification Email
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-footer text-muted small">
                    Mission status: Awaiting email confirmation.
                </div>
            </div>
        </div>
    </div>
@endsection