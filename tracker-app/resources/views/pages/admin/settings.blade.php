@extends('layouts.base')

@section('page-title', 'Site Settings')

@section('content')

<x-slim-container>

    <x-card>
        <form method="POST" action="{{ route('admin.settings') }}" novalidate>
            @csrf

            <x-input-container>
                <x-label>
                    Close Troop Tracker
                </x-label>
                <div class="form-check form-switch">
                    <input class="form-check-input"
                           type="checkbox"
                           id="site_closed"
                           name="site_closed"
                           value="1"
                           {{ $site_closed ? 'checked' : '' }}>
                    <label class="form-check-label" for="site_closed">
                        When enabled, only moderators and administrators can access the site.
                    </label>
                </div>
            </x-input-container>

            <x-input-container>
                <x-label>
                    Closed Message
                </x-label>
                <textarea class="form-control"
                          name="site_message"
                          id="site_message"
                          rows="3"
                          maxlength="500"
                          placeholder="Message shown to troopers when the site is closed...">{{ old('site_message', $site_message) }}</textarea>
                <small class="text-muted">Displayed to troopers attempting to access the site and on the mobile app.</small>
            </x-input-container>

            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>

        </form>
    </x-card>

</x-slim-container>

@endsection
