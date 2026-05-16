@extends('layouts.base')

@section('page-title', 'Costumes')

@section('content')

    <x-table>
        <thead>
            <tr>
                <th>
                    Name
                </th>
                <th>
                    <x-link-button-create :url="route('admin.costumes.create')">
                        Costume
                    </x-link-button-create>
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach($costumes as $costume)
                <tr>
                    <td>
                        {{ $costume->name }}
                        <br />
                        <i class="text-muted small">
                            {{ $costume->organizations->pluck('name')->implode(', ') }}
                        </i>
                    </td>
                    <td>
                        <x-action-menu>
                            <x-action-link-update :url="route('admin.costumes.update', compact('costume'))" />
                            @unless($costume->countsAsHandler())
                                <x-action-link-delete :url="route('admin.costumes.delete', compact('costume'))" />
                            @endunless
                        </x-action-menu>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>

@endsection