@props(['days' => null, 'allTime' => false])

<div class="btn-group shadow-sm">
    @if($allTime)
        <a href="{{ request()->fullUrlWithoutQuery('days') }}"
           class="btn btn-sm btn-outline-dark
                  {{ $days === null ? 'active' : '' }}">
            {{ __('components.All Time') }}
        </a>
    @endif
    @foreach([30, 60, 90, 180, 360] as $interval)
        <a href="{{ request()->fullUrlWithQuery(['days' => $interval]) }}"
           class="btn btn-sm btn-outline-dark
                  {{ $days == $interval ? 'active' : '' }}">
            {{ __('components.:daysD', ['days' => $interval]) }}
        </a>
    @endforeach
</div>
