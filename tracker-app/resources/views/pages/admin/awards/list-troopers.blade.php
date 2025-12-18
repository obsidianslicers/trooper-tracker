@extends('layouts.base')

@section('page-title', 'Troopers Awarded')

@section('content')

<x-slim-container>

    <x-card>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>Troopers Awarded: {{ $award->name }}</h5>
            <a href="{{ route('admin.awards.assign-troopers', $award) }}" class="btn btn-primary">Assign to Troopers</a>
        </div>

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
                        {{ $trooper->name }}
                    </td>
                    <td>
                        {{ $trooper->pivot->award_date->format('M d Y') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </x-table>
    </x-card>
</x-slim-container>

@endsection