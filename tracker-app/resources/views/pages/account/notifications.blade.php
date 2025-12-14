@extends('layouts.base')

@section('page-title', 'Trooper Notifications')

@section('content')

@include('pages.account.tabs')

<x-slim-container>

  <x-card>

    <form method="POST"
          novalidate="novalidate">
      @csrf

      <h3>Website</h3>

      <x-input-container class="ps-5">
        <!-- efast -->
        <x-input-checkbox :property="'instant_notification'"
                          :label="'Instant Event Notification'"
                          :value="1"
                          :checked="$instant_notification" />
      </x-input-container>

      <x-input-container class="ps-5">
        <!-- econfirm -->
        <x-input-checkbox :property="'attendance_notification'"
                          :label="'Confirm Attendance Notification'"
                          :value="1"
                          :checked="$attendance_notification" />
      </x-input-container>

      <x-input-container class="ps-5">
        <!-- ecommandnotify -->
        <x-input-checkbox :property="'command_staff_notification'"
                          :label="'Command Staff Notifications'"
                          :value="1"
                          :checked="$command_staff_notification" />
      </x-input-container>

      <h3>Squads / Clubs</h3>
      <p>
        <i>Note: Events are categorized by 501st region territory. To receive event notifications for a particular area,
          ensure you subscribed to the appropriate region(s). Organization notifications are used in command staff e-mails, to send
          command staff information on trooper milestones based on region or organization.</i>
      </p>

      @foreach ($organizations as $organization)
      <x-input-container class="ps-5">
        <x-input-checkbox :property="'organizations.' . $organization->id . '.notification'"
                          :label="$organization->name"
                          :value="1"
                          :checked="$organization->selected"
                          data-organization-id="{{ $organization->id }}" />
        @foreach ($organization->organizations as $region)
        <x-input-container class="ps-5">
          <x-input-checkbox :property="'regions.' . $region->id . '.notification'"
                            :label="$region->name"
                            :value="1"
                            :checked="$region->selected"
                            data-organization-id="{{ $organization->id }}"
                            data-region-id="{{ $region->id }}" />
          @foreach ($region->organizations as $unit)
          <x-input-container class="ps-5">
            <x-input-checkbox :property="'units.' . $unit->id . '.notification'"
                              :label="$unit->name"
                              :value="1"
                              :checked="$unit->selected"
                              data-organization-id="{{ $organization->id }}"
                              data-region-id="{{ $region->id }}" />
          </x-input-container>
          @endforeach
        </x-input-container>
        @endforeach
      </x-input-container>
      @endforeach

      <x-submit-container>
        <x-submit-button>
          Update
        </x-submit-button>
      </x-submit-container>
    </form>

  </x-card>

</x-slim-container>

@endsection

@section('page-script')
<script type="text/javascript">
  document.addEventListener('DOMContentLoaded', () => {
    function bindCascadeCheckboxes() {
      document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
          const isChecked = this.checked;
          const name = this.getAttribute('name');

          // Organization-level toggle
          if (name.startsWith('organizations') && this.hasAttribute('data-organization-id')) {
            const orgId = this.getAttribute('data-organization-id');
            document.querySelectorAll(`input[type="checkbox"][data-organization-id="${orgId}"]`).forEach(cb => {
              if (cb !== this) cb.checked = isChecked;
            });
          }

          // Region-level toggle
          if (name.startsWith('regions') && this.hasAttribute('data-region-id')) {
            const regionId = this.getAttribute('data-region-id');
            document.querySelectorAll(`input[type="checkbox"][data-region-id="${regionId}"]`).forEach(cb => {
              if (cb !== this) cb.checked = isChecked;
            });
          }
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