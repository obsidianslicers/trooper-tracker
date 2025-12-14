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
        <div class="alert alert-{{ $notice->type }} mt-2">
            <h5>
                <i class="fa fa-fw fa-solid {{ $icons[$notice->type->value] }}"></i>
                {{ $notice->type->description() }}
            </h5>
            <p class="fw-bold py-2 border-top border-bottom">
                {{ $notice->title }}
            </p>
            <div class="row">
                <div class="col">
                    {!! Str::markdown($notice->message) !!}
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <button class="btn btn-outline-secondary btn-sm float-end"
                            type="submit"
                            hx-post="{{ route('account.notices-htmx', ['notice' => $notice]) }}"
                            hx-swap="outerHTML"
                            hx-indicator="#transmission-bar-notices">
                        <i class="fas fa-book-reader pe-2"></i>
                        Mark as Read
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>