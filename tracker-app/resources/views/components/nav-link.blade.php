@props(['url' => '#', 'active' => false])

<li {{$attributes->class(['nav-item'])}}>
    <a class="nav-link {{ $active ? 'active' : '' }}"
       href="{{ $url }}">
        {{ $slot }}
    </a>
</li>