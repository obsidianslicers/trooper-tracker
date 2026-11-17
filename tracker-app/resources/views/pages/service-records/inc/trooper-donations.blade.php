@pluginSlot('service-records.trooper.donations')

<h6 class="text-muted text-uppercase small fw-bold mb-2 mt-3">{{ __('service-records.Donation Records') }}</h6>
<div class="table-responsive">
    <x-table>
        <thead>
            <tr>
                <th>{{ __('service-records.Date') }}</th>
                <th class="text-end">{{ __('service-records.Amount') }}</th>
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
                    {{ __('service-records.No Donations ... Yet!') }}
                </x-table-empty>
            @endforelse
        </tbody>
        @if($all_donations->isNotEmpty())
            <tfoot>
                <tr class="fw-bold">
                    <td>{{ __('service-records.Total') }}</td>
                    <td class="text-end">
                        $ <x-number-format :value="$all_donations->sum('amount')"
                                         :decimals="2" />
                    </td>
                </tr>
            </tfoot>
        @endif
    </x-table>
</div>
