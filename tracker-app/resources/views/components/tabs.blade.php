@props(['id' => 'tabs'])

<div class="mb-3">
  <ul class="nav nav-tabs d-md-flex"
      id="{{ $id }}"
      role="tablist">
    {{ $slot }}
  </ul>
  <div class="tab-content mt-3">
    {{ $panes ?? '' }}
  </div>
</div>