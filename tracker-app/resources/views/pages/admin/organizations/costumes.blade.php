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
                        <x-link-button-create :url="route('admin.organizations.create-costume', compact('organization'))">
                            Costume
                        </x-link-button-create>
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach($organization->organization_costumes as $costume)
                    <tr>
                        <td colspan="2">
                            {{ $costume->name }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>

    </form>

@endsection