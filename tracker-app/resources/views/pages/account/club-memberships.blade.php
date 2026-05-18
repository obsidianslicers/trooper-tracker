@extends('layouts.base')

@section('page-title', 'Club Memberships')

@section('content')

    @include('pages.account.tabs')

    <x-transmission-bar :id="'club-memberships'" />

    <x-slim-container>
        @if($available_clubs->isEmpty())
            <x-message type="info" icon="fa-solid fa-circle-info">
                You are already a member of all available clubs, or no clubs are available to join at this time.
            </x-message>
        @else
            <x-card>
                <p class="text-muted mb-3">
                    Select a club to request membership. A moderator will review your request.
                </p>

                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Club / Unit</th>
                            <th>Identifier</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($available_clubs as $org)
                            @include('pages.account.club-memberships-row', compact('org'))
                        @endforeach
                    </tbody>
                </table>
            </x-card>
        @endif
    </x-slim-container>

@endsection
