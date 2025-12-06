@extends('layouts.base')

@section('page-title', 'Create Event')

@section('content')

<x-transmission-bar :id="'event'" />

<x-slim-container>

  <x-card>
    <form method="POST"
          novalidate="novalidate">
      @csrf

      <x-input-container>
        <x-label>
          Hosting Organization:
        </x-label>
        <x-input-picker :property="'organization_id'"
                        :route="'pickers.organization'"
                        :params="['moderated_only' => true]"
                        :text="$event->organization->name ?? 'Select a Host'"
                        :value="$event->organization_id" />
      </x-input-container>

      <x-input-container>
        <x-label>
          Type:
        </x-label>
        <x-input-select :property="'type'"
                        :value="$event->title"
                        :options="$options" />
      </x-input-container>

      <x-input-container>
        <x-label>
          Title:
        </x-label>
        <x-input-text :property="'title'"
                      :value="$event->title" />
      </x-input-container>

      <x-input-container>
        <div class="row">
          <div class="col-6">
            <x-label>
              Starts:
            </x-label>
            <x-input-datetime :property="'starts_at'"
                              :value="$event->starts_at" />
          </div>
          <div class="col-6">
            <x-label>
              Ends:
            </x-label>
            <x-input-datetime :property="'ends_at'"
                              :value="$event->ends_at" />
          </div>
        </div>
      </x-input-container>

      <x-input-container>
        <x-label>
          Message:
        </x-label>
        <x-input-text class="markdown-editor"
                      :multiline="true"
                      :property="'message'"
                      :value="$event->message" />
      </x-input-container>

      <x-submit-container>
        <x-submit-button>
          Create
        </x-submit-button>
        <x-link-button-cancel :url="route('admin.events.list', ['organization_id'=>$event->organization_id])" />
      </x-submit-container>

    </form>
  </x-card>

</x-slim-container>

<x-modal-picker :label="'Select an Organization'" />

@endsection