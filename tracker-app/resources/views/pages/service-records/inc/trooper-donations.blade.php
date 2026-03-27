<x-table>
    <thead>
        <tr>
            <th>Date</th>
            <th class="text-end">Amount</th>
        </tr>
    </thead>
    <tbody>
        @forelse($all_donations as $donation)
            <tr>
                <td>
                    <x-date-format :value="$donation->created_at"
                                   :format="'M j, Y'" />
                </td>
                <td class="text-end">
                    $ <x-number-format :value="$donation->amount"
                                     :decimals="2" />
                </td>
            </tr>
        @empty
            <x-table-empty :colspan="2">
                No Donations ... Yet!
            </x-table-empty>
        @endforelse
    </tbody>
    @if($all_donations->isNotEmpty())
        <tfoot>
            <tr class="fw-bold">
                <td>Total</td>
                <td class="text-end">
                    $ <x-number-format :value="$all_donations->sum('amount')"
                                     :decimals="2" />
                </td>
            </tr>
        </tfoot>
    @endif
</x-table>
