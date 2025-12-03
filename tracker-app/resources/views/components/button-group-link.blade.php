@props(['label', 'url', 'active'])
<a href="{{ $url }}"
   class="btn btn-outline-primary {{ $active ? ' active' : '' }}">
  {{ $label }}
</a>