@extends('layouts.base')

@section('page-title', 'Troopers Awards')

@section('content')
@php($organization_id = $organization->id ?? null)

<div class="row mb-3">
  <div class="col-sm-12 col-md-6">
    @if($organization != null)
    <x-filter-chip :label="$organization->name"
                   :url="route('admin.awards.list',qs(['organization_id'=>null]))" />
    @endif
  </div>
</div>

<x-table>
  <thead>
    <tr>
      <th style="width: 36px;"></th>
      <th>
        Title
      </th>
      <th>
        Organization
      </th>
      <th>
        <x-link-button-create :url="route('admin.awards.create', ['organization_id'=> $organization_id])">
          Award
        </x-link-button-create>
      </th>
    </tr>
  </thead>
  <tbody>
    @foreach($awards as $award)
    <tr>
      <td>
        <x-logo :storage_path="$award->organization->image_path_sm ?? ''"
                :default_path="'img/icons/organization-32x32.png'"
                :width="32"
                :height="32" />
      </td>
      <td>
        {{ $award->name }}
      </td>
      <td>
        @if($award->organization_id == null)
        Everyone
        @else
        <a href="{{ route('admin.awards.list', qs(['organization_id'=>$award->organization_id])) }}">
          {{ $award->organization->name }}
        </a>
        @endif
      </td>
      <td>
        <x-action-menu>
          @if(Auth::user()->isAdministrator() || $award->organization->trooper_assignments->count() > 0)
          <x-action-link-update :url="route('admin.awards.update', compact('award'))" />
          <x-action-link-copy :url="route('admin.awards.create', ['copy_id'=>$award->id])" />
          <x-action-separator />
          <x-action-link :label="'Troopers Awarded'"
                         :url="route('admin.awards.list-troopers', compact('award'))" />
          @endif
        </x-action-menu>
      </td>
    </tr>
    @endforeach
  </tbody>
  <tfoot>
    <tr>
      <td colspan="4">
        {{ $awards->links() }}
      </td>
    </tr>
  </tfoot>
</x-table>

@endsection