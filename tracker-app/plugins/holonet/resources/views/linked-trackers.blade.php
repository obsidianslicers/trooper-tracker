@extends('layouts.base')

@section('page-title', 'HoloNet')

@section('content')
    <x-slim-container class="mt-4">
        @if(!$is_listed)
            <div class="alert alert-warning">
                {!! __("holonet::account.This tracker (:url) isn't on the HoloNet list, so other trackers won't accept links from it. Check :app_url matches the address in the list, or add this tracker to :file.", [
                    'url' => '<code>'.e($tracker_url).'</code>',
                    'app_url' => '<code>APP_URL</code>',
                    'file' => '<code>plugins/holonet/trackers.json</code>',
                ]) !!}
            </div>
        @endif
        <x-card :label="__('holonet::account.Your linked trackers')">
            <p class="small text-muted">
                {{ __("holonet::account.Troops from trackers you link show on your service record here, and count in your all-time totals there. They don't count toward :tracker's reports, awards or club credit.", ['tracker' => $tracker_name]) }}
            </p>

            @forelse($links as $link)
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-top py-2">
                    <div>
                        <div class="fw-bold">{{ $link->tracker_name }}</div>
                        <div class="small text-muted">
                            @if($link->remote_display_name)
                                {{ __('holonet::account.As :name', ['name' => $link->remote_display_name]) }} &middot;
                            @endif
                            {{ trans_choice('holonet::account.:count troop|:count troops', $link->troops_count, ['count' => format_number($link->troops_count)]) }}
                            @if($link->refreshed_at)
                                &middot; {{ __('holonet::account.updated') }} <x-date-format :value="$link->refreshed_at" />
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
                                        class="btn btn-sm btn-outline-secondary btn-secondary-action">{{ __('holonet::account.Refresh') }}</button>
                            </form>
                        @endif
                        <form method="POST"
                              action="{{ route('plugins.holonet.links.remove', ['link' => $link]) }}"
                              data-confirm="{{ __('holonet::account.Unlink this tracker and remove its troops from your service record?') }}"
                              onsubmit="return confirm(this.dataset.confirm);">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="btn btn-outline-danger btn-sm">{{ __('holonet::account.Unlink') }}</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="mb-0 border-top pt-2">{{ __('holonet::account.You haven\'t linked any trackers yet.') }}</p>
            @endforelse
        </x-card>

        <x-card :label="__('holonet::account.Link another tracker')">
            @if(!$can_link)
                <p class="mb-0">{{ __('holonet::account.Troopers under 18 can\'t link trackers.') }}</p>
            @elseif($available_trackers->isEmpty())
                <p class="mb-0">{{ __('holonet::account.There are no other supported trackers to link yet.') }}</p>
            @else
                <p class="small text-muted">
                    {{ __('holonet::account.You\'ll sign in to that tracker and confirm the link there.') }}
                </p>
                <form method="POST"
                      action="{{ route('plugins.holonet.links.start') }}"
                      class="d-flex flex-wrap gap-2">
                    @csrf
                    <select name="tracker_url"
                            class="form-select w-auto"
                            aria-label="{{ __('holonet::account.Tracker') }}">
                        @foreach($available_trackers as $tracker)
                            <option value="{{ $tracker->url }}">{{ $tracker->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit"
                            class="btn btn-primary">{{ __('holonet::account.Link') }}</button>
                </form>
            @endif
        </x-card>

        <x-card :label="__('holonet::account.Trackers that can see your history here')">
            @if(!$shares_history)
                <p class="small text-muted">
                    {{ __("holonet::account.:tracker doesn't share troop history with other trackers right now.", ['tracker' => $tracker_name]) }}
                </p>
            @endif
            @forelse($grants as $grant)
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-top py-2">
                    <div>
                        <div class="fw-bold">{{ $grant->tracker_name }}</div>
                        <div class="small text-muted">
                            {{ __('holonet::account.Allowed') }} <x-date-format :value="$grant->created_at" />
                            @if($grant->last_used_at)
                                &middot; {{ __('holonet::account.last read') }} <x-date-format :value="$grant->last_used_at" />
                            @endif
                        </div>
                    </div>
                    <form method="POST"
                          action="{{ route('plugins.holonet.grants.revoke', ['grant' => $grant]) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="btn btn-outline-danger btn-sm">{{ __('holonet::account.Remove access') }}</button>
                    </form>
                </div>
            @empty
                <p class="mb-0 border-top pt-2">{{ __('holonet::account.No other tracker can see your troop history here.') }}</p>
            @endforelse
        </x-card>
    </x-slim-container>
@endsection
