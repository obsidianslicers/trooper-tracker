@props(['days' => 30])

<div class="btn-group shadow-sm">
    @foreach([30, 60, 90, 180, 360] as $interval)
        <a href="{{ request()->fullUrlWithQuery(['days' => $interval]) }}"
           class="btn btn-outline-dark text-uppercase small fw-bold {{ ($days ?? 30) == $interval ? 'active' : '' }}">
            {{ $interval }}D
        </a>
    @endforeach
</div>