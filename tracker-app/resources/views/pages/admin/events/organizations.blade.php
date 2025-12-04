@extends('layouts.base')

@section('page-title', 'Organization Limits')

@section('content')

@include('pages.admin.events.tabs',['event'=>$event])


<form method="POST"
      novalidate="novalidate">
  @csrf

  <x-table class="mt-3">
    <thead>
      <tr>
        <th colspan="2">Organization</th>
        @if($event->limit_organizations)
        <th>Can Attend</th>
        <th>Trooper Limit</th>
        <th>Handler Limit</th>
        @endif
      </tr>
    </thead>
    <tr>
      <td colspan="2">
        {{ $event->name }}
      </td>
      @if($event->limit_organizations)
      <td>
        <x-input-checkbox :property="'organizationsx'"
                          :value="1"
                          :checked="!$event->limit_organizations"
                          data-node-path="" />
      </td>
      <td>
        <x-input-text :property="'troopers_allowed'"
                      :value="$event->troopers_allowed"
                      placeholder="Unlimited"
                      class="form-control-sm" />
      </td>
      <td>
        <x-input-text :property="'handlers_allowed'"
                      :value="$event->handlers_allowed"
                      placeholder="Unlimited"
                      class="form-control-sm" />
      </td>
      @endif
    </tr>

    @foreach ($event_organizations as $organization)
    @php($event_organization = $organization->event_organizations->first())
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
        <i class="fa fa-fw fa-circle text-success"></i>
        @else
        <i class="fa fa-fw"></i>
        @endif
        @endforeach
        {{ $organization->name }}
      </td>
      @if($event->limit_organizations)
      <td>
        <x-input-checkbox :property="'organizations.' . $organization->id . '.x'"
                          :value="1"
                          :checked="$event_organization->can_attend ?? !$event->limit_organizations"
                          data-node-path="{{ $organization->node_path }}" />
      </td>
      <td>
        <x-input-text :property="'organizations.' . $organization->id . 'troopers_allowed'"
                      placeholder="Unlimited"
                      class="form-control-sm {{ $event_organization->can_attend ?? false ? '' : 'd-none' }}"
                      data-node-path="{{ $organization->node_path }}" />
      </td>
      <td>
        <x-input-text :property="'organizations.' . $organization->id . 'handlers_allowed'"
                      placeholder="Unlimited"
                      class="form-control-sm {{ $event_organization->can_attend ?? false ? '' : 'd-none' }}"
                      data-node-path="{{ $organization->node_path }}" />
      </td>
      @endif
    </tr>
    @endforeach
  </x-table>

  <x-submit-container>
    <x-submit-button>
      Save
    </x-submit-button>
  </x-submit-container>
</form>


@endsection

@section('page-script')
<script type="text/javascript">
  document.addEventListener('DOMContentLoaded', () => {
    function toggleInput(path, isChecked) {
      const input = document.querySelectorAll(`input[type="text"][data-node-path="${path}"]`).forEach(input => {
        if (input) {
          if (isChecked) {
            input.classList.remove('d-none');
          } else {
            input.classList.add('d-none');
          }
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