@extends('layouts.base')

@section('page-name', 'Update Award')

@section('content')

@include('pages.admin.awards.tabs', compact('award'))

<x-transmission-bar :id="'award'" />

<x-slim-container>

  <x-card>
    <form method="POST"
          novalidate="novalidate">
      @csrf

      <x-input-container>
        <x-label>
          Organization:
        </x-label>
        <x-input-text :property="'organization_name'"
                      :disabled="true"
                      :value="$award->organization->name" />
      </x-input-container>

      <x-input-container>
        <x-label>
          Frequency:
        </x-label>
        <x-input-select :property="'false'"
                        :disabled="true"
                        :value="$award->frequency->value"
                        :options="\App\Enums\AwardFrequency::toArray()" />
      </x-input-container>

      <x-input-container>
        <x-label>
          Name:
        </x-label>
        <x-input-text :property="'name'"
                      :value="$award->name" />
      </x-input-container>

      <x-submit-container>
        <x-submit-button>
          Update
        </x-submit-button>
        <x-link-button-cancel :url="route('admin.awards.list')" />
      </x-submit-container>

      <x-trooper-stamps :model="$award" />

    </form>
  </x-card>

</x-slim-container>

@endsection