@if(filled(config('plugins.xenforo.base_url')))
    <x-nav-link :url="config('plugins.xenforo.base_url')">
        Forum
    </x-nav-link>
@endif
