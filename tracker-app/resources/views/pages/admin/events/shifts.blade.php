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
                            <th></th>
                        </tr>
                    </thead>

                    @foreach($shifts as $shift)
                        <tbody x-data="{ open: false }">
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
                                <td>
                                    <button type="button"
                                            class="btn btn-sm"
                                            :class="open ? 'btn-outline-success' : 'btn-outline-secondary'"
                                            @click="open = !open"
                                            title="Charity details">
                                        <i class="fa fa-fw fa-dollar-sign"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr x-show="open"
                                x-cloak>
                                <td colspan="6"
                                    class="pb-2 pt-0">
                                    <div class="d-flex gap-2 align-items-start px-1">
                                        <div class="flex-grow-1">
                                            <div class="form-label small text-muted mb-1">Charity</div>
                                            <x-input-text :property="'shifts.' . $shift->id . '.charity_name'"
                                                          :value="$shift->charity_name"
                                                          :disabled="!$event->is_active"
                                                          class="form-control-sm" />
                                        </div>
                                        <div style="width:80px">
                                            <div class="form-label small text-muted mb-1">Hours</div>
                                            <x-input-text :property="'shifts.' . $shift->id . '.charity_hours'"
                                                          :value="$shift->charity_hours"
                                                          :disabled="!$event->is_active"
                                                          class="form-control-sm" />
                                        </div>
                                        <div style="width:90px">
                                            <div class="form-label small text-muted mb-1">Direct $</div>
                                            <x-input-text :property="'shifts.' . $shift->id . '.charity_direct_funds'"
                                                          :value="$shift->charity_direct_funds"
                                                          :disabled="!$event->is_active"
                                                          class="form-control-sm" />
                                        </div>
                                        <div style="width:90px">
                                            <div class="form-label small text-muted mb-1">Indirect $</div>
                                            <x-input-text :property="'shifts.' . $shift->id . '.charity_indirect_funds'"
                                                          :value="$shift->charity_indirect_funds"
                                                          :disabled="!$event->is_active"
                                                          class="form-control-sm" />
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="form-label small text-muted mb-1">Notes</div>
                                            <x-input-text :property="'shifts.' . $shift->id . '.charity_notes'"
                                                          :value="$shift->charity_notes"
                                                          :multiline="true"
                                                          :rows="1"
                                                          :disabled="!$event->is_active"
                                                          style="resize:none;overflow:hidden"
                                                          x-on:input="$el.style.height='auto';$el.style.height=$el.scrollHeight+'px'"
                                                          class="form-control-sm" />
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    @endforeach

                    {{-- Rows created dynamically (negative indices) --}}
                    @if(old('shifts'))
                        @foreach(old('shifts') as $key => $data)
                            @if($key < 0)
                                <tbody x-data="{ open: false }">
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
                                        <td>
                                            <button type="button"
                                                    class="btn btn-sm"
                                                    :class="open ? 'btn-outline-success' : 'btn-outline-secondary'"
                                                    @click="open = !open"
                                                    title="Charity details">
                                                <i class="fa fa-fw fa-dollar-sign"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr x-show="open"
                                        x-cloak>
                                        <td colspan="6"
                                            class="pb-2 pt-0">
                                            <div class="d-flex gap-2 align-items-start px-1">
                                                <div class="flex-grow-1">
                                                    <div class="form-label small text-muted mb-1">Charity</div>
                                                    <x-input-text :property="'shifts.' . $key . '.charity_name'"
                                                                  :value="$data['charity_name'] ?? ''"
                                                                  class="form-control-sm" />
                                                </div>
                                                <div style="width:80px">
                                                    <div class="form-label small text-muted mb-1">Hours</div>
                                                    <x-input-text :property="'shifts.' . $key . '.charity_hours'"
                                                                  :value="$data['charity_hours'] ?? ''"
                                                                  class="form-control-sm" />
                                                </div>
                                                <div style="width:90px">
                                                    <div class="form-label small text-muted mb-1">Direct $</div>
                                                    <x-input-text :property="'shifts.' . $key . '.charity_direct_funds'"
                                                                  :value="$data['charity_direct_funds'] ?? ''"
                                                                  class="form-control-sm" />
                                                </div>
                                                <div style="width:90px">
                                                    <div class="form-label small text-muted mb-1">Indirect $</div>
                                                    <x-input-text :property="'shifts.' . $key . '.charity_indirect_funds'"
                                                                  :value="$data['charity_indirect_funds'] ?? ''"
                                                                  class="form-control-sm" />
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="form-label small text-muted mb-1">Notes</div>
                                                    <x-input-text :property="'shifts.' . $key . '.charity_notes'"
                                                                  :value="$data['charity_notes'] ?? ''"
                                                                  :multiline="true"
                                                                  :rows="1"
                                                                  style="resize:none;overflow:hidden"
                                                                  x-on:input="$el.style.height='auto';$el.style.height=$el.scrollHeight+'px'"
                                                                  class="form-control-sm" />
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            @endif
                        @endforeach
                    @endif

                    @if($event->is_active)
                        <tfoot>
                            <tr>
                                <td colspan="6"
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
        function addShiftRow(tableSelector) {
            const table = document.querySelector(tableSelector);
            if (!table) return;

            // Find the last date input with a value across all tbodies
            const dateInputs = [...table.querySelectorAll('input.date-picker')];
            const lastWithValue = dateInputs.reverse().find(input => input.value && input.value.trim() !== '');
            const defaultDate = lastWithValue ? lastWithValue.value : '';

            // Count how many "negative" rows already exist
            const existingNegRows = [...table.querySelectorAll('input')]
                .map(input => input.name.match(/shifts\[(\-?\d+)\]/))
                .filter(Boolean)
                .map(match => parseInt(match[1], 10))
                .filter(n => n < 0);

            const minNeg = existingNegRows.length ? Math.min(...existingNegRows) : 0;
            const newIndex = minNeg - 1;

            const tbodyEl = document.createElement('tbody');
            tbodyEl.setAttribute('x-data', '{ open: false }');

            // Row 1: core shift fields
            const tr1 = document.createElement('tr');
            tr1.innerHTML = `
                <td></td>
                <td>
                    <input type="text"
                           name="shifts[${newIndex}][date]"
                           id="shifts.${newIndex}.date"
                           class="form-control date-picker form-control-sm"
                           value="${defaultDate}"
                           readonly="readonly">
                </td>
                <td>
                    <input type="time"
                           name="shifts[${newIndex}][starts_at]"
                           id="shifts.${newIndex}.starts_at"
                           class="form-control form-control-sm">
                </td>
                <td>
                    <input type="time"
                           name="shifts[${newIndex}][ends_at]"
                           id="shifts.${newIndex}.ends_at"
                           class="form-control form-control-sm">
                </td>
                <td></td>
                <td>
                    <button type="button"
                            class="btn btn-sm"
                            :class="open ? 'btn-outline-success' : 'btn-outline-secondary'"
                            @click="open = !open"
                            title="Charity details">
                        <i class="fa fa-fw fa-dollar-sign"></i>
                    </button>
                </td>`;
            tbodyEl.appendChild(tr1);

            // Row 2: charity fields (hidden by default)
            const tr2 = document.createElement('tr');
            tr2.setAttribute('x-show', 'open');
            tr2.setAttribute('x-cloak', '');
            tr2.innerHTML = `
                <td colspan="6" class="pb-2 pt-0">
                    <div class="d-flex gap-2 align-items-start px-1">
                        <div class="flex-grow-1">
                            <div class="form-label small text-muted mb-1">Charity</div>
                            <input type="text"
                                   name="shifts[${newIndex}][charity_name]"
                                   id="shifts.${newIndex}.charity_name"
                                   class="form-control form-control-sm">
                        </div>
                        <div style="width:80px">
                            <div class="form-label small text-muted mb-1">Hours</div>
                            <input type="number" min="0"
                                   name="shifts[${newIndex}][charity_hours]"
                                   id="shifts.${newIndex}.charity_hours"
                                   class="form-control form-control-sm">
                        </div>
                        <div style="width:90px">
                            <div class="form-label small text-muted mb-1">Direct $</div>
                            <input type="number" min="0"
                                   name="shifts[${newIndex}][charity_direct_funds]"
                                   id="shifts.${newIndex}.charity_direct_funds"
                                   class="form-control form-control-sm">
                        </div>
                        <div style="width:90px">
                            <div class="form-label small text-muted mb-1">Indirect $</div>
                            <input type="number" min="0"
                                   name="shifts[${newIndex}][charity_indirect_funds]"
                                   id="shifts.${newIndex}.charity_indirect_funds"
                                   class="form-control form-control-sm">
                        </div>
                        <div class="flex-grow-1">
                            <div class="form-label small text-muted mb-1">Notes</div>
                            <textarea name="shifts[${newIndex}][charity_notes]"
                                      id="shifts.${newIndex}.charity_notes"
                                      class="form-control form-control-sm"
                                      rows="1"
                                      style="resize:none;overflow:hidden"
                                      x-on:input="$el.style.height='auto';$el.style.height=$el.scrollHeight+'px'"></textarea>
                        </div>
                    </div>
                </td>`;
            tbodyEl.appendChild(tr2);

            // Insert before tfoot if it exists, otherwise append to table
            const tfoot = table.querySelector('tfoot');
            if (tfoot) {
                table.insertBefore(tbodyEl, tfoot);
            } else {
                table.appendChild(tbodyEl);
            }

            Alpine.initTree(tbodyEl);

            document.body.dispatchEvent(new Event('tracker:date-picker:added'));
        }
    </script>
@endsection
