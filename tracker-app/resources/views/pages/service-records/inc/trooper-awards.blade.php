<div class="table-responsive">
    <x-table>
        <thead>
            <tr>
                <th>{{ __('service-records.Award') }}</th>
                <th>{{ __('service-records.Date') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($awards as $award)
                <tr>
                    <td>
                        {{ $award->award->name }}
                    </td>
                    <td>
                        <x-date-format :value="$award->award_date"
                                       :format="'M j, Y'" />
                    </td>
                </tr>
            @empty
                <x-table-empty :colspan="2">
                    {{ __('service-records.No Awards ... Yet!') }}
                </x-table-empty>
            @endforelse
        </tbody>
    </x-table>
</div>