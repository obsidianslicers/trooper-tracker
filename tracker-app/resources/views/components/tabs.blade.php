@props(['id' => 'tabs'])

<div class="overflow-auto tabs-scroll-mobile">
    <ul class="nav nav-tabs flex-nowrap flex-md-wrap w-max-content w-md-auto"
        id="{{ $id }}"
        role="tablist">
        {{ $slot }}
    </ul>
    <div class="tab-content mt-3">
        {{ $panes ?? '' }}
    </div>
</div>