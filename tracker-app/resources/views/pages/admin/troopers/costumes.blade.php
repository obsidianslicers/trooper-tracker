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
                        </tr>
                    </thead>
                    @foreach ($trooper_costumes as $trooper_costume)
                        <tr>
                            <td class="text-nowrap">
                                {{ $trooper_costume->organization_costume->costume->name }}
                            </td>
                        </tr>
                    @endforeach
                </x-table>

            </form>
        </x-card>

    </x-slim-container>

@endsection