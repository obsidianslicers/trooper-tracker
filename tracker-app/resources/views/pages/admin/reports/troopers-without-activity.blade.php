@extends('layouts.base')

@section('page-title', 'Status Change Log')

@section('content')

    <x-table class="caption-top">
        <caption>
            Active Troopers who have not {{ \App\Enums\EventTrooperStatus::ATTENDED->name }}
            an event in the last 12 months.
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
                            {{ $trooper->name }}
                        </a>
                    </td>
                    <td>
                        {{ $trooper->last_active_at?->format('M d, Y') ?? 'Never' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        No troopers found, all present an accounted for.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

@endsection