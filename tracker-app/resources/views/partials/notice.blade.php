@php
    $icons = [
        'success' => 'fa-circle-check',
        'info' => 'fa-circle-info',
        'warning' => 'fa-circle-exclamation',
        'danger' => 'fa-circle-xmark',
    ];
@endphp
<div class="row">
    <div class="col">
        <div class="alert alert-{{ $notice->type->value }} position-relative mt-2 shadow-sm">
            <h5 class="alert-heading">
                <i class="fa fa-fw fa-solid {{ $icons[$notice->type->value] }}"></i>
                {{ $notice->type->description() }}
            </h5>
            <button class="btn-close position-absolute top-0 end-0 m-2 p-2 text-white"
                    role="alert"
                    type="submit"
                    hx-post="{{ route('account.notices-htmx', ['notice' => $notice]) }}"
                    hx-swap="delete"
                    hx-target="closest .alert"
                    hx-indicator="#transmission-bar-notices">
            </button>
            <p class="fw-bold py-2 border-white border-top border-bottom">
                {{ $notice->title }}
            </p>
            <div class="row">
                <div class="col">
                    {!! Str::markdown($notice->message) !!}
                </div>
            </div>
        </div>
    </div>
</div>