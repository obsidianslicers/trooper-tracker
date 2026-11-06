@extends('layouts.base')

@section('page-title', $manifest->name.' Settings')

@section('content')

<x-slim-container>

  <x-card>
    <form method="POST"
          novalidate="novalidate">
      @csrf

      @foreach($manifest->settings as $setting)
        @php($property = 'settings.'.$setting->key)
        <x-input-container>
          <x-label>
            {{ $setting->label }}:
          </x-label>
          @if($setting->type === 'secret')
            <x-input-password :property="$property"
                              :placeholder="filled($settings[$setting->key] ?? null) ? 'Saved. Leave empty to keep it.' : ''" />
          @elseif($setting->type === 'boolean')
            <x-input-yesno :property="$property"
                           :value="(bool) ($settings[$setting->key] ?? false)" />
          @else
            <x-input-text :property="$property"
                          :value="$settings[$setting->key] ?? ''" />
          @endif
          @if($setting->help !== '')
            <x-input-help>
              {{ $setting->help }}
            </x-input-help>
          @endif
        </x-input-container>
      @endforeach

      <x-submit-container>
        <x-submit-button>
          Save
        </x-submit-button>
        <x-link-button-cancel :url="route('admin.plugins.list')" />
      </x-submit-container>

    </form>
  </x-card>

</x-slim-container>

@endsection
