@extends('layouts.email')

@section('message')

    <h1 style="margin-top: 0;">⚠️ Fix408: Ambiguous Club Memberships Need Review</h1>

    <p>Hi {{ $trooper->display_name }},</p>

    <p>
        The <code>Fix408</code> false-membership correction found
        <strong>{{ count($ambiguous_memberships) }}</strong>
        membership{{ count($ambiguous_memberships) === 1 ? '' : 's' }} that match the same pattern
        as known false memberships (a stray legacy identifier with no matching club permission),
        but the trooper's club membership is still marked <strong>active</strong> today — they may
        have legitimately joined for real after the original import. These were
        <strong>not</strong> changed automatically. Please confirm whether each trooper actually
        belongs to the listed club; if not, their membership and any credit/achievements tied to it
        should be corrected manually.
    </p>

    <table style="width:100%; border-collapse:collapse; margin-top:12px;">
        <thead>
            <tr>
                <th style="text-align:left; border-bottom:1px solid #ddd; padding:6px;">Trooper</th>
                <th style="text-align:left; border-bottom:1px solid #ddd; padding:6px;">Club</th>
                <th style="text-align:left; border-bottom:1px solid #ddd; padding:6px;">Status</th>
                <th style="text-align:left; border-bottom:1px solid #ddd; padding:6px;">Credited Shifts</th>
                <th style="text-align:left; border-bottom:1px solid #ddd; padding:6px;">Achievements</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ambiguous_memberships as $row)
                <tr>
                    <td style="border-bottom:1px solid #eee; padding:6px;">{{ $row['trooper_name'] }}</td>
                    <td style="border-bottom:1px solid #eee; padding:6px;">{{ $row['organization_name'] }}</td>
                    <td style="border-bottom:1px solid #eee; padding:6px;">{{ $row['membership_status'] }}</td>
                    <td style="border-bottom:1px solid #eee; padding:6px;">{{ $row['event_trooper_row_count'] }}</td>
                    <td style="border-bottom:1px solid #eee; padding:6px;">{{ $row['achievement_row_count'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
