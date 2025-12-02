@extends('layouts.base')

@section('content')

<div class="row">
  <div class="col-6">
  </div>
  <div class="col-6 text-end">

    <x-button-group>
      <x-button-group-link :label="'All'"
                           :url="route('admin.troopers.list')"
                           :active="$membership_role==null" />
      @foreach(\App\Enums\MembershipRole::cases() as $case)
      <x-button-group-link :label="$case->name"
                           :url="route('admin.troopers.list', ['membership_role'=>$case->value])"
                           :active="$membership_role==$case->value" />
      @endforeach
    </x-button-group>

  </div>
</div>

<x-table>
  <thead>
    <tr>
      <th>
        Name
        <br />
        Email
      </th>
      <th>Role</th>
      <th>Status</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    @foreach($troopers as $trooper)
    <tr>
      <td>
        {{ $trooper->name }}
        <br />
        {{ $trooper->email }}
      </td>
      <td>
        <a href="{{ route('admin.troopers.list', ['membership_role'=>$trooper->membership_role->value]) }}">
          {{ $trooper->membership_role->name }}
        </a>
      </td>
      <td>{{ $trooper->membership_status->name }}</td>
      <td>
        <x-action-menu>
          <x-action-link-update :url="route('admin.troopers.update', ['trooper'=>$trooper])" />
        </x-action-menu>
      </td>
    </tr>
    @endforeach
  </tbody>
  <tfoot>
    <tr>
      <td colspan="4">
        {{ $troopers->links() }}
      </td>
    </tr>
  </tfoot>
</x-table>

@endsection