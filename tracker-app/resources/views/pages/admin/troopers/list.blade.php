@extends('layouts.base')

@section('page-title', 'Troopers')

@section('content')

    <div class="row mb-3 gy-2">
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
                       placeholder="Search Name, Legal Name, Email (at least 3 chars)"
                       class="form-control rounded-start"
                       value="{{ $search_term }}" />

                <button type="submit"
                        class="btn btn-outline-secondary">
                    <i class="fa fa-fw fa-search"></i>
                </button>
            </form>

            <form method="GET"
                  action="{{ route('admin.troopers.list') }}"
                  class="mt-2">
                @foreach(request()->except(['organization_id', 'page']) as $key => $value)
                    <x-input-hidden :property="$key"
                                    :value="$value" />
                @endforeach
                <select name="organization_id"
                        class="form-select"
                        onchange="this.form.submit()">
                    <option value="">All Groups</option>
                    @foreach($organizations as $org)
                        <option value="{{ $org->id }}"
                                {{ $organization_id == $org->id ? 'selected' : '' }}>
                            {{ $org->indented_name }}
                        </option>
                    @endforeach
                </select>
            </form>

            @if($selected_organization)
                <div class="mt-2">
                    <x-filter-chip :label="$selected_organization->name"
                                   :url="route('admin.troopers.list', qs(['organization_id' => null]))" />
                </div>
            @endif

        </div>
        <div class="col-sm-12 col-md-6 text-end d-flex flex-wrap justify-content-end gap-2 align-items-start">

            <a href="{{ route('admin.troopers.recruit') }}"
               class="btn btn-outline-primary btn-sm">
                <i class="fa fa-fw fa-user-plus"></i> Recruit Trooper
            </a>

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
                @foreach($sort_headers as $header)
                    <th>
                        <a href="{{ $header['url'] }}">
                            {{ $header['label'] }} <i class="fa fa-fw {{ $header['icon'] }}"></i>
                        </a>
                    </th>
                @endforeach
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
                        {{ $trooper->display_name }}
                        @if($trooper->membership_status === \App\Enums\MembershipStatus::VOID)
                            <span class="badge bg-secondary ms-1">Created in Error</span>
                        @endif
                        <br />
                        <span class="text-muted">{{ $trooper->legal_name }}</span>
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
