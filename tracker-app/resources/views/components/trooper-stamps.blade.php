@props(['model'])

@isset($model)

    <hr />

    <div class="row">
        <div class="col-12 text-end">
            @if (isset($model->deleted_at))
                <span class="text-muted">
                    {{ __('components.soft deleted') }}
                    @isset($model->deleted_id)
                        {{ __('components.by') }}
                        <a href="{{ route('admin.troopers.profile', ['trooper' => $model->deleted_id]) }}">
                            {{ $model->deleted_by->legal_name }}
                        </a>
                    @endisset
                    {{ $model->deleted_at->diffForHumans() }}
                </span>
            @elseif ($model->created_at == $model->updated_at)
                <span class="text-muted">
                    {{ __('components.created') }} {{ $model->created_id }}
                    @isset($model->created_id)
                        {{ __('components.by') }}
                        <a href="{{ route('admin.troopers.profile', ['trooper' => $model->created_id]) }}">
                            {{ $model->created_by->legal_name }}
                        </a>
                    @endisset
                    {{ $model->created_at->diffForHumans() }}
                </span>
            @else
                <span class="text-muted">
                    {{ __('components.updated') }}
                    @isset($model->updated_id)
                        {{ __('components.by') }}
                        <a href="{{ route('admin.troopers.profile', ['trooper' => $model->updated_id]) }}">
                            {{ $model->updated_by->legal_name }}
                        </a>
                    @endisset
                    {{ $model->updated_at->diffForHumans() }}
                </span>
            @endif
        </div>
    </div>
@endisset