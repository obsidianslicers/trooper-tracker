@extends('layouts.base')

@section('page-title', 'Event Venue')

@section('content')

<x-transmission-bar :id="'event'" />

@include('pages.admin.events.tabs', ['event'=>$event])

<x-slim-container>

  <x-card>

    <form method="POST"
          novalidate="novalidate">
      @csrf

      {{-- Coordinates --}}
      <x-input-container>
        <div class="row">
          <div class="col-6">
            <x-label>
              Latitude:
            </x-label>
            <x-input-text :property="'latitude'"
                          :value="$event_venue->latitude" />
          </div>
          <div class="col-6">
            <x-label>
              Longitude:
            </x-label>
            <x-input-text :property="'longitude'"
                          :value="$event_venue->longitude" />
          </div>
        </div>
      </x-input-container>

      {{-- Contact info --}}
      <x-input-container>
        <x-label>
          Contact Name:
        </x-label>
        <x-input-text :property="'contact_name'"
                      :value="$event_venue->contact_name" />
      </x-input-container>

      <x-input-container>
        <x-label>
          Contact Phone:
        </x-label>
        <x-input-text :property="'contact_phone'"
                      :value="$event_venue->contact_phone" />
      </x-input-container>

      <x-input-container>
        <x-label>
          Contact Email:
        </x-label>
        <x-input-text :property="'contact_email'"
                      :value="$event_venue->contact_email" />
      </x-input-container>

      {{-- Event details --}}
      <x-input-container>
        <x-label>
          Venue:
        </x-label>
        <x-input-text :property="'venue'"
                      :value="$event_venue->venue" />
      </x-input-container>

      <x-input-container>
        <x-label>
          Venue Address:
        </x-label>
        <x-input-text :property="'venue_address'"
                      :value="$event_venue->venue_address" />
      </x-input-container>

      <x-input-container>
        <div class="row">
          <div class="col-6">
            <x-label>
              City:
            </x-label>
            <x-input-text :property="'venue_city'"
                          :value="$event_venue->venue_city" />
          </div>
          <div class="col-6">
            <x-label>
              State:
            </x-label>
            <x-input-text :property="'venue_state'"
                          :value="$event_venue->venue_state" />
          </div>
        </div>
      </x-input-container>

      <x-input-container>
        <div class="row">
          <div class="col-6">
            <x-label>
              Zip:
            </x-label>
            <x-input-text :property="'venue_zip'"
                          :value="$event_venue->venue_zip" />
          </div>
          <div class="col-6">
            <x-label>
              Country:
            </x-label>
            <x-input-text :property="'venue_country'"
                          :value="$event_venue->venue_country" />
          </div>
        </div>
      </x-input-container>

      <x-input-container>
        <div class="row">
          <div class="col-6">
            <x-label>
              Starts:
            </x-label>
            <x-input-datetime :property="'event_start'"
                              :value="$event_venue->event_start" />
          </div>
          <div class="col-6">
            <x-label>
              Ends:
            </x-label>
            <x-input-datetime :property="'event_end'"
                              :value="$event_venue->event_end" />
          </div>
        </div>
      </x-input-container>

      <x-input-container>
        <x-label>
          Website:
        </x-label>
        <x-input-text :property="'event_website'"
                      :value="$event_venue->event_website" />
      </x-input-container>

      {{-- Request specifics --}}
      <x-input-container>
        <x-label>
          Expected Attendees:
        </x-label>
        <x-input-text :property="'expected_attendees'"
                      :value="$event_venue->expected_attendees" />
      </x-input-container>

      <x-input-container>
        <x-label>
          Requested Characters:
        </x-label>
        <x-input-text :property="'requested_characters'"
                      :value="$event_venue->requested_characters" />
      </x-input-container>

      <x-input-container>
        <x-label>
          Requested Character Types:
        </x-label>
        <x-input-text :property="'requested_character_types'"
                      :multiline="true"
                      :value="$event_venue->requested_character_types" />
      </x-input-container>

      {{-- Venue amenities / permissions --}}
      <x-input-container>
        <x-label>
          Secure Staging Area:
        </x-label>
        <x-input-yesno :property="'secure_staging_area'"
                       :value="$event_venue->secure_staging_area" />
      </x-input-container>

      <x-input-container>
        <x-label>
          Allow Blasters:
        </x-label>
        <x-input-yesno :property="'allow_blasters'"
                       :value="$event_venue->allow_blasters" />
      </x-input-container>

      <x-input-container>
        <x-label>
          Allow Props:
        </x-label>
        <x-input-yesno :property="'allow_props'"
                       :value="$event_venue->allow_props" />
      </x-input-container>

      <x-input-container>
        <x-label>
          Parking Available:
        </x-label>
        <x-input-yesno :property="'parking_available'"
                       :value="$event_venue->parking_available" />
      </x-input-container>

      <x-input-container>
        <x-label>
          Accessible:
        </x-label>
        <x-input-yesno :property="'accessible'"
                       :value="$event_venue->accessible" />
      </x-input-container>

      <x-input-container>
        <x-label>
          Amenities:
        </x-label>
        <x-input-text :property="'amenities'"
                      :multiline="true"
                      :value="$event_venue->amenities" />
      </x-input-container>

      {{-- Misc --}}
      <x-input-container>
        <x-label>
          Comments:
        </x-label>
        <x-input-text :property="'comments'"
                      :multiline="true"
                      :value="$event_venue->comments" />
      </x-input-container>

      <x-input-container>
        <x-label>
          Referred By:
        </x-label>
        <x-input-text :property="'referred_by'"
                      :value="$event_venue->referred_by" />
      </x-input-container>

      <x-submit-container>
        <x-submit-button>Save</x-submit-button>
        <x-link-button-cancel :url="route('admin.events.list')" />
      </x-submit-container>
    </form>

  </x-card>

</x-slim-container>

@endsection