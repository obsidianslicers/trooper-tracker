@extends('layouts.email')

@section('message')

    <h1 style="margin-top: 0;">⚠️ Fix406: Outstanding Credit Records</h1>

    <p>Hi {{ $trooper->display_name }},</p>

    <p>
        The <code>Fix406</code> backfill seeder finished running, but could not determine which
        organization to credit for <strong>{{ count($outstanding_rows) }}</strong> attended
        record{{ count($outstanding_rows) === 1 ? '' : 's' }}. TT1.0 signups are only credited from
        their legacy signup, and TT2.0 signups only from the trooper's costume and memberships on
        the shift date — when that evidence doesn't settle it, the record is left uncredited for
        manual review rather than guessed.
    </p>

    <table style="width:100%; border-collapse:collapse; margin-top:12px;">
        <thead>
            <tr>
                <th style="text-align:left; border-bottom:1px solid #ddd; padding:6px;">Trooper</th>
                <th style="text-align:left; border-bottom:1px solid #ddd; padding:6px;">Event</th>
                <th style="text-align:left; border-bottom:1px solid #ddd; padding:6px;">Costume</th>
                <th style="text-align:left; border-bottom:1px solid #ddd; padding:6px;">Reason</th>
                <th style="text-align:left; border-bottom:1px solid #ddd; padding:6px;">EventTrooper ID</th>
            </tr>
        </thead>
        <tbody>
            @foreach($outstanding_rows as $row)
                <tr>
                    <td style="border-bottom:1px solid #eee; padding:6px;">{{ $row['trooper_name'] }}</td>
                    <td style="border-bottom:1px solid #eee; padding:6px;">{{ $row['event_name'] }}</td>
                    <td style="border-bottom:1px solid #eee; padding:6px;">{{ $row['costume_name'] ?? '—' }}</td>
                    <td style="border-bottom:1px solid #eee; padding:6px;">{{ $row['reason'] ?? '—' }}</td>
                    <td style="border-bottom:1px solid #eee; padding:6px;">{{ $row['event_trooper_id'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
