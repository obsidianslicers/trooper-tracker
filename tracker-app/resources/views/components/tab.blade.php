@props(['label', 'target' => null, 'active' => false])

<li class="nav-item"
    role="presentation">
    <a class="nav-link {{ $active ? 'active' : '' }}"
       href="{{ $target }}"
       {{str_starts_with($target, '#') ? 'data-bs-toggle="tab"' : ''}}>
        {{ $label }}
    </a>
</li>