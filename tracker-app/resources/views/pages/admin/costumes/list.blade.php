@extends('layouts.base')

@section('page-title', 'Costumes')

@section('content')

    <div class="row mb-3">
        <div class="col-sm-12 col-md-6">
            <form method="GET"
                  class="input-group allow-enter-keypress"
                  action="{{ route('admin.costumes.list') }}">
                @foreach (qs(['page' => 1]) as $key => $value)
                    <x-input-hidden :property="$key"
                                    :value="$value" />
                @endforeach
                <input type="text"
                       name="search_term"
                       placeholder="Search Name (at least 3 chars)"
                       class="form-control rounded-start"
                       value="{{ $search_term }}" />
                <button type="submit"
                        class="btn btn-outline-secondary">
                    <i class="fa fa-fw fa-search"></i>
                </button>
            </form>
        </div>
    </div>

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
        <tfoot>
            <tr>
                <td colspan="2">
                    {{ $costumes->links() }}
                </td>
            </tr>
        </tfoot>
    </x-table>

@endsection