@extends('layouts.base')

@section('page-title', __('admin.Create Notice'))

@section('content')

<x-transmission-bar :id="'notice'" />

<x-slim-container>

  <x-card>
    <form method="POST"
          novalidate="novalidate">
      @csrf

      <x-input-container>
        <x-label>
          {{ __('admin.Organization:') }}
        </x-label>
        <x-input-picker :property="'organization_id'"
                        :route="'pickers.organization'"
                        :params="['moderated_only' => true]"
                        :text="$notice->organization->name ?? 'Everyone'"
                        :value="$notice->organization_id" />
      </x-input-container>

      <x-input-container>
        <x-label>
          {{ __('admin.Type:') }}
        </x-label>
        <x-input-select :property="'type'"
                        :value="$notice->type->value"
                        :options="\App\Enums\NoticeType::toOptions()" />
      </x-input-container>

      <x-input-container>
        <x-label>
          {{ __('admin.Title:') }}
        </x-label>
        <x-input-text :property="'title'"
                      :value="$notice->title" />
      </x-input-container>

      <x-input-container>
        <div class="row">
          <div class="col-6">
            <x-label>
              {{ __('admin.Starts:') }}
            </x-label>
            <x-input-datetime :property="'starts_at'"
                              :value="$notice->starts_at" />
          </div>
          <div class="col-6">
            <x-label>
              {{ __('admin.Ends:') }}
            </x-label>
            <x-input-datetime :property="'ends_at'"
                              :value="$notice->ends_at" />
          </div>
        </div>
      </x-input-container>

      <x-input-container>
        <x-label>
          {{ __('admin.Message:') }}
        </x-label>
        <x-input-text class="markdown-editor"
                      :multiline="true"
                      :property="'message'"
                      :value="$notice->message" />
      </x-input-container>

      <x-submit-container>
        <x-submit-button>
          {{ __('admin.Create') }}
        </x-submit-button>
        <x-link-button-cancel :url="route('admin.notices.list', ['organization_id'=>$notice->organization_id])" />
      </x-submit-container>

    </form>
  </x-card>

</x-slim-container>

<x-modal-picker :label="__('admin.Select an Organization')" />

@endsection