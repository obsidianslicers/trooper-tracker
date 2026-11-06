@if($linked_troops->isNotEmpty())
    @php
        $linked_count = $linked_troops->count();
        $linked_hours = \TroopTracker\Plugins\Federation\Support\LinkedTroopsOnPage::hours($linked_troops);
    @endphp
    <table class="table table-sm mt-3 mb-0">
        <tbody>
            <tr class="small">
                <td class="text-muted border-0">
                    <span data-bs-toggle="tooltip"
                          data-bs-title="Troops recorded by other garrisons' trackers this trooper linked">
                        Linked Trackers
                    </span>
                </td>
                <td class="text-end fw-bold border-0">
                    <x-number-format :value="$linked_count" />
                    <span class="text-muted fw-normal">troops</span>
                </td>
            </tr>
            <tr class="small">
                <td class="text-muted border-0">All-Time Troops</td>
                <td class="text-end fw-bold border-0">
                    <x-number-format :value="(int) ($service_summary['total_shifts'] ?? 0) + $linked_count" />
                </td>
            </tr>
            <tr class="small">
                <td class="text-muted border-0">All-Time Hours</td>
                <td class="text-end fw-bold border-0">
                    <x-number-format :value="(float) ($service_summary['total_hours'] ?? 0) + $linked_hours" />
                </td>
            </tr>
        </tbody>
    </table>
@endif
