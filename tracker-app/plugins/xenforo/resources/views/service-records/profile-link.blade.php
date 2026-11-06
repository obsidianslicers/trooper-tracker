@php
    $xenforo_profile_url = app(\TroopTracker\Plugins\Xenforo\Donations\XenforoProfile::class)
        ->profileUrl($trooper);
@endphp
@if($xenforo_profile_url)
    <a href="{{ $xenforo_profile_url }}"
       class="btn btn-outline-secondary"
       target="_blank"
       rel="noopener noreferrer">
        <i class="fa fa-fw fa-comments"></i>
        Forum Profile
    </a>
@endif
