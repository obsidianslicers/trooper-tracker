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
                            <x-input-text :property="'costumes.' . $costume->id . '.name'"
                                          :value="$costume->name" />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>

        <x-submit-container>
            <x-submit-button>
                Update
            </x-submit-button>
            <x-link-button-cancel :url="route('admin.organizations.list')" />
        </x-submit-container>

    </form>

@endsection