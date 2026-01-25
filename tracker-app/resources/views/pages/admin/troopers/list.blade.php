@extends('layouts.base')

@section('page-title', 'Troopers')

@section('content')

    <div class="row mb-3">
        <div class="col-sm-12 col-md-6">

            <form method="GET"
                  class="input-group allow-enter-keypress"
                  action="{{ route('admin.troopers.list') }}">
                @foreach (qs(['page' => 1]) as $key => $value)
                    <x-input-hidden :property="$key"
                                    :value="$value" />
                @endforeach
                <input type="text"
                       name="search_term"
                       placeholder="Search Name, Username, Email (at least 3 chars)"
                       class="form-control rounded-start"
                       value="{{ $search_term }}" />

                <button type="submit"
                        class="btn btn-outline-secondary">
                    <i class="fa fa-fw fa-search"></i>
                </button>
            </form>


        </div>
        <div class="col-sm-12 col-md-6 text-end">

            <x-button-group>
                <x-button-group-link :label="'All'"
                                     :url="route('admin.troopers.list')"
                                     :active="$membership_role === null" />
                @foreach(\App\Enums\MembershipRole::toArray() as $value => $name)
                    <x-button-group-link :label="$name"
                                         :url="route('admin.troopers.list', qs(['membership_role' => $value, 'page' => 1]))"
                                         :active="$membership_role == $value" />
                @endforeach
            </x-button-group>

        </div>
    </div>

    <x-transmission-bar :id="'trooper-search-results'" />

    <x-table id="trooper-search-results">
        <thead>
            <tr>
                <th>
                    Name / Email
                </th>
                <th>Role</th>
                <th>Status</th>
                <th>Last Seen</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($troopers as $trooper)
                <tr>
                    <td class="text-nowrap">
                        @if($trooper->is_active)
                            <i class="fa fa-fw fa-check text-success pe-2"></i>
                        @else
                            <i class="fa fa-fw fa-times text-danger pe-2"></i>
                        @endif
                        {{ $trooper->name }}
                        <br />
                        @if($trooper->email[0] == '^')
                            <span class="text-muted">( missing email )</span>
                        @else
                            {{ $trooper->email }}
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.troopers.list', qs(['membership_role' => $trooper->membership_role->value])) }}">
                            {{ to_title($trooper->membership_role->name) }}
                        </a>
                    </td>
                    <td>{{ to_title($trooper->membership_status->name) }}</td>
                    <td>
                    @if($trooper->last_active_at)
                        @php
                            $last_active = $trooper->last_active_at;
                            $is_old = $last_active->lt(now()->subYear());
                        @endphp
                        @if($is_old)
                            <span class="text-muted">
                                {{ $last_active->format('M d, Y') }}
                            </span>
                        @else
                            {{ $last_active->format('M d, Y') }}
                        @endif
                    @else
                    -
                    @endif
                    </td>
                    <td>
                        <x-action-menu>
                            <x-action-link-update :label="'Update'"
                                                  :url="route('admin.troopers.profile', compact('trooper'))" />
                        </x-action-menu>
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">
                    {{ $troopers->links() }}
                </td>
            </tr>
        </tfoot>
    </x-table>

@endsection