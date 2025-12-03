@props(['url'])

<a class="btn btn-sm btn-outline-primary float-end"
   href="{{ $url }}">
  <i class="fa fa-fw fa-add"></i>
  {{ $slot }}
</a>