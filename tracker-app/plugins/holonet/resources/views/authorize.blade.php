@extends('layouts.base')

@section('page-title', 'Link a Tracker')

@section('content')
    <x-slim-container class="mt-4">
        <x-card>
            @if($problem)
                <h2 class="h4 mb-3">Link a Tracker</h2>
                <p class="mb-0">{{ $problem }}</p>
            @else
                <h2 class="h4 mb-3">
                    Let {{ $link_request->tracker->name }} see your troop history?
                </h2>
                <p>
                    You're signed in to {{ $tracker_name }} as
                    <strong>{{ Auth::user()->display_name }}</strong>.
                    {{ $link_request->tracker->name }}'s tracker
                    (<code>{{ $link_request->tracker->url }}</code>) asked to show your troops
                    from here on your service record there.
                </p>
                <p class="mb-1">It will see, for each troop you attended here:</p>
                <ul>
                    <li>the event name, type and venue</li>
                    <li>the shift's date and times</li>
                    <li>the costume you wore, and the clubs that got credit</li>
                </ul>
                <p class="small text-muted">
                    It won't see your email, phone, legal name, or anything about other troopers.
                    You can remove its access at any time on the HoloNet page.
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
                        Allow
                    </button>
                    <button type="submit"
                            name="allow"
                            value="0"
                            class="btn btn-outline-secondary">
                        Cancel
                    </button>
                </form>
            @endif
        </x-card>
    </x-slim-container>
@endsection
