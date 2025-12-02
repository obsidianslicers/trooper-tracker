@extends('layouts.base')

@section('content')
@php($organization_id = $organization->id ?? null)

<div class="row">
  <div class="col-6">
    @if($organization != null)
    <x-filter-chip :label="$organization->name"
                   :url="route('admin.notices.list')" />
    @endif
  </div>
  <div class="col-6 text-end">

    <x-button-group>
      <x-button-group-link :label="'Active'"
                           :url="route('admin.notices.list', ['organization_id'=>$organization_id, 'scope'=>'active'])"
                           :active="$scope=='active'" />
      <x-button-group-link :label="'Past'"
                           :url="route('admin.notices.list', ['organization_id'=>$organization_id, 'scope'=>'past'])"
                           :active="$scope=='past'" />
      <x-button-group-link :label="'Future'"
                           :url="route('admin.notices.list', ['organization_id'=>$organization_id, 'scope'=>'future'])"
                           :active="$scope=='future'" />
    </x-button-group>

  </div>
</div>

<x-table>
  <thead>
    <tr>
      <th>
        Name
      </th>
      <th>
        Organization
      </th>
      <th>
        <x-link-button-create :url="route('admin.notices.create', ['organization_id'=> request('organization_id')])">
          Notice
        </x-link-button-create>
      </th>
    </tr>
  </thead>
  <tbody>
    @foreach($notices as $notice)
    <tr>
      <td>
        <i class="fa fa-fw fa-circle text-{{ $notice->type->value }} me-2"></i>
        {{ $notice->title }}
      </td>
      <td>
        @if($notice->organization_id == null)
        Everyone
        @else
        <a class="text-decoration-none"
           href="{{ route('admin.notices.list', ['organization_id'=>$notice->organization_id]) }}">
          {{ $notice->organization->name }}
        </a>
        @endif
      </td>
      <td>
        <x-action-menu>
          @if(Auth::user()->isAdministrator() && $notice->organization == null)
          <x-action-link-update :url="route('admin.notices.update', ['notice'=>$notice])" />
          <x-action-link-copy :url="route('admin.notices.create', ['copy_id'=>$notice->id])" />
          @elseif($notice->organization->trooper_assignments->count() > 0)
          <x-action-link-update :url="route('admin.notices.update', ['notice'=>$notice])" />
          <x-action-link-copy :url="route('admin.notices.create', ['copy_id'=>$notice->id])" />
          @endif
        </x-action-menu>
      </td>
    </tr>
    @endforeach
  </tbody>
  <tfoot>
    <tr>
      <td colspan="3">
        {{ $notices->links() }}
      </td>
    </tr>
  </tfoot>
</x-table>

@endsection