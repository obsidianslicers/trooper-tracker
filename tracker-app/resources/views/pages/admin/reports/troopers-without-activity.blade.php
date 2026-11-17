@extends('layouts.base')

@section('page-title', __('admin.Troopers Without Activity'))

@section('content')

    <x-table class="caption-top">
        <caption>
            Active Troopers who have not {{ \App\Enums\EventTrooperStatus::ATTENDED->name }}
            an event since {{ $lookback->localFormat('M d, Y') }}.
        </caption>
        <thead>
            <tr>
                <th scope="col">Trooper</th>
                <th scope="col">Last Seen</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($troopers as $trooper)
                <tr>
                    <td>
                        <a href="{{ route('admin.troopers.profile', $trooper) }}">
                            {{ $trooper->display_name }}
                        </a>
                    </td>
                    <td>
                        {{ $trooper->last_active_at?->localFormat('M d, Y') ?? 'Never' }}
                    </td>
                </tr>
            @empty
                <x-table-empty :colspan="2">
                    No troopers found, all present and accounted for.
                </x-table-empty>
            @endforelse
        </tbody>
    </x-table>

@endsection