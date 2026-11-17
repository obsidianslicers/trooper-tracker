@extends('layouts.base')

@section('page-title', __('admin.Status Change Log'))

@section('content')

    <x-table class="caption-top">
        <caption>
            {{ __('admin.Troopers who :attended an event where their status was updated by someone other than the trooper in action for the last :days days.', ['attended' => \App\Enums\EventTrooperStatus::ATTENDED->label(), 'days' => $lookback]) }}
        </caption>
        <thead>
            <tr>
                <th scope="col">{{ __('admin.Date/Time') }}</th>
                <th scope="col">{{ __('admin.Event') }}</th>
                <th scope="col">{{ __('admin.Shift') }}</th>
                <th scope="col">{{ __('admin.Trooper') }}</th>
                <th scope="col">{{ __('admin.Updated By') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($changes as $change)
                <tr>
                    <td class="text-nowrap">
                        {{ $change->created_at->localFormat('D - M d, Y g:ia') }}
                    </td>
                    <td>
                        <a href="{{ route('admin.events.update', $change->auditable->event_shift->event) }}">
                            {{ $change->auditable->event_shift->event->name }}
                        </a>
                    </td>
                    <td>
                        {{ $change->auditable->event_shift->time_display }}
                    </td>
                    <td>
                        <a href="{{ route('admin.troopers.profile', ['trooper' => $change->auditable->trooper, 'tab' => 'changes']) }}">
                            {{ $change->auditable->trooper->display_name }}
                        </a>
                    </td>
                    <td>
                        {{ $change->trooper->display_name ?? 'System' }}
                    </td>
                </tr>
            @empty
                <x-table-empty :colspan="5">
                    {{ __('admin.No status changes found that were updated by anyone but the trooper.') }}
                </x-table-empty>
            @endforelse
        </tbody>
    </x-table>

@endsection