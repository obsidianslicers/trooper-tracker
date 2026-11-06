@extends('layouts.base')

@section('page-title', 'HoloNet')

@section('content')
    <x-slim-container class="mt-4">
        <x-card label="Your linked trackers">
            <p class="small text-muted">
                Troops from trackers you link show on your service record here, and count in your
                all-time totals there. They don't count toward {{ $tracker_name }}'s reports,
                awards or club credit.
            </p>

            @forelse($links as $link)
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-top py-2">
                    <div>
                        <div class="fw-bold">{{ $link->tracker_name }}</div>
                        <div class="small text-muted">
                            @if($link->remote_display_name)
                                As {{ $link->remote_display_name }} &middot;
                            @endif
                            {{ $link->troops_count }} {{ Str::plural('troop', $link->troops_count) }}
                            @if($link->refreshed_at)
                                &middot; updated <x-date-format :value="$link->refreshed_at" />
                            @endif
                        </div>
                        @if($link->last_error)
                            <div class="small text-warning">{{ $link->last_error }}</div>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        @if($link->isActive())
                            <form method="POST"
                                  action="{{ route('plugins.holonet.links.refresh', ['link' => $link]) }}">
                                @csrf
                                <button type="submit"
                                        class="btn btn-outline-secondary btn-sm">Refresh</button>
                            </form>
                        @endif
                        <form method="POST"
                              action="{{ route('plugins.holonet.links.remove', ['link' => $link]) }}"
                              onsubmit="return confirm('Unlink this tracker and remove its troops from your service record?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="btn btn-outline-danger btn-sm">Unlink</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="mb-0 border-top pt-2">You haven't linked any trackers yet.</p>
            @endforelse
        </x-card>

        <x-card label="Link another tracker">
            @if(!$can_link)
                <p class="mb-0">Troopers under 18 can't link trackers.</p>
            @elseif($available_trackers->isEmpty())
                <p class="mb-0">There are no other supported trackers to link yet.</p>
            @else
                <p class="small text-muted">
                    You'll sign in to that tracker and confirm the link there.
                </p>
                <form method="POST"
                      action="{{ route('plugins.holonet.links.start') }}"
                      class="d-flex flex-wrap gap-2">
                    @csrf
                    <select name="tracker_url"
                            class="form-select w-auto"
                            aria-label="Tracker">
                        @foreach($available_trackers as $tracker)
                            <option value="{{ $tracker->url }}">{{ $tracker->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit"
                            class="btn btn-primary">Link</button>
                </form>
            @endif
        </x-card>

        <x-card label="Trackers that can see your history here">
            @if(!$shares_history)
                <p class="small text-muted">
                    {{ $tracker_name }} doesn't share troop history with other trackers right now.
                </p>
            @endif
            @forelse($grants as $grant)
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-top py-2">
                    <div>
                        <div class="fw-bold">{{ $grant->tracker_name }}</div>
                        <div class="small text-muted">
                            Allowed <x-date-format :value="$grant->created_at" />
                            @if($grant->last_used_at)
                                &middot; last read <x-date-format :value="$grant->last_used_at" />
                            @endif
                        </div>
                    </div>
                    <form method="POST"
                          action="{{ route('plugins.holonet.grants.revoke', ['grant' => $grant]) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="btn btn-outline-danger btn-sm">Remove access</button>
                    </form>
                </div>
            @empty
                <p class="mb-0 border-top pt-2">No other tracker can see your troop history here.</p>
            @endforelse
        </x-card>
    </x-slim-container>
@endsection
