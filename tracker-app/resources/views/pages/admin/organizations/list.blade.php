@extends('layouts.base')

@section('page-title', 'Organizations')

@section('content')

<x-table>
  <thead>
    <tr>
      <th>
        Name
      </th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    @foreach($organizations as $organization)
    <tr>
      <td>
        @foreach(range(0, $organization->depth - 1) as $i)
        <i class="fa fa-fw"></i>
        @endforeach

        {{ $organization->name }}
      </td>
      <td>
        @if(Auth::user()->isAdministrator() || $organization->trooper_assignments->count() > 0)
        <x-action-menu>
          @can('create', \App\Models\Organization::class)
          @if($organization->type == \App\Enums\OrganizationType::ORGANIZATION)
          <x-action-link-create :label="'Add Region'"
                                :url="route('admin.organizations.create', ['parent'=>$organization])" />
          @endif
          @if($organization->type == \App\Enums\OrganizationType::REGION)
          <x-action-link-create :label="'Add Unit'"
                                :url="route('admin.organizations.create', ['parent'=>$organization])" />
          @endif
          @endcan
          <x-action-link-update :url="route('admin.organizations.update', ['organization'=>$organization])" />
          <x-action-separator />
          <x-action-link :label="'Add Notice'"
                         :icon="'fa-add'"
                         :url="route('admin.notices.create', ['organization_id'=>$organization->id])" />
          <x-action-link :label="'Notices'"
                         :url="route('admin.notices.list', ['organization_id'=>$organization->id])" />
        </x-action-menu>
        @endif
      </td>
    </tr>
    @endforeach
  </tbody>
</x-table>

@endsection