@extends('layouts.base')

@section('page-title', 'Event Shifts')

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

                <x-table id="shifts">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Date</th>
                            <th>Starts At</th>
                            <th>Ends At</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shifts as $shift)
                            @php
                                $hasStationRows = $shift->event_shift_stations->isNotEmpty() || !empty(old('shifts.' . $shift->id . '.stations'));
                            @endphp
                            <tr>
                                <td>
                                    {{ $shift->shift_starts_at->format('D') }}
                                </td>
                                <td>
                                    <x-input-date :property="'shifts.' . $shift->id . '.date'"
                                                  :value="$shift->shift_starts_at->format('Y-m-d')"
                                                  :disabled="!$event->is_active"
                                                  class="form-control-sm" />
                                </td>
                                <td>
                                    <x-input-time :property="'shifts.' . $shift->id . '.starts_at'"
                                                  :value="$shift->shift_starts_at->format('H:i')"
                                                  :disabled="!$event->is_active"
                                                  class="form-control-sm" />
                                </td>
                                <td>
                                    <x-input-time :property="'shifts.' . $shift->id . '.ends_at'"
                                                  :value="$shift->shift_ends_at->format('H:i')"
                                                  :disabled="!$event->is_active"
                                                  class="form-control-sm" />
                                </td>
                                <td>
                                    <x-input-select :property="'shifts.' . $shift->id . '.status'"
                                                    :options="\App\Enums\EventStatus::toArray()"
                                                    :value="$shift->status->value"
                                                    :disabled="!$event->is_active"
                                                    class="form-select-sm" />
                                    @if($event->is_active && !$hasStationRows)
                                        <button type="button"
                                                class="btn btn-sm btn-link text-muted px-0 mt-1"
                                                onclick="showStationEditor({{ $shift->id }}, true)">
                                            <i class="fa fa-fw fa-plus me-1"></i>
                                            Stations
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            <tr id="station-editor-row-{{ $shift->id }}"
                                class="{{ $hasStationRows ? '' : 'd-none' }}">
                                    <td colspan="5" class="pt-2 pb-3 border-top-0">
                                        <div class="station-editor ms-md-5">
                                            <div class="station-editor__header">
                                                <div class="d-flex align-items-start gap-2">
                                                    <span class="station-editor__icon" aria-hidden="true">
                                                        <i class="fa fa-fw fa-map-marker"></i>
                                                    </span>
                                                    <div>
                                                        <div class="fw-semibold">Stations</div>
                                                    @if($hasStationRows)
                                                            <div class="text-muted small mt-1">
                                                                <i class="fa fa-fw fa-grip-vertical me-1"></i>
                                                                Drag stations to change their display order.
                                                            </div>
                                                    @endif
                                                    </div>
                                                </div>
                                                @if($event->is_active)
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-success flex-shrink-0"
                                                            onclick="addStationRow({{ $shift->id }})">
                                                        <i class="fa fa-fw fa-plus me-1"></i>
                                                        Add Station
                                                    </button>
                                                @endif
                                            </div>
                                            <div class="station-list station-sortable-tbody"
                                                 id="stations-{{ $shift->id }}"
                                                 data-reorder-url="{{ route('admin.events.shifts.stations.reorder', ['event' => $event, 'event_shift' => $shift]) }}">
                                                    @foreach($shift->event_shift_stations->sortBy([['sequence', 'asc'], ['name', 'asc']]) as $station)
                                                        <div data-id="{{ $station->id }}" class="station-item">
                                                            <button type="button" class="station-drag-handle" title="Drag to reorder" aria-label="Drag to reorder">
                                                                <i class="fa fa-fw fa-grip-vertical"></i>
                                                            </button>
                                                            <div class="station-field station-field--name">
                                                                <label class="form-label small mb-1" for="shifts.{{ $shift->id }}.stations.{{ $station->id }}.name">Station name</label>
                                                                <x-input-text :property="'shifts.' . $shift->id . '.stations.' . $station->id . '.name'"
                                                                              :value="$station->name"
                                                                              :disabled="!$event->is_active"
                                                                              class="form-control-sm" />
                                                            </div>
                                                            <div class="station-field station-field--capacity">
                                                                <label class="form-label small mb-1" for="shifts.{{ $shift->id }}.stations.{{ $station->id }}.troopers_allowed">Capacity</label>
                                                                <x-input-text :property="'shifts.' . $shift->id . '.stations.' . $station->id . '.troopers_allowed'"
                                                                              :value="$station->troopers_allowed"
                                                                              :disabled="!$event->is_active"
                                                                              class="form-control-sm" />
                                                            </div>
                                                            <div class="station-item__action">
                                                                @if($event->is_active)
                                                                    <button type="button"
                                                                            class="btn btn-sm btn-outline-danger station-remove"
                                                                            hx-post="{{ route('admin.events.shifts.stations.remove', ['event' => $event, 'event_shift' => $shift, 'event_shift_station' => $station]) }}"
                                                                            hx-confirm="Remove station {{ $station->name }}?"
                                                                            hx-trigger="click"
                                                                            title="Remove station">
                                                                        <i class="fa fa-fw fa-trash"></i>
                                                                        <span class="visually-hidden">Remove {{ $station->name }}</span>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                    @if(old('shifts.' . $shift->id . '.stations'))
                                                        @foreach(old('shifts.' . $shift->id . '.stations') as $key => $data)
                                                            @if($key < 0)
                                                                <div class="station-item">
                                                                    <span class="station-drag-placeholder"></span>
                                                                    <div class="station-field station-field--name">
                                                                        <label class="form-label small mb-1" for="shifts.{{ $shift->id }}.stations.{{ $key }}.name">Station name</label>
                                                                        <x-input-text :property="'shifts.' . $shift->id . '.stations.' . $key . '.name'"
                                                                                      :value="$data['name'] ?? ''"
                                                                                      class="form-control-sm" />
                                                                    </div>
                                                                    <div class="station-field station-field--capacity">
                                                                        <label class="form-label small mb-1" for="shifts.{{ $shift->id }}.stations.{{ $key }}.troopers_allowed">Capacity</label>
                                                                        <x-input-text :property="'shifts.' . $shift->id . '.stations.' . $key . '.troopers_allowed'"
                                                                                     :value="$data['troopers_allowed'] ?? ''"
                                                                                     class="form-control-sm" />
                                                                    </div>
                                                                    <div class="station-item__action">
                                                                        <button type="button"
                                                                                class="btn btn-sm btn-outline-danger station-remove"
                                                                                onclick="this.closest('.station-item').remove()"
                                                                                title="Remove station">
                                                                            <i class="fa fa-fw fa-trash"></i>
                                                                            <span class="visually-hidden">Remove station</span>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    @endif
                                            </div>
                                        </div>
                                    </td>
                            </tr>
                        @endforeach

                        {{-- Rows created dynamically (negative indices) --}}
                        @if(old('shifts'))
                            @foreach(old('shifts') as $key => $data)
                                @if($key < 0)
                                    <tr>
                                        <td></td>
                                        <td>
                                            <x-input-date :property="'shifts.' . $key . '.date'"
                                                          :value="$data['date'] ?? ''"
                                                          class="form-control-sm" />
                                        </td>
                                        <td>
                                            <x-input-time :property="'shifts.' . $key . '.starts_at'"
                                                          :value="$data['starts_at'] ?? ''"
                                                          class="form-control-sm" />
                                        </td>
                                        <td>
                                            <x-input-time :property="'shifts.' . $key . '.ends_at'"
                                                          :value="$data['ends_at'] ?? ''"
                                                          class="form-control-sm" />
                                        </td>
                                        <td></td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif
                    </tbody>
                    @if($event->is_active)
                        <tfoot>
                            <tr>
                                <td colspan="5"
                                    class="text-end">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-success"
                                            onclick="addShiftRow('#shifts')">
                                        <i class="fa fa-fw fa-plus"></i>
                                        Add Shift
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </x-table>

                @if($event->is_active)
                    <x-submit-container>
                        <x-submit-button>Update</x-submit-button>
                        <x-link-button-cancel :url="route('admin.events.shifts', compact('event'))" />
                    </x-submit-container>
                @endif

            </form>
        </x-card>

    </x-slim-container>

@endsection

@section('page-script')
    <script type="text/javascript">
        function addShiftRow(tableSelector, rowCount = 1) {
            const tbody = document.querySelector(`${tableSelector} tbody`);
            if (!tbody) return;

            // Find the last date input with a value
            const dateInputs = [...tbody.querySelectorAll('input.date-picker')];
            const lastWithValue = dateInputs.reverse().find(input => input.value && input.value.trim() !== '');
            const defaultDate = lastWithValue ? lastWithValue.value : '';

            // Count how many "negative" rows already exist
            const existingNegRows = [...tbody.querySelectorAll("input")]
                .map(input => input.name.match(/shifts\[(\-?\d+)\]/))
                .filter(Boolean)
                .map(match => parseInt(match[1], 10))
                .filter(n => n < 0);

            let minNeg = existingNegRows.length ? Math.min(...existingNegRows) : 0;

            const newIndex = minNeg - 1;

            const tr = document.createElement("tr");

            // Day display
            const tdDay = document.createElement("td");
            tr.appendChild(tdDay);

            // Date input
            const tdDate = document.createElement("td");
            tdDate.innerHTML = `
          <input type="text"
                 name="shifts[${newIndex}][date]"
                 id="shifts.${newIndex}.date"
                 class="form-control date-picker form-control-sm"
                 value="${defaultDate}"
                 readonly="readonly">`;
            tr.appendChild(tdDate);

            // Starts_at input
            const tdStart = document.createElement("td");
            tdStart.innerHTML = `
          <input type="time"
                 name="shifts[${newIndex}][starts_at]"
                 id="shifts.${newIndex}.starts_at"
                 class="form-control form-control-sm">`;
            tr.appendChild(tdStart);

            // Ends_at input
            const tdEnd = document.createElement("td");
            tdEnd.innerHTML = `
          <input type="time"
                 name="shifts[${newIndex}][ends_at]"
                 id="shifts.${newIndex}.ends_at"
                 class="form-control form-control-sm">`;
            tr.appendChild(tdEnd);

            // Status (empty for new rows)
            const tdStatus = document.createElement("td");
            tr.appendChild(tdStatus);

            tbody.appendChild(tr);

            document.body.dispatchEvent(new Event('tracker:date-picker:added'));
        }

        function addStationRow(shiftId) {
            const stationList = document.querySelector(`#stations-${shiftId}`);
            if (!stationList) return;

            const existingNegRows = [...stationList.querySelectorAll("input")]
                .map(input => input.name.match(/stations\]\[(\-?\d+)\]/))
                .filter(Boolean)
                .map(match => parseInt(match[1], 10))
                .filter(n => n < 0);

            let minNeg = existingNegRows.length ? Math.min(...existingNegRows) : 0;
            const newIndex = minNeg - 1;

            const stationItem = document.createElement("div");
            stationItem.className = "station-item";
            stationItem.innerHTML = `
                <span class="station-drag-placeholder"></span>
                <div class="station-field station-field--name">
                    <label class="form-label small mb-1" for="shifts.${shiftId}.stations.${newIndex}.name">Station name</label>
                    <input type="text"
                           name="shifts[${shiftId}][stations][${newIndex}][name]"
                           id="shifts.${shiftId}.stations.${newIndex}.name"
                           class="form-control form-control-sm">
                </div>
                <div class="station-field station-field--capacity">
                    <label class="form-label small mb-1" for="shifts.${shiftId}.stations.${newIndex}.troopers_allowed">Capacity</label>
                    <input type="text"
                           name="shifts[${shiftId}][stations][${newIndex}][troopers_allowed]"
                           id="shifts.${shiftId}.stations.${newIndex}.troopers_allowed"
                           class="form-control form-control-sm">
                </div>
                <div class="station-item__action">
                    <button type="button"
                            class="btn btn-sm btn-outline-danger station-remove"
                            onclick="this.closest('.station-item').remove()"
                            title="Remove station">
                        <i class="fa fa-fw fa-trash"></i>
                        <span class="visually-hidden">Remove station</span>
                    </button>
                </div>`;

            stationList.appendChild(stationItem);
            stationItem.querySelector('input')?.focus();
        }

        function showStationEditor(shiftId, addInitialRow = false) {
            const row = document.querySelector(`#station-editor-row-${shiftId}`);
            if (row) row.classList.remove('d-none');

            const stationList = document.querySelector(`#stations-${shiftId}`);
            if (addInitialRow && stationList && stationList.querySelectorAll('.station-item').length === 0) {
                addStationRow(shiftId);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Sortable === 'undefined') return;

            document.querySelectorAll('.station-sortable-tbody').forEach(function (stationList) {
                Sortable.create(stationList, {
                    handle: '.station-drag-handle',
                    animation: 150,
                    onEnd: function () {
                        var ordered_ids = Array.from(stationList.querySelectorAll('.station-item[data-id]'))
                            .map(function (row) { return row.dataset.id; });

                        fetch(stationList.dataset.reorderUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ ids: ordered_ids }),
                        });
                    },
                });
            });
        });
    </script>
@endsection
