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
                    @endif
                </x-table>

                @if($event->is_active)
                    <x-submit-container>
                        @if($event->is_active)
                            <x-submit-button>Update</x-submit-button>
                        @endif
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
                .map(input => input.name.match(/shift\[(\-?\d+)\]/))
                .filter(Boolean)
                .map(match => parseInt(match[1], 10))
                .filter(n => n < 0);

            let minNeg = existingNegRows.length ? Math.min(...existingNegRows) : 0;

            const newIndex = minNeg - 1; // next negative index
            minNeg = newIndex;

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

            // status input
            const tdStatus = document.createElement("td");
            tdStatus.innerHTML = ``;
            tr.appendChild(tdStatus);

            tbody.appendChild(tr);

            document.body.dispatchEvent(new Event('tracker:date-picker:added'));
        }
    </script>
@endsection