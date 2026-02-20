<x-table>
    <thead>
        <tr>
            <th>Award</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @forelse($awards as $troop)
            <tr>
                <td>
                    {{ $troop->award->name }}
                </td>
                <td>
                    <x-date-format :value="$troop->award_date"
                                   :format="'M j, Y'" />
                </td>
            </tr>
        @empty
            <x-table-empty :colspan="2">
                No Awards ... Yet!
            </x-table-empty>
        @endforelse
    </tbody>
</x-table>