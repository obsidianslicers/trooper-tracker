@props(['url'])

<a href="{{ $url }}"
   {{$attributes->class(['btn btn-secondary ms-3 px-4'])}}>
    Cancel
</a>