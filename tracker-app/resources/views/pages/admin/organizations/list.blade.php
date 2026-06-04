@extends('layouts.base')

@section('page-title', 'Organizations')

@section('content')

    <x-table>
        <thead>
            <tr>
                <th style="width: 36px;"></th>
                <th>
                    Name
                </th>
                <th>
                    Synchronized
                </th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($organizations as $organization)
                <tr>
                    <td>
                        <x-logo :storage_path="$organization->image_path_sm"
                                :default_path="'img/icons/organization-32x32.png'"
                                :width="32"
                                :height="32" />
                    </td>
                    <td>
                        @foreach(range(0, $organization->depth - 1) as $i)
                            <i class="fa fa-fw"></i>
                        @endforeach

                        {{ $organization->name }}
                    </td>
                    <td>
                        @if($organization->type == \App\Enums\OrganizationType::ORGANIZATION)
                            <x-date-format :value="$organization->synchronized_at" />
                        @endif
                    </td>
                    <td>
                        @if(Auth::user()->is_administrator || $organization->trooper_assignments->count() > 0 || $organization->can_moderate)
                            <x-action-menu>
                                @can('create', \App\Models\Organization::class)
                                    @if($organization->type == \App\Enums\OrganizationType::ORGANIZATION)
                                        <x-action-link-create :label="'Add Region'"
                                                              :url="route('admin.organizations.create', ['parent' => $organization])" />
                                        <x-action-link-update :label="'Costumes'"
                                                              :url="route('admin.organizations.costumes', compact('organization'))" />
                                    @endif
                                    @if($organization->type == \App\Enums\OrganizationType::REGION)
                                        <x-action-link-create :label="'Add Unit'"
                                                              :url="route('admin.organizations.create', ['parent' => $organization])" />
                                    @endif
                                @endcan
                                <x-action-link-update :url="route('admin.organizations.update', compact('organization'))" />
                                <x-action-separator />
                                <x-action-link :label="'Add Notice'"
                                               :icon="'fa-add'"
                                               :url="route('admin.notices.create', ['organization_id' => $organization->id])" />
                                <x-action-link :label="'Notices'"
                                               :url="route('admin.notices.list', ['organization_id' => $organization->id])" />
                            </x-action-menu>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>

@endsection