@extends('layouts.base')

@section('page-title', 'Troopers')

@section('content')

<div class="row mb-3">
  <div class="col-sm-12 col-md-6">

    <form method="GET"
          action="{{ route('admin.troopers.list') }}"
          class="input-group">
      @foreach (qs() as $key=>$value)
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
                           :active="$membership_role==null" />
      @foreach(\App\Enums\MembershipRole::toArray() as $value => $name)
      <x-button-group-link :label="$name"
                           :url="route('admin.troopers.list', qs(['membership_role'=>$value]))"
                           :active="$membership_role==$value" />
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
        <a href="{{ route('admin.troopers.list', qs(['membership_role'=>$trooper->membership_role->value])) }}">
          {{ to_title($trooper->membership_role->name) }}
        </a>
      </td>
      <td>{{ to_title($trooper->membership_status->name) }}</td>
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
      <td colspan="4">
        {{ $troopers->links() }}
      </td>
    </tr>
  </tfoot>
</x-table>

@endsection