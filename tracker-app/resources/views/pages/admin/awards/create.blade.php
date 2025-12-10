@extends('layouts.base')

@section('page-name', 'Create Award')

@section('content')

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
        <x-input-picker :property="'organization_id'"
                        :route="'pickers.organization'"
                        :params="['moderated_only' => true]"
                        :text="$award->organization->name ?? '(Select an Organization)'"
                        :value="$award->organization_id" />
      </x-input-container>

      <x-input-container>
        <x-label>
          Frequency:
        </x-label>
        <x-input-select :property="'false'"
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
          Create
        </x-submit-button>
        <x-link-button-cancel :url="route('admin.awards.list', ['organization_id'=>$award->organization_id])" />
      </x-submit-container>

    </form>
  </x-card>

</x-slim-container>

<x-modal-picker :label="'Select an Organization'" />

@endsection