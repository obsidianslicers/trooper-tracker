@extends('layouts.base')

@section('page-title', 'Trooper Profile')

@section('content')

@include('pages.account.tabs')

<x-slim-container>

  <x-card>
    <form method="POST"
          novalidate="novalidate">
      @csrf

      <x-input-container>
        <x-label>
          Display Name (first &amp last name, or use a nickname to remain anonymous):
        </x-label>
        <x-input-text :property="'name'"
                      :value="$trooper->name" />
      </x-input-container>

      <x-input-container>
        <x-label>
          Email:
        </x-label>
        <x-input-text :property="'email'"
                      :value="$trooper->email" />
      </x-input-container>

      <x-input-container>
        <x-label>
          Phone (Optional):
        </x-label>
        <x-input-text :property="'phone'"
                      :value="$trooper->phone" />
      </x-input-container>

      <x-input-container>
        <x-label>
          Theme:
        </x-label>
        <x-input-select :property="'theme'"
                        :options="\App\Enums\TrooperTheme::toArray()"
                        :value="$trooper->theme->value" />
      </x-input-container>

      <x-submit-container>
        <span class="float-start">
          <a href="{{ route('dashboard.display') }}"
             class="btn btn-outline-info mb-2">
            View Your Dashboard
          </a>
        </span>
        <x-submit-button>
          Save
        </x-submit-button>
      </x-submit-container>
    </form>

  </x-card>

</x-slim-container>

@endsection