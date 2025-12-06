@props(['label', 'url'])

<span class="px-3 py-2 badge rounded-pill bg-primary">
  <a class="text-white"
     href="{{ $url }}">
    {{ $label }}
    <span class="ps-2">
      <i class="fa fa-fw fa-times"></i>
    </span>
  </a>
</span>