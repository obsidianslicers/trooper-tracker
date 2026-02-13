@extends('layouts.base')

@section('page-title', 'Troopers Awarded')

@section('content')

    @include('pages.admin.awards.tabs', compact('award'))

    <x-slim-container>

        <x-card>
            <form method="POST"
                  novalidate="novalidate">
                @csrf

                <x-input-container>
                    <x-label>
                        Award:
                    </x-label>
                    <x-input-text :property="'award_name'"
                                  :disabled="true"
                                  :value="$award->name" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Organization:
                    </x-label>
                    <x-input-text :property="'organization_name'"
                                  :disabled="true"
                                  :value="$award->organization->name" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Frequency:
                    </x-label>
                    <x-input-text :property="'frequency'"
                                  :disabled="true"
                                  :value="to_title($award->frequency->name)" />
                </x-input-container>

                <x-input-container class="text-end">
                    <a href="{{ route('admin.awards.assign-trooper', compact('award')) }}"
                       class="btn btn-outline-primary">
                        Award Trooper
                    </a>
                </x-input-container>

            </form>

            <x-table>
                <thead>
                    <tr>
                        <th>
                            Trooper
                        </th>
                        <th>
                            Awarded
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($troopers as $trooper)
                        <tr>
                            <td>
                                {{ $trooper->display_name }}
                            </td>
                            <td>
                                {{ $trooper->pivot->award_date->format('M d, Y') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        </x-card>
    </x-slim-container>

@endsection