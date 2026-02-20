@extends('layouts.base')

@section('page-title', 'Organizations')

@section('content')

    @include('pages.admin.organizations.tabs', compact('organization'))

    <form method="POST"
          novalidate="novalidate">
        @csrf

        <x-table>
            <thead>
                <tr>
                    <th>
                        Name
                    </th>
                    <th>
                        Synchronized
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach($organization->costumes as $costume)
                    <tr>
                        <td>
                            {{ $costume->name }}
                        </td>
                        <td>
                            <x-date-format :value="$costume->pivot->synchronized_at" />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>

    </form>

@endsection