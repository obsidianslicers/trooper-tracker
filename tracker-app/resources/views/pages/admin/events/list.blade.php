@extends('layouts.base')

@section('page-title', 'Events / Troops')

@section('content')
@php($organization_id = $organization->id ?? null)
<div class="row mb-3">
  <div class="col-sm-12 col-md-6">

    <form method="GET"
          action="{{ route('admin.events.list', ['organization_id'=>$organization_id]) }}"
          class="input-group">
      @foreach (qs() as $key=>$value)
      <x-input-hidden :property="$key"
                      :value="$value" />
      @endforeach
      <input type="text"
             name="search_term"
             placeholder="Search Event Name (at least 3 chars)"
             class="form-control rounded-start"
             value="{{ $search_term }}" />

      <button type="submit"
              class="btn btn-outline-secondary">
        <i class="fa fa-fw fa-search"></i>
      </button>
    </form>
    <div class="m-3">
      @if($organization != null)
      <x-filter-chip :label="$organization->name"
                     :url="route('admin.events.list', qs(['organization_id'=>null]))" />
      @endif
    </div>

  </div>
  <div class="col-sm-12 col-md-6 text-end">

    <x-button-group>
      <x-button-group-link :label="'All'"
                           :url="route('admin.events.list', qs(['status'=>null]))"
                           :active="$status==null" />
      @foreach(\App\Enums\EventStatus::toArray() as $value => $name)
      <x-button-group-link :label="$name"
                           :url="route('admin.events.list', qs(['status'=>$value]))"
                           :active="$status==$value" />
      @endforeach
    </x-button-group>

  </div>
</div>

<x-table>
  <thead>
    <tr>
      <th style="width: 36px;"></th>
      <th>
        Name
      </th>
      <th>
        Organization
      </th>
      <th>
        Status
      </th>
      <th>
        {{--
        <x-link-button-create :url="route('admin.events.create', ['organization_id'=> request('organization_id')])">
          Event
        </x-link-button-create>
        --}}
      </th>
    </tr>
  </thead>
  <tbody>
    @foreach($events as $event)
    <tr>
      <td>
        <x-logo :storage_path="$event->organization->image_path_sm"
                :default_path="'img/icons/organization-32x32.png'"
                :width="32"
                :height="32" />
      </td>
      <td>
        {{ $event->name }}
        <br />
        <i class="text-muted small">
          {{ $event->timeDisplay() }}
        </i>
      </td>
      <td>
        <a href="{{ route('admin.events.list', qs(['organization_id'=>$event->organization_id])) }}">
          {{ $event->organization->name }}
        </a>
      </td>
      <td>
        <a href="{{ route('admin.events.list', qs(['status'=>$event->status->value])) }}">
          {{ to_title($event->status->name) }}
        </a>
      </td>
      <td>
        <x-action-menu>
          @if(Auth::user()->isAdministrator() || $event->organization->trooper_assignments->count() > 0)
          <x-action-link-update :url="route('admin.events.update', ['event'=>$event])" />
          {{--<x-action-link-copy :url="route('admin.events.create', ['copy_id'=>$event->id])" />--}}
          @endif
        </x-action-menu>
      </td>
    </tr>
    @endforeach
  </tbody>
  <tfoot>
    <tr>
      <td colspan="5">
        {{ $events->links() }}
      </td>
    </tr>
  </tfoot>
</x-table>

@endsection