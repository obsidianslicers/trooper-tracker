{{-- A trooper's real name in gray next to their username on the leaderboards (under it on phones),
     when it says something more than the username --}}
@if(filled($trooper->legal_name) && $trooper->legal_name !== $trooper->display_name)
    <span class="text-muted small d-block d-sm-inline ms-sm-1">{{ $trooper->legal_name }}</span>
@endif
