@props(['organizations', 'planned' => true])

@if($planned)
    <i class="small text-muted">
        <span class="text-uppercase">Planned:</span>
        {{ $organizations }}
        <i class="fa fa-fw fa-circle-info"
           title="Final organization credit is set when the trooper's attendance is confirmed."></i>
    </i>
@else
    <i class="small text-muted">
        {{ $organizations }}
    </i>
@endif
