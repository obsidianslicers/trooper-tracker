@extends('layouts.base')

@section('page-title', 'Update Organization')

@section('content')

<x-transmission-bar :id="'organization'" />

<x-slim-container>

  <x-card>
    <div class="row">
      <div class="col-md-12 col-lg-8">

        <form method="POST"
              novalidate="novalidate">
          @csrf

          @isset($organization->parent)
          <x-input-container>
            <x-label>
              Parent {{ $organization->parent->type->name }}:
            </x-label>
            <x-input-hidden :property="'parent_id'"
                            :value="$organization->parent_id" />
            <x-input-text :property="'parent_name'"
                          :disabled="true"
                          :value="$organization->parent->name" />
          </x-input-container>
          @endisset

          @if($organization->type == \App\Enums\OrganizationType::UNIT)
          @can('update', $organization->parent)
          {{-- TODO MOVE PARENTS --}}
          @endcan
          @endif

          <x-input-container>
            <x-label>
              Name:
            </x-label>
            <x-input-text :property="'name'"
                          :value="$organization->name" />
          </x-input-container>

          <x-submit-container>
            <x-submit-button>
              Save
            </x-submit-button>
            <x-link-button-cancel :url="route('admin.organizations.list')" />
          </x-submit-container>

        </form>

      </div>
      <div class="col-md-12 col-lg-4">

        <div class="row">
          <div class="col-sm-12 text-center">
            @include('pages.admin.organizations.image', ['organization'=>$organization])
          </div>
          <div class="col-sm-12">
            <p class="form-help text-center">
              <i class="form-help text-muted">
                The uploaded image will be resized to large 128x128 and small 32x32.
              </i>
            </p>
          </div>
        </div>
      </div>

    </div>


    <x-trooper-stamps :model="$organization" />
  </x-card>

</x-slim-container>

@endsection