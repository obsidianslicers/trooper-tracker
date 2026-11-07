@if($linked_troops->isNotEmpty())
    <h6 class="text-muted text-uppercase small fw-bold mt-4 mb-2">
        Troops from the HoloNet
    </h6>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr class="small text-muted">
                    <th>Date</th>
                    <th>Event</th>
                    <th>Costume</th>
                    <th>Tracker</th>
                </tr>
            </thead>
            <tbody>
                @foreach($linked_troops as $troop)
                    <tr class="small">
                        <td class="text-nowrap">
                            <x-date-format :value="$troop->shift_starts_at" />
                        </td>
                        <td>
                            {{ $troop->event_name }}
                            @if($troop->venue)
                                <div class="text-muted">{{ $troop->venue }}</div>
                            @endif
                        </td>
                        <td>
                            {{ $troop->is_handler ? 'Handler' : ($troop->costume_name ?? '-') }}
                        </td>
                        <td>{{ $troop->link->tracker_name }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
