@extends('layouts.base')

@section('page-title', 'Attached Costumes')

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
                                  :value="$trooper->display_name" />
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
                            <th>Attached Costume</th>
                            <th>Organizations</th>
                        </tr>
                    </thead>
                    @foreach ($costumes as $costume)
                        <tr>
                            <td class="text-nowrap">
                                {{ $costume->name }}
                            </td>
                            <td>
                                {{ $costume->costume_organizations }}
                            </td>
                        </tr>
                    @endforeach
                </x-table>

            </form>
        </x-card>

        <x-card>
            <h5 class="mb-3">Costume approvals by club</h5>

            <x-table>
                <thead>
                    <tr>
                        <th>Costume</th>
                        <th>Club</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                @forelse ($trooper_costumes as $trooper_costume)
                    <tr>
                        <td>{{ $trooper_costume->organization_costume?->costume?->name }}</td>
                        <td>{{ $trooper_costume->organization_costume?->organization?->name }}</td>
                        <td class="text-nowrap">
                            @if ($trooper_costume->isPending())
                                <span class="badge text-bg-warning">Pending review</span>
                            @elseif ($trooper_costume->synchronized_at !== null)
                                <span class="badge text-bg-success">Club records</span>
                            @else
                                <span class="badge text-bg-success">Approved</span>
                                @if ($trooper_costume->approved_by)
                                    <span class="text-muted small">by {{ $trooper_costume->approved_by->display_name }}</span>
                                @endif
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            @if ($trooper_costume->can_review)
                                @include('pages.admin.troopers.partials.costume-review-buttons', compact('trooper_costume'))
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="4">
                        No costumes attached.
                    </x-table-empty>
                @endforelse
            </x-table>
        </x-card>

    </x-slim-container>

@endsection