<x-accordion-card :label="'Character Requests & Attendee Limits (Trooper Quotas)'">

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
                              x-model="form.requested_number_characters" />
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
        <div class="row">
            <div class="col-12 col-md-6">
                <x-label>Maximum Shift Sign-Ups (per event):</x-label>
                <x-input-text :property="'shifts_allowed'"
                              :value="$event->shifts_allowed"
                              x-model="form.shifts_allowed"
                              placeholder="blank=unlimited" />
            </div>
            <div class="col-12 col-md-6">
                <x-label>Tentative Sign-Ups Allowed (per shift):</x-label>
                <x-input-yesno :property="'tentative_signups_allowed'"
                               :value="$event->tentative_signups_allowed"
                               x-model="form.tentative_signups_allowed" />
            </div>
        </div>
    </x-input-container>

    <x-input-container>
        <div class="row">
            <div class="col-12 col-md-6">
                <x-label>Trooper Capacity (per shift):</x-label>
                <x-input-text :property="'troopers_allowed'"
                              :value="$event->troopers_allowed"
                              x-model="form.troopers_allowed"
                              placeholder="blank=unlimited" />
            </div>
            <div class="col-12 col-md-6">
                <x-label>Handler Capacity (per shift):</x-label>
                <x-input-text :property="'handlers_allowed'"
                              :value="$event->handlers_allowed"
                              x-model="form.handlers_allowed"
                              placeholder="blank=unlimited, zero=none" />
            </div>
        </div>
    </x-input-container>

    <x-input-container>
        <div class="row">
            <div class="col-12 col-md-6">
                <x-label>Limit <b class="text-success">Add a Trooper</b> Sign-Ups (per shift):</x-label>
                <x-input-text :property="'friends_allowed'"
                              :value="$event->friends_allowed"
                              x-model="form.friends_allowed"
                              placeholder="blank=unlimited, zero=none" />
            </div>
            <div class="col-12 col-md-6">
                <x-label>Limit <b class="text-success">Add a Guest</b> Sign-Ups (per shift):</x-label>
                <x-input-text :property="'guests_allowed'"
                              :value="$event->guests_allowed"
                              x-model="form.guests_allowed"
                              placeholder="blank=unlimited, zero=none" />
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
                <th>Limit Troopers</th>
                <th>Limit Handlers</th>
            </tr>
        </thead>
        @foreach ($organizations as $organization)
            <tr
                x-data="Admin.Events.eventOrganizationAttendance({ canAttend: {{ ($organization->pivot->can_attend ?? false) ? 'true' : 'false' }}, troopers: {{ $organization->pivot->troopers_allowed ?? 'null' }}, handlers: {{ $organization->pivot->handlers_allowed ?? 'null' }} })">
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
                                      :checked="$organization->pivot->can_attend ?? false"
                                      x-model="canAttend" />
                </td>
                <td class="text-center">
                    <x-input-text :property="'organizations.' . $organization->id . '.troopers_allowed'"
                                  :value="$organization->pivot->troopers_allowed ?? null"
                                  class="form-control-sm"
                                  placeholder="unlimited"
                                  x-model="troopers"
                                  x-bind:disabled="!canAttend" />
                </td>
                <td class="text-center">
                    <x-input-text :property="'organizations.' . $organization->id . '.handlers_allowed'"
                                  :value="$organization->pivot->handlers_allowed ?? null"
                                  class="form-control-sm"
                                  placeholder="unlimited"
                                  x-model="handlers"
                                  x-bind:disabled="!canAttend" />
                </td>
            </tr>
        @endforeach
        <tfoot>
            <tr>
                <td colspan="5"
                    class="text-muted">
                    ** Organization limits are allowed, but attendance is managed on the honor
                    system. Total event capacity is the main restriction, and because
                    participants may wear dual organization costumes, attendance cannot
                    be accurately tracked by organization.
                </td>
            </tr>
        </tfoot>
    </x-table>
</x-accordion-card>