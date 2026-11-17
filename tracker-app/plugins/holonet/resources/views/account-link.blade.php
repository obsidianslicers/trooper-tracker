<div class="card mt-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <div class="fw-bold">{{ __('holonet::account.HoloNet') }}</div>
            <div class="small text-muted">
                {{ __('holonet::account.Troop on another garrison\'s tracker too? Link it to see every troop on your service record.') }}
            </div>
        </div>
        <a href="{{ route('plugins.holonet.index') }}"
           class="btn btn-outline-secondary btn-sm">
            {{ __('holonet::account.Open the HoloNet') }}
        </a>
    </div>
</div>
