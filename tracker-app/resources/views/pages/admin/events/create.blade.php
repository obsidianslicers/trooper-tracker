@extends('layouts.base')

@section('page-title', 'Create Event')

@section('content')

    <x-transmission-bar :id="'event'" />

    <x-slim-container>

        <x-card>
            <form method="POST"
                  novalidate="novalidate"
                  x-data="Admin.Events.eventCreation()">
                @csrf

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

                <x-input-container>
                    <x-label>
                        Email Message (will be parsed based on organization picked above):
                    </x-label>
                    <x-input-text :property="'source'"
                                  :multiline="true"
                                  :placeholder="'Event Name: Name of Event'"
                                  x-model="sourceContent"
                                  x-on:change="parseSource()" />
                </x-input-container>

                <x-input-container>
                    <x-label>Name:</x-label>
                    <x-input-text :property="'name'"
                                  :value="$event->name"
                                  x-model="form.name" />
                </x-input-container>

                <x-submit-container>
                    <x-submit-button>
                        Create
                    </x-submit-button>
                    <x-link-button-cancel :url="route('admin.events.list', ['organization_id' => $event->organization_id])" />
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