<x-table>
  <thead>
    <tr>
      <th></th>
      <th>Event Name</th>
      <th class="text-center">Date</th>
      <th>Attended Costume</th>
    </tr>
  </thead>
  <tbody>
    @forelse($historical_shifts as $shift)
    <tr>
      <td>
        <x-logo :storage_path="$shift->organization->image_path_sm ?? ''"
                :default_path="'img/icons/organization-32x32.png'"
                :width="32"
                :height="32" />
      </td>
      <td>
        {{ $shift->event->name }}
      </td>
      <td class="text-center text-nowrap">
        <x-date-format :value="$shift->shift_date"
                       :format="'M j, Y'" />
      </td>
      <td>
        @foreach($shift->event_troopers as $event_trooper)
        @if(isset($event_trooper->organization_costume) && $event_trooper->organization_costume->name != 'N/A')
        {{ $event_trooper->organization_costume->full_name }}
        @else
        <span class="text-muted">N/A</span>
        @endif
        @endforeach
      </td>
    </tr>
    @empty
    <tr>
      <td colspan="4">
        No Troops ... Yet!
      </td>
    </tr>
    @endforelse
  </tbody>
</x-table>