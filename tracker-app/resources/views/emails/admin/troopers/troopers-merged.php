@extends('layouts.email')

@section('message')
<p>
    Esteemed Administrators,
</p>

<p>
    We wish to inform you that two troopers have been successfully merged in the system.
</p>

<p>
    Source Trooper: {{ $source_trooper->legal_name }} (#{{ $source_trooper->id }})
    <br />
    {{ $source_trooper->display_name }}
    <br />
    {{ $source_trooper->email }}
</p>

<p>
    Target Trooper: {{ $target_trooper->legal_name }} (#{{ $target_trooper->id }})
    <br />
    {{ $target_trooper->display_name }}
    <br />
    {{ $target_trooper->email }}
</p>

<p>
    In a stunning display of administrative chaos, two separate trooper accounts
    have collided within our systems and—through the miracle of Imperial
    bureaucracy—been forcibly merged into a single, allegedly "unified" profile.
    Whether this fusion creates a stronger trooper or just a more confused one
    remains, of course, your problem to determine.
</p>

<p>
    The Empire extends its gratitude for your continued vigilance, your heroic
    tolerance for data anomalies, and your unwavering commitment to pretending
    our systems are fully operational.
</p>

@include('emails.inc.signature')

@endsection