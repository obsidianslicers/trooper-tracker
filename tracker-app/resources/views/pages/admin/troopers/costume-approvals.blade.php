@extends('layouts.base')

@section('page-title', 'Costume Approvals')

@section('content')

    <x-slim-container>

        <x-card>

            <p class="text-muted">
                Troopers added these costumes themselves. Approve a costume once and the
                trooper can use it for that club from then on.
            </p>

            <x-table>
                <thead>
                    <tr>
                        <th>Trooper</th>
                        <th>Costume</th>
                        <th>Club</th>
                        <th>Requested</th>
                        <th></th>
                    </tr>
                </thead>
                @forelse ($trooper_costumes as $trooper_costume)
                    <tr>
                        <td>
                            <a href="{{ route('admin.troopers.costumes', ['trooper' => $trooper_costume->trooper]) }}">
                                {{ $trooper_costume->trooper->display_name }}
                            </a>
                        </td>
                        <td>{{ $trooper_costume->organization_costume->costume->name }}</td>
                        <td>{{ $trooper_costume->organization_costume->organization->name }}</td>
                        <td class="text-nowrap">{{ $trooper_costume->created_at?->format('M j, Y') }}</td>
                        <td class="text-end text-nowrap">
                            @include('pages.admin.troopers.partials.costume-review-buttons', compact('trooper_costume'))
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="5">
                        No costumes are waiting for review.
                    </x-table-empty>
                @endforelse
            </x-table>

        </x-card>

    </x-slim-container>

@endsection
