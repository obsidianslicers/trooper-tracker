@extends('layouts.base')

@section('page-title', 'Organization Limits')

@section('content')

@include('pages.admin.events.tabs',['event'=>$event])

<x-slim-container>

  <form method="POST"
        novalidate="novalidate">
    @csrf


    <x-table class="mt-3">
      <thead>
        <tr>
          <th colspan="2">Organization</th>
          @if($event->has_organization_limits)
          <th>Can Sign-Up</th>
          {{--
          <th>Trooper Limit</th>
          <th>Handler Limit</th>
          --}}
          @endif
        </tr>
      </thead>
      <tr>
        <td colspan="2">
          <label for="event_name">
            {{ $event->name }}
          </label>
        </td>
        @if($event->has_organization_limits)
        <td class="text-center">
          <!-- just a placeholder for "select-all" -->
          <x-input-checkbox :property="'event_name'"
                            :value="1"
                            :checked="!$event->has_organization_limits"
                            :label="'Toggle All'"
                            data-node-path="" />
        </td>
        {{--
        <td>
          <x-input-text :property="'troopers_allowed'"
                        :value="$event->troopers_allowed"
                        placeholder="Unspecified"
                        class="form-control-sm" />
        </td>
        <td>
          <x-input-text :property="'handlers_allowed'"
                        :value="$event->handlers_allowed"
                        placeholder="Unspecified"
                        class="form-control-sm" />
        </td>
        --}}
        @endif
      </tr>

      @php($selected_map = [])
      @foreach ($event_organizations as $organization)
      @php($event_organization = $organization->event_organizations->first())
      @php($parent_selected = $selected_map[$organization->parent_id] ?? false)
      @php($selected_map[$organization->id] = $parent_selected || ($event_organization->can_attend ?? false))
      <tr>
        <td>
          <x-logo :storage_path="$organization->image_path_sm"
                  :default_path="'img/icons/organization-32x32.png'"
                  :width="32"
                  :height="32" />
        </td>
        <td>
          @foreach(range(0, $organization->depth - 1) as $i)
          @if($i==0 && $organization->id == $event->organization_id)
          <i class="fa fa-fw fa-brands fa-empire text-success"></i>
          @else
          <i class="fa fa-fw"></i>
          @endif
          @endforeach
          <label for="{{'organizations.'.$organization->id.'.can_attend'}}">
            {{ $organization->name }}
          </label>
        </td>
        @if($event->has_organization_limits)
        @php($checked = old('organizations.'.$organization->id.'.can_attend', $selected_map[$organization->id]))
        <td class="text-center">
          <x-input-checkbox :property="'organizations.'.$organization->id.'.can_attend'"
                            :value="1"
                            :checked="$checked || $parent_selected"
                            :disabled="$parent_selected"
                            data-node-path="{{ $organization->node_path }}" />
        </td>
        {{--
        <td>
          <x-input-text :property="'organizations.'.$organization->id.'.troopers_allowed'"
                        :value="$event_organization->troopers_allowed ?? null"
                        placeholder="Unspecified"
                        class="form-control-sm d-none"
                        data-node-path="{{ $organization->node_path }}" />
        </td>
        <td>
          <x-input-text :property="'organizations.'.$organization->id.'.handlers_allowed'"
                        :value="$event_organization->handlers_allowed ?? null"
                        placeholder="Unspecified"
                        class="form-control-sm d-none"
                        data-node-path="{{ $organization->node_path }}" />
        </td>
        --}}
        @endif
      </tr>
      @endforeach
    </x-table>

    <x-submit-container>
      <x-submit-button>
        Update
      </x-submit-button>
      <x-link-button-cancel :url="route('admin.events.update',['event'=>$event])" />
    </x-submit-container>

  </form>

</x-slim-container>

@endsection

@section('page-script')
<script type="text/javascript">
  document.addEventListener('DOMContentLoaded', () => {
    function toggleInput(path, isChecked) {
      const input = document.querySelectorAll(`input[type="text"][data-node-path="${path}"]`).forEach(input => {
        if (input) {
          // if (isChecked) {
          //   input.classList.remove('d-none');
          // } else {
          //   input.classList.add('d-none');
          // }
        }
      });
    }
    function bindCascadeCheckboxes() {
      document.querySelectorAll('input[type="checkbox"][data-node-path]').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
          const isChecked = this.checked;
          const nodePath = this.getAttribute('data-node-path') + '';

          toggleInput(nodePath, isChecked);

          // Cascade to all checkboxes whose node_path starts with this one
          document.querySelectorAll('input[type="checkbox"][data-node-path]').forEach(cb => {
            const cbPath = cb.getAttribute('data-node-path');
            if (cb !== this && cbPath.startsWith(nodePath)) {
              cb.checked = isChecked;
              cb.disabled = isChecked;
              toggleInput(cbPath, isChecked);
            }
          });
        });

      });
    }

    // Initial bind
    bindCascadeCheckboxes();

    // Re-bind after HTMX swaps content
    document.body.addEventListener('htmx:afterSettle', bindCascadeCheckboxes);
  });
</script>
@endsection