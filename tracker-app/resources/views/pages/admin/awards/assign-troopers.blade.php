@extends('layouts.base')

@section('page-title', 'Assign Award to Troopers')

@section('content')

    <x-slim-container>

        <x-card>
            <form method="POST"
                  novalidate="novalidate">
                @csrf

                <x-input-container>
                    <x-label>
                        Award:
                    </x-label>
                    <x-input-text :property="'award_name'"
                                  :disabled="true"
                                  :value="$award->name" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Organization:
                    </x-label>
                    <x-input-text :property="'organization_name'"
                                  :disabled="true"
                                  :value="$award->organization->name" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Award Date:
                    </x-label>
                    <x-input-date :property="'award_date'"
                                  :value="now()->format('Y-m-d')" />
                </x-input-container>

                <x-modal-picker :label="'Find a Trooper'" />

                <x-input-container>
                    <x-label>Select Troopers:</x-label>

                    {{-- Selected troopers will appear here --}}
                    <div id="selected-troopers"
                         class="d-flex flex-wrap gap-2 mb-2"></div>

                    <button type="button"
                            class="btn btn-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#modal-picker"
                            hx-get="{{ route('pickers.trooper', ['property' => 'trooper_ids']) }}"
                            hx-target="#modal-picker .modal-body"
                            hx-swap="innerHTML">
                        Find a Trooper
                    </button>

                    @if($award->troopers->count() > 0)
                        <div class="mt-3">
                            <small class="text-muted">
                                <strong>Currently awarded to:</strong>
                                @foreach($award->troopers as $trooper)
                                    <span class="badge bg-secondary me-1">{{ $trooper->name }}</span>
                                @endforeach
                            </small>
                        </div>
                    @endif
                </x-input-container>

                <x-submit-container>
                    <x-submit-button>Assign Award</x-submit-button>
                    <x-link-button-cancel :url="route('admin.awards.list-troopers', $award)" />
                </x-submit-container>

            </form>
        </x-card>
    </x-slim-container>

    {{-- Handle trooper selection from picker --}}
    <div class="d-none"
         hx-trigger="trooper:selected from:document"
         hx-get="{{ route('admin.awards.assign-troopers-htmx', $award) }}"
         hx-vals="js:{trooper_id: event.detail.id, trooper_name: event.detail.name}"
         hx-target="#selected-troopers"
         hx-swap="beforeend">
    </div>

@endsection