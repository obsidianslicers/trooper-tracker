@extends('layouts.base')

@section('page-title', 'Event Charity')

@section('content')

    <x-transmission-bar :id="'event'" />

    @include('pages.admin.events.tabs', compact('event'))

    <x-slim-container>

        <x-card>
            <form method="POST"
                  novalidate="novalidate">
                @csrf

                <x-input-container>
                    <x-label>
                        Event:
                    </x-label>
                    <x-input-text :property="'event_name'"
                                  :disabled="true"
                                  :value="$event->name" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Event Status:
                    </x-label>
                    <x-input-text :property="'event_status'"
                                  :disabled="true"
                                  :value="to_title($event->status->name)" />
                </x-input-container>

                @foreach($shifts as $shift)
                    @if(!$loop->first)
                        <hr class="my-3">
                    @endif

                    <div class="mb-2 text-muted small fw-semibold">
                        {{ $shift->shift_starts_at->format('D, M j, Y') }}
                        &mdash;
                        {{ $shift->shift_starts_at->format('g:i A') }} to {{ $shift->shift_ends_at->format('g:i A') }}
                    </div>

                    <div class="d-flex gap-2 align-items-start">
                        <div class="flex-grow-1">
                            <label class="form-label small text-muted mb-1">Charity</label>
                            <x-input-text :property="'shifts.' . $shift->id . '.charity_name'"
                                          :value="$shift->charity_name"
                                          class="form-control-sm" />
                        </div>
                        <div style="width:75px">
                            <label class="form-label small text-muted mb-1">Hours</label>
                            <input type="text"
                                   name="shifts[{{ $shift->id }}][charity_hours]"
                                   id="shifts.{{ $shift->id }}.charity_hours"
                                   class="form-control form-control-sm"
                                   placeholder="{{ (int) $shift->shift_starts_at->diffInHours($shift->shift_ends_at) }}"
                                   value="{{ $shift->charity_hours ?? '' }}">
                        </div>
                        <div style="width:90px">
                            <label class="form-label small text-muted mb-1">Direct $</label>
                            <x-input-text :property="'shifts.' . $shift->id . '.charity_direct_funds'"
                                          :value="$shift->charity_direct_funds"
                                          class="form-control-sm" />
                        </div>
                        <div style="width:90px">
                            <label class="form-label small text-muted mb-1">Indirect $</label>
                            <x-input-text :property="'shifts.' . $shift->id . '.charity_indirect_funds'"
                                          :value="$shift->charity_indirect_funds"
                                          class="form-control-sm" />
                        </div>
                        <div class="flex-grow-1">
                            <label class="form-label small text-muted mb-1">Notes</label>
                            <x-input-text :property="'shifts.' . $shift->id . '.charity_notes'"
                                          :value="$shift->charity_notes"
                                          :multiline="true"
                                          :rows="1"
                                          style="resize:none;overflow:hidden"
                                          x-on:input="$el.style.height='auto';$el.style.height=$el.scrollHeight+'px'"
                                          class="form-control-sm" />
                        </div>
                    </div>
                @endforeach

                <div class="mt-3">
                <x-submit-container>
                    <x-submit-button>Update</x-submit-button>
                    <x-link-button-cancel :url="route('admin.events.charity', compact('event'))" />
                </x-submit-container>
                </div>

            </form>
        </x-card>

    </x-slim-container>

@endsection
