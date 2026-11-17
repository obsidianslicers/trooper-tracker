@if(filled(config('plugins.xenforo.base_url')))
    <x-nav-link :url="config('plugins.xenforo.base_url')">
        {{ __('xenforo::layout.Forum') }}
    </x-nav-link>
@endif
