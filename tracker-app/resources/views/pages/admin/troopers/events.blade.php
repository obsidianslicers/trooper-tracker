@extends('layouts.base')

@section('page-title', 'Recent Events')

@section('content')

    @include('pages.admin.troopers.tabs', compact('trooper'))

    <x-slim-container>

        <x-card>

            <form method="POST"
                  novalidate="novalidate">
                @csrf

                <x-input-container>
                    <x-label>
                        Trooper:
                    </x-label>
                    <x-input-text :property="'trooper_name'"
                                  :disabled="true"
                                  :value="$trooper->name" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Role:
                    </x-label>
                    <x-input-text :property="'trooper_role'"
                                  :disabled="true"
                                  :value="to_title($trooper->membership_role->name)" />
                </x-input-container>

                <x-table>
                    <thead>
                        <tr>
                            <th>Organization</th>
                            <th colspan="2">Most Recent Event</th>
                        </tr>
                    </thead>
                    @foreach ($organization_events as $organization)
                        <tr>
                            <td class="text-nowrap">
                                {{ $organization->name }}
                            </td>
                            <td>
                                @if($organization->event_shift)
                                    {{  $organization->event_shift->event->name }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if($organization->event_shift)
                                    @php
                                        $shift_date = $organization->event_shift->shift_starts_at;
                                        $is_old = $shift_date->lt(now()->subYear());
                                    @endphp
                                    {{ $shift_date->format('M d, Y') }}
                                    @if($is_old)
                                        <i class="fa fa-fw fa-times text-danger"></i>
                                    @else
                                        <i class="fa fa-fw fa-check text-success"></i>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-table>

            </form>
        </x-card>

    </x-slim-container>

@endsection