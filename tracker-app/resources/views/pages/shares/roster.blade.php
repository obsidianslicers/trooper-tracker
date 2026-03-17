@extends('layouts.base')

@section('page-title', 'Shared Event Roster')

@section('content')
    @if ($share->is_viewable)
        @foreach($event->event_shifts as $event_shift)
            @include('pages.shares.inc.shift-header', compact('event_shift'))
            <div class="ms-5">
                <h5>Troopers</h5>
                <x-table>
                    <thead>
                        <tr>
                            <th>
                                Trooper
                            </th>
                            <th>
                                Costume
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
                                    <i class="small text-muted">
                                        {{ $event_trooper->costume_organizations }}
                                    </i>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-table>
                @if($event_shift->event_guests->isNotEmpty())
                    <hr />
                    <h5>Guests</h5>
                    <x-table>
                        <thead>
                            <tr>
                                <th>
                                    Guest
                                </th>
                            </tr>
                        </thead>
                        @foreach($event_shift->event_guests as $event_guest)
                            <tr>
                                <td>
                                    {{ $event_guest->name }}
                                </td>
                            </tr>
                        @endforeach
                    </x-table>
                @endif
            </div>
        @endforeach
    @else
        <x-message :type="'danger'">
            This link has expired or is no longer available. Please contact your {{ config('app.name') }} event
            coordinator for more information.
        </x-message>
    @endif
@endsection