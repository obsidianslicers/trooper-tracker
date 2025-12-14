@props(['label', 'url', 'active' => false])
<a href="{{ $url }}"
   class="btn btn-outline-primary {{ $active ? ' active' : '' }}">
    {{ $value ?? $slot }}
</a>