@extends('layouts.base')

@section('page-title', 'Trooper Audits')

@section('content')

    @include('pages.admin.troopers.tabs', compact('trooper'))

    <x-slim-container>

        <x-card>
            <x-message>
                Below are the recent changes made to this trooper's record within the last 30 days.
            </x-message>

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
                            <th>What</th>
                            <th>Field</th>
                            <th>Old</th>
                            <th>New</th>
                            <th>When</th>
                            <th>Who</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($changes as $change)
                            <tr>
                                <td>
                                    {{ $change->auditable_type }}
                                    <br />
                                    <span class="text-muted">
                                        {{ $change->auditable_label }}
                                    </span>
                                </td>
                                <td>{{ $change->field_name }}</td>
                                <td class="text-danger">
                                    {{ $change->old_value }}
                                </td>
                                <td class="text-success">
                                    {{ $change->new_value }}
                                </td>
                                <td class="text-nowrap">
                                    {{ $change->created_at->diffForHumans() }}
                                </td>
                                <td class="text-nowrap">
                                    {{ $change->trooper->name ?? 'System' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>

            </form>
        </x-card>

    </x-slim-container>

@endsection