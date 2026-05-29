@props(['days' => null, 'allTime' => false])

<div class="btn-group shadow-sm">
    @if($allTime)
        <a href="{{ request()->fullUrlWithoutQuery('days') }}"
           class="btn btn-outline-dark text-uppercase small fw-bold {{ $days === null ? 'active' : '' }}">
            All Time
        </a>
    @endif
    @foreach([30, 60, 90, 180, 360] as $interval)
        <a href="{{ request()->fullUrlWithQuery(['days' => $interval]) }}"
           class="btn btn-outline-dark text-uppercase small fw-bold {{ $days == $interval ? 'active' : '' }}">
            {{ $interval }}D
        </a>
    @endforeach
</div>
