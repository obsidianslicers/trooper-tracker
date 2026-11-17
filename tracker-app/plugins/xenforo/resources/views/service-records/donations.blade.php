@php
    $xenforo_donations = app(\TroopTracker\Plugins\Xenforo\Donations\XenforoProfile::class)
        ->upgradeHistory($trooper);
@endphp
@if(!empty($xenforo_donations))
    <h6 class="text-muted text-uppercase small fw-bold mb-2 mt-3">{{ __('xenforo::service-records.Xenforo Donation History') }}</h6>
    <div class="table-responsive">
        <x-table>
            <thead>
                <tr>
                    <th>{{ __('xenforo::service-records.Upgrade') }}</th>
                    <th>{{ __('xenforo::service-records.Start Date') }}</th>
                    <th>{{ __('xenforo::service-records.End Date') }}</th>
                    <th>{{ __('xenforo::service-records.Status') }}</th>
                    <th class="text-end">{{ __('xenforo::service-records.Total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($xenforo_donations as $upgrade)
                    <tr>
                        <td>{{ $upgrade['upgrade_title'] }}</td>
                        <td>
                            @if($upgrade['start_date'])
                                {{ \Carbon\Carbon::createFromTimestamp($upgrade['start_date'])->localFormat('M j, Y') }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if($upgrade['end_date'])
                                {{ \Carbon\Carbon::createFromTimestamp($upgrade['end_date'])->localFormat('M j, Y') }}
                            @else
                                {{ __('xenforo::service-records.Lifetime') }}
                            @endif
                        </td>
                        <td>
                            @if($upgrade['is_active'])
                                <span class="badge bg-success">{{ __('xenforo::service-records.Active') }}</span>
                            @else
                                <span class="badge bg-secondary">{{ __('xenforo::service-records.Expired') }}</span>
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
                    <td colspan="4">{{ __('xenforo::service-records.Total') }}</td>
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
