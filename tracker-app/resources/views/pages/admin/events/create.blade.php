@extends('layouts.base')

@section('page-title', 'Create Event')

@section('content')

    <x-transmission-bar :id="'event'" />

    <x-slim-container>

        <x-card>
            <form method="POST"
                  novalidate="novalidate"
                  x-data="Admin.Events.eventCreation({ mode: '{{ $mode }}', organizationId: {{ $event->organization_id ?? 'null' }}, organizationName: '{{ $event->organization?->name ?? 'null' }}', clubName: '{{ $event->organization?->getSourceClub()?->name ?? 'null' }}' })">
                @csrf

                <x-message>
                    By default when creating a new event, ALL clubs are defaulted to attend unless otherwise updated.
                    See <b>Character Requests & Attendee Limits</b> section to make adjustments to which clubs can attend.
                </x-message>

                <x-input-container x-ref="organizationPicker">
                    <x-label>
                        Hosting Organization:
                    </x-label>
                    <x-input-picker :property="'organization_id'"
                                    :route="'pickers.organization'"
                                    :params="['moderated_only' => true]"
                                    :text="$event->organization->name ?? 'Select a Host'"
                                    :value="$event->organization_id" />
                </x-input-container>

                <div x-show="form.organization_id > 0">
                    <x-input-container>
                        <div class="btn-group mb-3" role="group">
                            <input type="radio" 
                                class="btn-check"
                                name="mode"
                                id="mode-email"
                                autocomplete="off"
                                @checked($mode === 'email')
                                x-model="mode" 
                                value="email" />
                            <label class="btn btn-outline-primary"
                                for="mode-email">
                                Parse Message
                            </label>
                            <input type="radio" 
                                class="btn-check"
                                name="mode"
                                id="mode-manual"
                                autocomplete="off"
                                @checked($mode === 'manual')
                                x-model="mode"
                                value="manual" />
                            <label class="btn btn-outline-primary"
                                for="mode-manual">
                                Input Manually
                            </label>
                        </div>
                    </x-input-container>

                    <x-input-container x-show="mode == 'email'">
                        <x-label>
                            <div x-show="form.organization_id > 0">
                                <b x-text="form.club_name"></b>
                                Message:
                            </div>
                            <div x-show="form.organization_id == 0">
                                Message to Parse:
                            </div>
                        </x-label>
                        <x-input-text :property="'source'"
                                    :multiline="true"
                                    :placeholder="'Event Name: Name of Event'"
                                    x-model="sourceContent"
                                    x-on:change="parseSource()" />
                        <x-input-help>
                            Paste the request for appearance message here, and the
                            system will attempt to parse the relevant event details
                            based on the selected organization.
                        </x-input-help>
                    </x-input-container>

                    <div x-show="mode != 'email'">

                        <x-input-container>
                            <x-label>Name:</x-label>
                            <x-input-text :property="'name'"
                                        :value="$event->name"
                                        x-model="form.name" />
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

                        <x-accordion-card :label="'Location'">
                            <x-input-container>
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <x-label>Latitude:</x-label>
                                        <x-input-text :property="'latitude'"
                                                    :value="$event->latitude"
                                                    x-model="form.latitude" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <x-label>Longitude:</x-label>
                                        <x-input-text :property="'longitude'"
                                                    :value="$event->longitude"
                                                    x-model="form.longitude" />
                                    </div>
                                </div>
                            </x-input-container>

                            <x-input-container>
                                <x-label>Venue:</x-label>
                                <x-input-text :property="'venue'"
                                            :value="$event->venue"
                                            x-model="form.venue" />
                            </x-input-container>

                            <x-input-container>
                                <x-label>Venue Address:</x-label>
                                <x-input-text :property="'venue_address'"
                                            :value="$event->venue_address" 
                                            x-model="form.venue_address" />
                            </x-input-container>

                            <x-input-container>
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <x-label>City:</x-label>
                                        <x-input-text :property="'venue_city'"
                                                    :value="$event->venue_city"
                                                    x-model="form.venue_city" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <x-label>State:</x-label>
                                        <x-input-text :property="'venue_state'"
                                                    :value="$event->venue_state"
                                                    x-model="form.venue_state" />
                                    </div>
                                </div>
                            </x-input-container>

                            <x-input-container>
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <x-label>Zip:</x-label>
                                        <x-input-text :property="'venue_zip'"
                                                    :value="$event->venue_zip"
                                                    x-model="form.venue_zip" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <x-label>Country:</x-label>
                                        <x-input-text :property="'venue_country'"
                                                    :value="$event->venue_country"
                                                    x-model="form.venue_country" />
                                    </div>
                                </div>
                            </x-input-container>
                        </x-accordion-card>

                        <x-accordion-card :label="'Schedule'">
                            <x-message>
                                Shifts are ???
                            </x-message>
                            
                            <x-input-container>
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <x-label>Starts:</x-label>
                                        <x-input-datetime :property="'event_start'"
                                                        :value="$event->event_start"
                                                        x-model="form.event_start" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <x-label>Ends:</x-label>
                                        <x-input-datetime :property="'event_end'"
                                                        :value="$event->event_end"
                                                        x-model="form.event_end" />
                                    </div>
                                </div>
                            </x-input-container>

                            <x-input-container>
                                <x-label>Website:</x-label>
                                <x-input-text :property="'event_website'"
                                            :value="$event->event_website"
                                            x-model="form.event_website" />
                            </x-input-container>
                        </x-accordion-card>

                        <x-accordion-card :label="'Contact Information'">
                            <x-input-container>
                                <x-label>Contact Name:</x-label>
                                <x-input-text :property="'contact_name'"
                                            :value="$event->contact_name"
                                            x-model="form.contact_name" />
                            </x-input-container>

                            <x-input-container>
                                <x-label>Contact Phone:</x-label>
                                <x-input-text :property="'contact_phone'"
                                            :value="$event->contact_phone"
                                            x-model="form.contact_phone" />
                            </x-input-container>

                            <x-input-container>
                                <x-label>Contact Email:</x-label>
                                <x-input-text :property="'contact_email'"
                                            :value="$event->contact_email"
                                            x-model="form.contact_email" />
                            </x-input-container>
                        </x-accordion-card>

                        <x-accordion-card :label="'Character Requests & Attendee Limits'">

                            <x-input-container>
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <x-label>Expected Attendees:</x-label>
                                        <x-input-text :property="'expected_attendees'"
                                                    :value="$event->expected_attendees"
                                                    x-model="form.expected_attendees" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <x-label>Requested Number of Characters:</x-label>
                                        <x-input-text :property="'requested_number_characters'"
                                                    :value="$event->requested_number_characters" 
                                                    x-model="form.requested_number_characters"/>
                                    </div>
                                </div>
                            </x-input-container>

                            <x-input-container>
                                <x-label>Requested Character Types:</x-label>
                                <x-input-text :property="'requested_character_types'"
                                            :multiline="true"
                                            :value="$event->requested_character_types"
                                            x-model="form.requested_character_types" />
                            </x-input-container>

                            <x-input-container>
                                <x-label>Shifts Allowed (per event):</x-label>
                                <x-input-text :property="'shifts_allowed'"
                                            :value="$event->shifts_allowed"
                                            x-model="form.shifts_allowed"
                                            placeholder="blank=unlimited" />
                            </x-input-container>

                            <x-input-container>
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <x-label>Troopers Allowed (per shift):</x-label>
                                        <x-input-text :property="'troopers_allowed'"
                                                    :value="$event->troopers_allowed"
                                                    x-model="form.troopers_allowed"
                                                    placeholder="blank=unlimited" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <x-label>Handlers Allowed (per shift):</x-label>
                                        <x-input-text :property="'handlers_allowed'"
                                                    :value="$event->handlers_allowed"
                                                    x-model="form.handlers_allowed"
                                                    placeholder="blank=unlimited" />
                                    </div>
                                </div>
                            </x-input-container>

                            <x-input-container>
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <x-label>Friend Sign-Ups (per shift):</x-label>
                                        <x-input-text :property="'friends_allowed'"
                                                    :value="$event->friends_allowed"
                                                    x-model="form.friends_allowed"
                                                    placeholder="blank=unlimited" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <x-label>Allow Tentative Sign-Ups (per shift):</x-label>
                                        <x-input-yesno :property="'tentative_signups_allowed'"
                                                    :value="$event->tentative_signups_allowed"
                                                    x-model="form.tentative_signups_allowed" />
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
                                        <th>Troopers Allowed</th>
                                        <th>Handlers Allowed</th>
                                    </tr>
                                </thead>
                                @foreach ($organizations as $organization)
                                    <tr x-data="Admin.Events.eventOrganizationAttendance({ canAttend: {{ $organization->can_attend ?? 'false' }}, troopers: {{ $organization->troopers_allowed ?? 'null' }}, handlers: {{ $organization->handlers_allowed ?? 'null' }} })">
                                        <td>
                                            <x-logo :storage_path="$organization->image_path_sm"
                                                    :default_path="'img/icons/organization-32x32.png'"
                                                    :width="32"
                                                    :height="32" />
                                        </td>
                                        <td>
                                            <label for="{{ 'organizations.' . $organization->id . '.can_attend' }}">
                                                {{ $organization->name }}
                                            </label>
                                        </td>
                                        <td class="text-center">
                                            <x-input-checkbox :property="'organizations.' . $organization->id . '.can_attend'"
                                                              :value="1"
                                                              :checked="$organization->can_attend ?? false"
                                                              x-model="canAttend" />
                                        </td>
                                        <td class="text-center">
                                            <x-input-text :property="'organizations.' . $organization->id . '.troopers_allowed'"
                                                          :value="$organization->troopers_allowed ?? null"
                                                          class="form-control-sm"
                                                          placeholder="unlimited"
                                                          x-model="troopers"
                                                          x-bind:disabled="!canAttend" />
                                        </td>
                                        <td class="text-center">
                                            <x-input-text :property="'organizations.' . $organization->id . '.handlers_allowed'"
                                                          :value="$organization->handlers_allowed ?? null"
                                                          class="form-control-sm"
                                                          placeholder="unlimited"
                                                          x-model="handlers"
                                                          x-bind:disabled="!canAttend" />
                                        </td>
                                    </tr>
                                @endforeach
                            </x-table>
                        </x-accordion-card>

                        <x-accordion-card :label="'Venue Permissions & Amenities'">
                            <x-input-container>
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <x-label>Secure Staging Area:</x-label>
                                        <x-input-yesno :property="'secure_staging_area'"
                                                       :value="$event->secure_staging_area"
                                                      x-model="form.secure_staging_area" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <x-label>Parking Available:</x-label>
                                        <x-input-yesno :property="'parking_available'"
                                                       :value="$event->parking_available"
                                                       x-model="form.parking_available" />
                                    </div>
                                </div>
                            </x-input-container>

                            <x-input-container>
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <x-label>Allow Blasters:</x-label>
                                        <x-input-yesno :property="'allow_blasters'"
                                                    :value="$event->allow_blasters"
                                                    x-model="form.allow_blasters" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <x-label>Allow Props:</x-label>
                                        <x-input-yesno :property="'allow_props'"
                                                       :value="$event->allow_props"
                                                       x-model="form.allow_props" />
                                    </div>
                                </div>
                            </x-input-container>

                            <x-input-container>
                                <x-label>Accessible:</x-label>
                                <x-input-yesno :property="'accessible'"
                                               :value="$event->accessible"
                                               x-model="form.accessible" />
                            </x-input-container>

                            <x-input-container>
                                <x-label>Amenities:</x-label>
                                <x-input-text :property="'amenities'"
                                              :multiline="true"
                                              :value="$event->amenities"
                                              x-model="form.amenities" />
                            </x-input-container>
                        </x-accordion-card>

                        <x-accordion-card :label="'Charity'">

                            <x-input-container>
                                <x-label>Name:</x-label>
                                <x-input-text :property="'charity_name'"
                                              :value="$event->charity_name"
                                              x-model="form.charity_name" />
                            </x-input-container>

                            <x-input-container>
                                <x-label>Hours:</x-label>
                                <x-input-text :property="'charity_hours'"
                                              :value="$event->charity_hours"
                                              x-model="form.charity_hours" />
                            </x-input-container>

                            <x-input-container>
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <x-label>Direct Funds:</x-label>
                                        <x-input-text :property="'direct_funds'"
                                                      :value="$event->direct_funds"
                                                      x-model="form.direct_funds" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <x-label>Indirect Funds:</x-label>
                                        <x-input-text :property="'indirect_funds'"
                                                      :value="$event->indirect_funds"
                                                      x-model="form.indirect_funds" />
                                    </div>
                                </div>
                            </x-input-container>

                            <x-input-container>
                                <x-label>Notes:</x-label>
                                <x-input-text :property="'charity_notes'"
                                              :multiline="true"
                                              :value="$event->charity_notes"
                                              x-model="form.charity_notes" />
                            </x-input-container>
                        </x-accordion-card>

                        <x-accordion-card :label="'Miscellaneous'">
                            <x-input-container>
                                <x-label>Comments:</x-label>
                                <x-input-text :property="'comments'"
                                              :multiline="true"
                                              :value="$event->comments"
                                              x-model="form.comments"
                                              class="markdown-editor" />
                            </x-input-container>

                            <x-input-container>
                                <x-label>Referred By:</x-label>
                                <x-input-text :property="'referred_by'"
                                              :value="$event->referred_by"
                                              x-model="form.referred_by" />
                            </x-input-container>
                        </x-accordion-card>

                    </div>
                </div>

                <x-submit-container>
                    <x-submit-button>
                        Create
                    </x-submit-button>
                    @if($event->organization_id > 0)
                        <x-link-button-cancel :url="route('admin.events.list', ['organization_id' => $event->organization_id])" />
                    @else
                        <x-link-button-cancel :url="route('admin.events.list')" />
                    @endif
                </x-submit-container>

            </form>
        </x-card>

    </x-slim-container>

    <x-modal-picker :label="'Select an Organization'" />

@endsection

@section('page-script')
<script>
    window.$organization_hierarchy = @json($organization_hierarchy);
</script>
@endsection