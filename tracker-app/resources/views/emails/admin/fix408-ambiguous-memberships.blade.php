@extends('layouts.email')

@section('message')

    <h1 style="margin-top: 0;">⚠️ Fix408: Ambiguous Club Memberships Need Review</h1>

    <p>Hi {{ $trooper->display_name }},</p>

    <p>
        The <code>Fix408</code> false-membership correction found
        <strong>{{ count($ambiguous_memberships) }}</strong>
        membership{{ count($ambiguous_memberships) === 1 ? '' : 's' }} with the same pattern as a
        known false membership (a stray legacy identifier with no matching club permission), but
        the club membership is still marked <strong>active</strong> today. These were
        <strong>not</strong> changed automatically, since the trooper may have joined for real
        since the import. Please confirm whether each trooper actually belongs to the listed club,
        and correct their membership and any credit/achievements by hand if not.
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
