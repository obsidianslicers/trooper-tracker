@extends('layouts.base')

@section('page-title', 'Link a Tracker')

@section('content')
    <x-slim-container class="mt-4">
        <x-card>
            @if($problem)
                <h2 class="h4 mb-3">{{ __('holonet::account.Link a Tracker') }}</h2>
                <p class="mb-0">{{ $problem }}</p>
            @else
                <h2 class="h4 mb-3">
                    {{ __('holonet::account.Let :tracker see your troop history?', ['tracker' => $link_request->tracker->name]) }}
                </h2>
                <p>
                    {!! __("holonet::account.You're signed in to :here as :name. :tracker's tracker (:url) asked to show your troops from here on your service record there.", [
                        'here' => e($tracker_name),
                        'name' => '<strong>'.e(Auth::user()->display_name).'</strong>',
                        'tracker' => e($link_request->tracker->name),
                        'url' => '<code>'.e($link_request->tracker->url).'</code>',
                    ]) !!}
                </p>
                <p class="mb-1">{{ __('holonet::account.It will see, for each troop you attended here:') }}</p>
                <ul>
                    <li>{{ __('holonet::account.the event name, type and venue') }}</li>
                    <li>{{ __('holonet::account.the shift\'s date and times') }}</li>
                    <li>{{ __('holonet::account.the costume you wore, and the clubs that got credit') }}</li>
                </ul>
                <p class="small text-muted">
                    {{ __('holonet::account.It won\'t see your email, phone, legal name, or anything about other troopers. You can remove its access at any time on the HoloNet page.') }}
                </p>
                <form method="POST"
                      action="{{ route('plugins.holonet.authorize.submit') }}"
                      class="d-flex gap-2">
                    @csrf
                    @foreach($link_request->parameters() as $name => $value)
                        <input type="hidden"
                               name="{{ $name }}"
                               value="{{ $value }}">
                    @endforeach
                    <button type="submit"
                            name="allow"
                            value="1"
                            class="btn btn-primary">
                        {{ __('holonet::account.Allow') }}
                    </button>
                    <button type="submit"
                            name="allow"
                            value="0"
                            class="btn btn-outline-secondary">
                        {{ __('holonet::account.Cancel') }}
                    </button>
                </form>
            @endif
        </x-card>
    </x-slim-container>
@endsection
