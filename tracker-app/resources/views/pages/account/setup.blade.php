@extends('layouts.base')

@section('page-title', 'Trooper Profile')

@section('content')

<x-slim-container>

  <x-message type="info"
             icon="fa-brands fa-empire"
             class="w-100">
    Welcome to Trooper Tracker 2.0! To get started, please complete some necessary setup provided below.
  </x-message>

  <x-card>
    <form method="POST"
          novalidate="novalidate">
      @csrf

      <p class="text-warning">
        Is your email correct?
      </p>

      <x-input-container>
        <x-label>
          Email:
        </x-label>
        <x-input-text :property="'email'"
                      :value="$trooper->email" />
      </x-input-container>

      <x-transmission-bar :id="'organizations'" />

      <p class="text-warning">
        Are your assigned organizations correct, whether you're a member or a handler?
      </p>

      @foreach($organizations as $organization)
      <div class="row mb-3">
        <div class="col-sm-12 col-md-12 col-lg-4">
          <x-input-checkbox :property="'organizations.' . $organization->id . '.selected'"
                            :label="$organization->name"
                            :value="'1'"
                            :checked="$organization->selected"
                            data-organization-id="{{ $organization->id }}" />
        </div>
        <div class="col-sm-12 col-md-6 col-lg-4">
          @if($organization->organizations->isNotEmpty())
          <x-input-select :property="'organizations.' . $organization->id . '.region_id'"
                          :options="$organization->organizations->pluck('name', 'id')->toArray()"
                          :placeholder="'-- Region/Garrison --'"
                          :value="$organization->region->id ?? null"
                          :disabled="!$organization->selected"
                          hx-get="{{ route('account.setup', ['region_id'=>$organization->id]) }}"
                          hx-target="#unit-container-{{ $organization->id }}"
                          hx-swap="outerHTML"
                          hx-trigger="change"
                          hx-include="closest div"
                          hx-indicator="#transmission-bar-organizations"
                          data-organization-id="{{ $organization->id }}"
                          class="form-select-sm" />
          @endif
        </div>
        <div id="unit-container-{{ $organization->id }}"
             class="col-sm-12 col-md-6 col-lg-4">
          @php($units = $organization->region ? $organization->region->organizations : collect())
          @if($units->isNotEmpty())
          <x-input-select :property="'organizations.' . $organization->id . '.unit_id'"
                          :options="$units->pluck('name', 'id')->toArray()"
                          :placeholder="'-- Unit/Squad --'"
                          :value="$organization->unit->id ?? null"
                          :disabled="!$organization->selected"
                          data-organization-id="{{ $organization->id }}"
                          class="form-select-sm" />
          @endif
        </div>
      </div>
      @endforeach

      <x-submit-container>
        <x-submit-button>
          Save
        </x-submit-button>
      </x-submit-container>
    </form>

  </x-card>

</x-slim-container>

@endsection

@section('page-script')
<script type="text/javascript">
  document.addEventListener('DOMContentLoaded', () => {
    function bindCascadeDisabling() {
      document.querySelectorAll('input[type="checkbox"][data-organization-id]').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
          const isChecked = this.checked;
          const organizationId = this.getAttribute('data-organization-id') + '';

          // Cascade to all checkboxes whose node_path starts with this one
          const selects = `select[data-organization-id="${organizationId}"]`;
          document.querySelectorAll(selects).forEach(x => {
            x.disabled = !isChecked;
          });
        });
      });
    }

    // Initial bind
    bindCascadeDisabling();

    // Re-bind after HTMX swaps content
    document.body.addEventListener('htmx:afterSettle', bindCascadeDisabling);
  });
</script>
@endsection