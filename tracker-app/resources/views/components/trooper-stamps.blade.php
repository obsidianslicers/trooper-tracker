@props(['model'])

@isset($model)

    <hr />

    <div class="row">
        <div class="col-12 text-end">

            @if (isset($model->deleted_at))
                <span class="text-muted">
                    soft deleted
                    @isset($model->deleted_id)w
                        by {{ $model->deleted_by->display_name }}
                    @endisset
                    {{ $model->deleted_at->diffForHumans() }}
                </span>
            @elseif ($model->created_at == $model->updated_at)
                <span class="text-muted">
                    created
                    @isset($model->created_id)
                        by {{ $model->created_by->display_name }}
                    @endisset
                    {{ $model->created_at->diffForHumans() }}
                </span>
            @else
                <span class="text-muted">
                    updated
                    @isset($model->updated_id)
                        by {{ $model->updated_by->display_name }}
                    @endisset
                    {{ $model->updated_at->diffForHumans() }}
                </span>
            @endif
        </div>
    </div>
@endisset