@extends('layouts.base')

@section('page-title', 'Update Event')

@section('content')

<x-transmission-bar :id="'event'" />

@include('pages.admin.events.tabs', compact('event'))

<x-slim-container>
  <x-card>
    <form method="POST"
          novalidate="novalidate">
      @csrf

      <x-accordion-card :label="'Basic Information'"
                        :open="true">
        <x-input-container>
          <x-label>Hosting Organization:</x-label>
          <x-input-text :property="'organization_name'"
                        :disabled="true"
                        :value="$event->organization->name ?? 'Everyone'" />
        </x-input-container>

        <x-input-container>
          <x-label>Name:</x-label>
          <x-input-text :property="'name'"
                        :value="$event->name" />
        </x-input-container>

        <x-input-container>
          <x-label>Event Type:</x-label>
          <x-input-select :property="'type'"
                          :options="\App\Enums\EventType::toArray()"
                          :value="$event->type->value" />
        </x-input-container>

        <x-input-container>
          <x-label>Status:</x-label>
          <x-input-select :property="'status'"
                          :options="\App\Enums\EventStatus::toArray()"
                          :value="$event->status->value" />
        </x-input-container>
      </x-accordion-card>

      <x-accordion-card :label="'Location'">
        <x-input-container>
          <div class="row">
            <div class="col-6">
              <x-label>Latitude:</x-label>
              <x-input-text :property="'latitude'"
                            :value="$event->latitude" />
            </div>
            <div class="col-6">
              <x-label>Longitude:</x-label>
              <x-input-text :property="'longitude'"
                            :value="$event->longitude" />
            </div>
          </div>
        </x-input-container>

        <x-input-container>
          <x-label>Venue:</x-label>
          <x-input-text :property="'venue'"
                        :value="$event->venue" />
        </x-input-container>

        <x-input-container>
          <x-label>Venue Address:</x-label>
          <x-input-text :property="'venue_address'"
                        :value="$event->venue_address" />
        </x-input-container>

        <x-input-container>
          <div class="row">
            <div class="col-6">
              <x-label>City:</x-label>
              <x-input-text :property="'venue_city'"
                            :value="$event->venue_city" />
            </div>
            <div class="col-6">
              <x-label>State:</x-label>
              <x-input-text :property="'venue_state'"
                            :value="$event->venue_state" />
            </div>
          </div>
        </x-input-container>

        <x-input-container>
          <div class="row">
            <div class="col-6">
              <x-label>Zip:</x-label>
              <x-input-text :property="'venue_zip'"
                            :value="$event->venue_zip" />
            </div>
            <div class="col-6">
              <x-label>Country:</x-label>
              <x-input-text :property="'venue_country'"
                            :value="$event->venue_country" />
            </div>
          </div>
        </x-input-container>
      </x-accordion-card>

      <x-accordion-card :label="'Schedule'">
        <x-input-container>
          <div class="row">
            <div class="col-6">
              <x-label>Starts:</x-label>
              <x-input-datetime :property="'event_start'"
                                :value="$event->event_start" />
            </div>
            <div class="col-6">
              <x-label>Ends:</x-label>
              <x-input-datetime :property="'event_end'"
                                :value="$event->event_end" />
            </div>
          </div>
        </x-input-container>

        <x-input-container>
          <x-label>Website:</x-label>
          <x-input-text :property="'event_website'"
                        :value="$event->event_website" />
        </x-input-container>
      </x-accordion-card>

      <x-accordion-card :label="'Contact Information'">
        <x-input-container>
          <x-label>Contact Name:</x-label>
          <x-input-text :property="'contact_name'"
                        :value="$event->contact_name" />
        </x-input-container>

        <x-input-container>
          <x-label>Contact Phone:</x-label>
          <x-input-text :property="'contact_phone'"
                        :value="$event->contact_phone" />
        </x-input-container>

        <x-input-container>
          <x-label>Contact Email:</x-label>
          <x-input-text :property="'contact_email'"
                        :value="$event->contact_email" />
        </x-input-container>
      </x-accordion-card>

      <x-accordion-card :label="'Character Requests & Attendees'">
        {{--
        <x-input-container>
          <x-label>Limit Organizations:</x-label>
          <x-input-yesno :property="'has_organization_limits'"
                         :value="$event->has_organization_limits" />
          <x-input-help>
            <b>TODO</b> limit to hosting organization, use a picker?<br />
            Can any organization sign up, or should it be restricted to selected organizations?
          </x-input-help>
        </x-input-container>
        --}}

        <x-input-container>
          <div class="row">
            <div class="col-6">
              <x-label>Expected Attendees:</x-label>
              <x-input-text :property="'expected_attendees'"
                            :value="$event->expected_attendees" />
            </div>
            <div class="col-6">
              <x-label>Requested Characters:</x-label>
              <x-input-text :property="'requested_characters'"
                            :value="$event->requested_characters" />
            </div>
          </div>
        </x-input-container>

        <x-input-container>
          <x-label>Requested Character Types:</x-label>
          <x-input-text :property="'requested_character_types'"
                        :multiline="true"
                        :value="$event->requested_character_types" />
        </x-input-container>

        <x-input-container>
          <div class="row">
            <div class="col-6">
              <x-label>Troopers Allowed (per shift):</x-label>
              <x-input-text :property="'troopers_allowed'"
                            :value="$event->troopers_allowed"
                            placeholder="blank=unlimited" />
            </div>
            <div class="col-6">
              <x-label>Handlers Allowed (per shift):</x-label>
              <x-input-text :property="'handlers_allowed'"
                            :value="$event->handlers_allowed"
                            placeholder="blank=unlimited" />
            </div>
          </div>
        </x-input-container>

        <x-table class="mt-3">
          <thead>
            <tr>
              <th colspan="2">Costume Organization Requested</th>
              <th>
                Can Sign Up
              </th>
            </tr>
          </thead>
          <tr>
            <td></td>
            <td></td>
            <td>
              <x-input-checkbox :property="'select_all_organizations'"
                                :value="1"
                                :checked="false"
                                :label="'Toggle All'" />
            </td>
          </tr>
          @foreach ($organizations as $organization)
          <tr>
            <td>
              <x-logo :storage_path="$organization->image_path_sm"
                      :default_path="'img/icons/organization-32x32.png'"
                      :width="32"
                      :height="32" />
            </td>
            <td>
              <label for="{{ 'organizations.'.$organization->id.'.can_attend' }}">
                {{ $organization->name }}
              </label>
            </td>
            <td class="text-center">
              <x-input-checkbox :property="'organizations.'.$organization->id.'.can_attend'"
                                :value="1"
                                :checked="$organization->selected"
                                data-organization-id="{{ $organization->id }}" />
            </td>
          </tr>
          @endforeach
        </x-table>
      </x-accordion-card>

      <x-accordion-card :label="'Venue Permissions & Amenities'">
        <x-input-container>
          <x-label>Secure Staging Area:</x-label>
          <x-input-yesno :property="'secure_staging_area'"
                         :value="$event->secure_staging_area" />
        </x-input-container>

        <x-input-container>
          <x-label>Allow Blasters:</x-label>
          <x-input-yesno :property="'allow_blasters'"
                         :value="$event->allow_blasters" />
        </x-input-container>

        <x-input-container>
          <x-label>Allow Props:</x-label>
          <x-input-yesno :property="'allow_props'"
                         :value="$event->allow_props" />
        </x-input-container>

        <x-input-container>
          <x-label>Parking Available:</x-label>
          <x-input-yesno :property="'parking_available'"
                         :value="$event->parking_available" />
        </x-input-container>

        <x-input-container>
          <x-label>Accessible:</x-label>
          <x-input-yesno :property="'accessible'"
                         :value="$event->accessible" />
        </x-input-container>

        <x-input-container>
          <x-label>Amenities:</x-label>
          <x-input-text :property="'amenities'"
                        :multiline="true"
                        :value="$event->amenities" />
        </x-input-container>
      </x-accordion-card>

      <x-accordion-card :label="'Miscellaneous'">
        <x-input-container>
          <x-label>Comments:</x-label>
          <x-input-text :property="'comments'"
                        :multiline="true"
                        :value="$event->comments" />
        </x-input-container>

        <x-input-container>
          <x-label>Referred By:</x-label>
          <x-input-text :property="'referred_by'"
                        :value="$event->referred_by" />
        </x-input-container>
      </x-accordion-card>

      <x-submit-container>
        <x-submit-button>Update</x-submit-button>
        <x-link-button-cancel :url="route('admin.events.update', compact('event'))" />
      </x-submit-container>

      <x-trooper-stamps :model="$event" />
    </form>
  </x-card>
</x-slim-container>

@endsection

@section('page-script')
<script type="text/javascript">
  document.addEventListener('DOMContentLoaded', () => {
    function toggleCheckboxes() {
      const toggler = document.querySelector('input[type="checkbox"][name="select_all_organizations"]');

      toggler.addEventListener('change', function () {
        const isChecked = this.checked;

        // Cascade to all checkboxes whose node_path starts with this one
        document.querySelectorAll('input[type="checkbox"][data-organization-id]').forEach(cb => {
          cb.checked = isChecked;
        });
      });
    }

    // Initial bind
    toggleCheckboxes();

    // Re-bind after HTMX swaps content
    document.body.addEventListener('htmx:afterSettle', toggleCheckboxes);
  });
</script>
@endsection