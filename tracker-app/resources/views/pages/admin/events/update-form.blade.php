<form method="POST"
      novalidate="novalidate">
  @csrf

  <x-input-container>
    <x-label>
      Hosting Organization:
    </x-label>
    <x-input-text :property="'organization_name'"
                  :disabled="true"
                  :value="$event->organization->name ?? 'Everyone'" />
  </x-input-container>

  <x-input-container>
    <x-label>
      Name:
    </x-label>
    <x-input-text :property="'name'"
                  :value="$event->name" />
  </x-input-container>

  <x-input-container>
    <x-label>
      Event Type:
    </x-label>
    <x-input-select :property="'type'"
                    :options="\App\Enums\EventType::toArray()"
                    :value="$event->type->value" />
  </x-input-container>

  <x-input-container>
    <x-label>
      Status:
    </x-label>
    <x-input-select :property="'status'"
                    :options="\App\Enums\EventStatus::toArray()"
                    :value="$event->status->value" />
  </x-input-container>

  <x-input-container>
    <x-label>
      Limit Organizations:
    </x-label>
    <x-input-yesno :property="'limit_organizations'"
                   :value="$event->limit_organizations"
                   hx-post="{{ route('admin.events.update-htmx',['event'=>$event]) }}"
                   hx-trigger="change"
                   hx-target="#limit-organizations"
                   hx-select="#limit-organizations"
                   hx-indicator="#transmission-bar-event" />
    <x-input-help>
      Can any organization sign up, or should it be restricted to selected organizations? For instance, an
      event with 12 max troops, may be limited to a single unit close to the event to ensure local participants
      can join.
    </x-input-help>
  </x-input-container>

  <div id="limit-organizations">
    @if(old('limit_organizations',$event->limit_organizations ))
    <x-input-container>
      <div class="row">
        <div class="col-6">
          <x-label>
            Troopers Allowed:
          </x-label>
          <x-input-text :property="'troopers_allowed'"
                        :value="$event->troopers_allowed"
                        placeholder="Total Troopers" />
        </div>
        <div class="col-6">
          <x-label>
            Handlers Allowed:
          </x-label>
          <x-input-text :property="'handlers_allowed'"
                        :value="$event->handlers_allowed"
                        placeholder="Total Handlers" />
        </div>
      </div>
    </x-input-container>
    @endif
  </div>

  <x-input-container>
    <div class="row">
      <div class="col-6">
        <x-label>
          Starts:
        </x-label>
        <x-input-datetime :property="'starts_at'"
                          :value="$event->starts_at" />
      </div>
      <div class="col-6">
        <x-label>
          Ends:
        </x-label>
        <x-input-datetime :property="'ends_at'"
                          :value="$event->ends_at" />
      </div>
    </div>
  </x-input-container>

  <x-submit-container>
    <x-submit-button>
      Update
    </x-submit-button>
    <x-link-button-cancel :url="route('admin.events.list')" />
  </x-submit-container>

  <x-trooper-stamps :model="$event" />

</form>