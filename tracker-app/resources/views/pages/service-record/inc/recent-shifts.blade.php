<x-table>
    <thead>
        <tr>
            <th></th>
            <th>Recent Shifts</th>
            <th class="text-center">Date</th>
            <th>Attended Costume</th>
        </tr>
    </thead>
    <tbody>
        @forelse($recent_shifts as $shift)
            <tr>
                <td>
                    <x-logo :storage_path="$shift->event->organization->image_path_sm ?? ''"
                            :default_path="'img/icons/organization-32x32.png'"
                            :width="32"
                            :height="32" />
                </td>
                <td>
                    <a href="{{ route('events.display', ['event' => $shift->event]) }}">
                        {{ $shift->event->name }}
                    </a>
                </td>
                <td class="text-start text-nowrap">
                    {{ $shift->full_date_display }}
                    <br />
                    <span class="text-muted">
                        {{ $shift->compact_time_display }}
                    </span>
                </td>
                <td class="text-start text-nowrap">
                    @if($shift->event_trooper->is_handler)
                        Handler
                    @else
                        @if($shift->event_trooper->costume != null)
                            <b>
                                {{ $shift->event_trooper->costume->name }}
                            </b>
                            <br />
                            <i class="small text-muted">
                                {{ $shift->event_trooper->costume_organizations }}
                            </i>
                        @endif
                    @endif
                </td>
            </tr>
        @empty
            <x-table-empty :colspan="4">
                No Recent Shifts ... Yet!
            </x-table-empty>
        @endforelse
    </tbody>
</x-table>