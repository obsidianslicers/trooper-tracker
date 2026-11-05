@php
    $xenforo_group_banners = app(\TroopTracker\Plugins\Xenforo\Donations\XenforoProfile::class)
        ->groupBanners($trooper);
@endphp
@if($xenforo_group_banners->isNotEmpty())
    <div class="mt-2 service-record-banners">
        @foreach($xenforo_group_banners as $banner)
            {!! $banner['banner_text'] !!}
        @endforeach
    </div>
@endif
