@php
    $xenforo_donations = app(\TroopTracker\Plugins\Xenforo\Donations\XenforoProfile::class)
        ->upgradeHistory($trooper);
@endphp
@if(!empty($xenforo_donations))
    <h6 class="text-muted text-uppercase small fw-bold mb-2 mt-3">Xenforo Donation History</h6>
    <div class="table-responsive">
        <x-table>
            <thead>
                <tr>
                    <th>Upgrade</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($xenforo_donations as $upgrade)
                    <tr>
                        <td>{{ $upgrade['upgrade_title'] }}</td>
                        <td>
                            @if($upgrade['start_date'])
                                {{ \Carbon\Carbon::createFromTimestamp($upgrade['start_date'])->format('M j, Y') }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if($upgrade['end_date'])
                                {{ \Carbon\Carbon::createFromTimestamp($upgrade['end_date'])->format('M j, Y') }}
                            @else
                                Lifetime
                            @endif
                        </td>
                        <td>
                            @if($upgrade['is_active'])
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Expired</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <x-number-format :value="$upgrade['total_amount'] ?? 0"
                                             :prefix="'$'"
                                             :decimals="2" />
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="4">Total</td>
                    <td class="text-end">
                        <x-number-format :value="collect($xenforo_donations)->sum('total_amount')"
                                         :prefix="'$'"
                                         :decimals="2" />
                    </td>
                </tr>
            </tfoot>
        </x-table>
    </div>
@endif
