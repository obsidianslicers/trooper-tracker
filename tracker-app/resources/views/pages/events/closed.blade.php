@extends('layouts.base')

@section('page-title', 'Completed Events')

@section('content')

    <div x-data="Events.Search.eventSelector()">

        @include('pages.events.inc.event-filters', compact('costume_organizations'))

        <x-table class="caption-top">
            <caption>
                Closed event history, newest first.
            </caption>
            <thead>
                <tr>
                    <th style="width: 36px;"></th>
                    <th></th>
                    <th></th>
                    <th scope="col"
                        class="text-nowrap text-center"
                        colspan="2">
                        -- Troopers --
                    </th>
                    <th scope="col"
                        class="text-nowrap text-center"
                        colspan="2">
                        -- Donations --
                    </th>
                </tr>
                <tr>
                    <th style="width: 36px;"></th>
                    <th>
                        Name
                    </th>
                    <th>
                        Shifts
                    </th>
                    <th scope="col"
                        class="text-center">
                        Unique
                    </th>
                    <th scope="col"
                        class="text-center">
                        Total
                    </th>
                    <th scope="col"
                        class="text-center">
                        Direct
                    </th>
                    <th scope="col"
                        class="text-center">
                        Indirect
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($events as $event)
                    <tr x-show="matches($el)"
                        data-event-name="{{ $event->name }}"
                        data-event-hosting-organization-id="{{ $event->organization_id  }}">
                        <td>
                            <x-logo :storage_path="$event->organization->image_path_sm"
                                    :default_path="'img/icons/organization-32x32.png'"
                                    :width="32"
                                    :height="32" />
                        </td>
                        <td>
                            <a href="{{ route('events.display', compact('event')) }}">
                                {{ $event->name }}
                            </a>
                            <span class="text-muted">
                                {{  $event->organization->name }}
                            </span>
                            <div class="d-none">
                                @foreach($event->organizations as $organization)
                                    @if($organization->pivot->can_attend)
                                        <span data-event-costume-organization-id="{{ $organization->id }}">
                                            {{ $organization->name }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </td>
                        <td class="text-end">
                            {{ $event->event_shifts_count }}
                        </td>
                        <td class="text-end">
                            <x-number-format :value="$event->unique_trooper_count" />
                        </td>
                        <td class="text-end">
                            <x-number-format :value="$event->total_trooper_count" />
                        </td>
                        <td class="text-end">
                            <x-number-format :value="$event->charity_direct_funds" />
                        </td>
                        <td class="text-end">
                            <x-number-format :value="$event->charity_indirect_funds" />
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="7">
                        No closed events found.
                    </x-table-empty>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2">
                        <x-number-format :value="$events->total()" />
                        <span class="text-muted">
                            Closed Events
                        </span>
                        <br />
                        <span class="small text-muted">
                            * Summary totals are for the events shown on this page (not filtered).
                        </span>
                    </th>
                    <th class="text-end">
                        <x-number-format :value="$events->sum('event_shifts_count')" />
                    </th>
                    <th class="text-end">
                        <x-number-format :value="$events->sum('unique_trooper_count')" />
                    </th>
                    <th class="text-end">
                        <x-number-format :value="$events->sum('total_trooper_count')" />
                    </th>
                    <th class="text-end">
                        <x-number-format :value="$events->sum('charity_direct_funds')" />
                    </th>
                    <th class="text-end">
                        <x-number-format :value="$events->sum('charity_indirect_funds')" />
                    </th>
                </tr>
            </tfoot>

        </x-table>

        {{ $events->links() }}

    </div>

@endsection
