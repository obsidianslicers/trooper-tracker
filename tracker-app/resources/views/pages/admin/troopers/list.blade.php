@extends('layouts.base')

@section('page-title', 'Troopers')

@section('content')

<div class="row">
<<<<<<< HEAD
  <div class="col-sm-12 col-md-6">

    <form method="GET"
          action="{{ route('admin.troopers.list') }}"
          class="input-group">
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
=======
  <div class="col-6">
  </div>
  <div class="col-6 text-end">
>>>>>>> b60e060 (feature: add notice board)

    <x-button-group>
      <x-button-group-link :label="'All'"
                           :url="route('admin.troopers.list')"
                           :active="$membership_role==null" />
<<<<<<< HEAD
      @foreach(\App\Enums\MembershipRole::toArray() as $value => $name)
      <x-button-group-link :label="$name"
                           :url="route('admin.troopers.list', ['membership_role'=>$value])"
                           :active="$membership_role==$value" />
=======
      @foreach(\App\Enums\MembershipRole::cases() as $case)
      <x-button-group-link :label="$case->name"
                           :url="route('admin.troopers.list', ['membership_role'=>$case->value])"
                           :active="$membership_role==$case->value" />
>>>>>>> b60e060 (feature: add notice board)
      @endforeach
    </x-button-group>

  </div>
</div>

<x-table>
  <thead>
    <tr>
      <th>
        Name <i class="text-muted">(Username)</i> Email
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
        {{ $trooper->name }} <i class="text-muted">({{ $trooper->username }})</i>
        <br />
        {{ $trooper->email }}
      </td>
      <td>
        <a href="{{ route('admin.troopers.list', ['membership_role'=>$trooper->membership_role->value]) }}">
<<<<<<< HEAD
          {{ to_title($trooper->membership_role->name) }}
        </a>
      </td>
      <td>{{ to_title($trooper->membership_status->name) }}</td>
=======
          {{ $trooper->membership_role->name }}
        </a>
      </td>
      <td>{{ $trooper->membership_status->name }}</td>
>>>>>>> b60e060 (feature: add notice board)
      <td>
        <x-action-menu>
          <x-action-link-update :label="'Profile'"
                                :url="route('admin.troopers.profile', ['trooper'=>$trooper])" />
          @if(Auth::user()->isAdministrator())
          <x-action-link :label="'Authority'"
                         :url="route('admin.troopers.authority', ['trooper'=>$trooper])" />
          @endif
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