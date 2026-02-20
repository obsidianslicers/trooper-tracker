<x-table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        @forelse($donations as $troop)
            <tr>
                <td>
                    <x-date-format :value="$troop->created_at"
                                   :format="'M j, Y'" />
                </td>
                <td>
                    $ <x-number-format :value="$troop->amount"
                                     :decimals="2" />
                </td>
            </tr>
        @empty
            <x-table-empty :colspan="2">
                No Donations ... Yet!
            </x-table-empty>
        @endforelse
    </tbody>
</x-table>