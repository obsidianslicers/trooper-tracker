@extends('layouts.base')

@section('page-title', 'Recently Cancelled Events')

@section('content')

    <div x-data="Events.Search.eventSelector()">

        @include('pages.events.inc.event-filters', compact('costume_organizations'))


        <x-table class="caption-top">
            <caption>
                {{ __('events.Summary of Cancelled Events over the last :days days.', ['days' => $lookback]) }}
            </caption>
            <thead>
                <tr>
                    <th style="width: 36px;"></th>
                    <th></th>
                    <th></th>
                    <th scope="col"
                        class="text-nowrap text-center"
                        colspan="2">
                        {{ __('events.-- Donations --') }}
                    </th>
                </tr>
                <tr>
                    <th style="width: 36px;"></th>
                    <th>
                        {{ __('events.Name') }}
                    </th>
                    <th>
                        {{ __('events.Shifts') }}
                    </th>
                    <th scope="col"
                        class="text-center">
                        {{ __('events.Direct') }}
                    </th>
                    <th scope="col"
                        class="text-center">
                        {{ __('events.Indirect') }}
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
                            <x-number-format :value="$event->charity_direct_funds" decimals="cents" />
                        </td>
                        <td class="text-end">
                            <x-number-format :value="$event->charity_indirect_funds" decimals="cents" />
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="7">
                        {{ __('events.There are not any events that have been cancelled in the last :days days.', ['days' => $lookback]) }}
                    </x-table-empty>
                @endforelse
            </tbody>
            @if($events->isNotEmpty())
                <tfoot>
                    <tr>
                        <th colspan="2">
                            <x-number-format :value="$events->count()" />
                            <span class="text-muted">
                                {{ __('events.Events in Total') }}
                            </span>
                            <br />
                            <span class="small text-muted">
                                {{ __('events.* Totals only all include events that have been closed within the lookback period (not filtered).') }}
                            </span>
                        </th>
                        <th class="text-end">
                            <x-number-format :value="$events->sum('event_shifts_count')" />
                        </th>
                        <th class="text-end">
                            <x-number-format :value="$events->sum('charity_direct_funds')" decimals="cents" />
                        </th>
                        <th class="text-end">
                            <x-number-format :value="$events->sum('charity_indirect_funds')" decimals="cents" />
                        </th>
                    </tr>
                </tfoot>
            @endif

        </x-table>

    </div>

@endsection