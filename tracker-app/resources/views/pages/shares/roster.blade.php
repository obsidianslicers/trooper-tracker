@extends('layouts.base')

@section('page-title', 'Shared Event Roster')

@section('content')
    @if ($share->is_viewable)
        <x-message :type="'info'">
            <h6 class="fw-bold">
                {{ __('shares.Note on Roster Flexibility:') }}
            </h6>
            <p>
                {!! __('shares.The following list of attendees and costumes for :event is our current plan, please consider it a "<i>working draft.</i>" Our members are volunteers with family and professional obligations that can occasionally result in last-minute changes to personnel and/or specific costume characters. We will do our best to notify you of any major shifts in our attendance.', ['event' => '<b>'.e($event->name).'</b>']) !!}
            </p>
        </x-message>
        @foreach($event->event_shifts as $event_shift)
            @include('pages.shares.inc.shift-header', compact('event_shift'))
            <div class="ms-5">
                <h5>{{ __('shares.Troopers') }}</h5>
                <x-table>
                    <thead>
                        <tr>
                            <th>
                                {{ __('shares.Trooper') }}
                            </th>
                            <th>
                                {{ __('shares.Costume') }}
                            </th>
                            <th>
                                {{ __('shares.Status') }}
                            </th>
                        </tr>
                    </thead>
                    @foreach($event_shift->event_troopers as $event_trooper)
                        <tr>
                            <td>
                                {{ $event_trooper->trooper->legal_name }}
                            </td>
                            <td>
                                @if($event_trooper->costume)
                                    <b>
                                        {{ $event_trooper->costume->name }}
                                    </b>
                                    <br />
                                    <x-planned-org-credit :organizations="$event_trooper->costume_organizations"
                                                           :planned="$event->status !== \App\Enums\EventStatus::CLOSED" />
                                @endif
                            </td>
                            <td>
                                <span class="{{ $event_trooper->status->color() }}">
                                    {{ $event_trooper->status->label() }}
                                    <span class="d-none d-md-inline">
                                        {!! $event_trooper->status->iconTag() !!}
                                    </span>
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </x-table>
                @if($event_shift->event_guests->isNotEmpty())
                    <hr />
                    <h5>{{ __('shares.Guests') }}</h5>
                    <x-table>
                        <thead>
                            <tr>
                                <th>
                                    {{ __('shares.Guest') }}
                                </th>
                                <th>
                                    {{ __('shares.Status') }}
                                </th>
                            </tr>
                        </thead>
                        @foreach($event_shift->event_guests as $event_guest)
                            <tr>
                                <td>
                                    {{ $event_guest->name }}
                                </td>
                                <td>
                                    <span class="{{ $event_guest->status->color() }}">
                                        {{ $event_guest->status->label() }}
                                        <span class="d-none d-md-inline">
                                            {!! $event_guest->status->iconTag() !!}
                                        </span>
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </x-table>
                @endif
            </div>
        @endforeach
    @else
        <x-message :type="'danger'">
            {{ __('shares.This link has expired or is no longer available. Please contact your :app event coordinator for more information.', ['app' => config('app.name')]) }}
        </x-message>
    @endif
@endsection