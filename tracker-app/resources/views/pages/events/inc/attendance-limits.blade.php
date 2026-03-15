<x-section-title>Unit Quotas</x-section-title>
<x-table>
    <thead>
        <tr>
            <th>Organization</th>
            <th class="text-end">Troopers</th>
            <th class="text-end">Handlers</th>
            <th class="text-end"></th>
        </tr>
    </thead>
    <tbody>
        @foreach($event->organizations as $organization)
            <tr>
                <td class="text-nowrap">
                    {{ $organization->name }}
                </td>
                <td class="text-end">
                    @if($organization->pivot->can_attend)
                        <x-number-format :value="$organization->pivot->troopers_allowed" />
                    @endif
                </td>
                <td class="text-end">
                    @if($organization->pivot->can_attend)
                        <x-number-format :value="$organization->pivot->handlers_allowed" />
                    @endif
                </td>
                <td class="text-end">
                    <x-yes-no class="me-2"
                              :value="$organization->pivot->can_attend ?? false" />
                </td>
            </tr>
        @endforeach
    </tbody>
</x-table>