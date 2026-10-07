@extends('layouts.email')

@section('message')

    <h1 style="margin-top: 0;">⚠️ Fix408: Outstanding Credit Records</h1>

    <p>Hi {{ $trooper->display_name }},</p>

    <p>
        The <code>Fix408</code> false-membership correction finished running, but could not
        determine which organization to credit for <strong>{{ count($outstanding_rows) }}</strong>
        attended record{{ count($outstanding_rows) === 1 ? '' : 's' }} that lost credit to a
        corrected false club membership. Neither current costume approvals / membership nor the
        legacy signup record gave a usable answer, so these require manual review.
    </p>

    <table style="width:100%; border-collapse:collapse; margin-top:12px;">
        <thead>
            <tr>
                <th style="text-align:left; border-bottom:1px solid #ddd; padding:6px;">Trooper</th>
                <th style="text-align:left; border-bottom:1px solid #ddd; padding:6px;">Event</th>
                <th style="text-align:left; border-bottom:1px solid #ddd; padding:6px;">Costume</th>
                <th style="text-align:left; border-bottom:1px solid #ddd; padding:6px;">EventTrooper ID</th>
            </tr>
        </thead>
        <tbody>
            @foreach($outstanding_rows as $row)
                <tr>
                    <td style="border-bottom:1px solid #eee; padding:6px;">{{ $row['trooper_name'] }}</td>
                    <td style="border-bottom:1px solid #eee; padding:6px;">{{ $row['event_name'] }}</td>
                    <td style="border-bottom:1px solid #eee; padding:6px;">{{ $row['costume_name'] ?? '—' }}</td>
                    <td style="border-bottom:1px solid #eee; padding:6px;">{{ $row['event_trooper_id'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
