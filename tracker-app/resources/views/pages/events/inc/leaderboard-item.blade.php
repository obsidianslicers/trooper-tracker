@props(['label', 'count', 'count_of', 'max'])

<div class="d-flex justify-content-between align-items-center mb-1">
    <div class="d-flex align-items-center">
        <span class="fw-bold small text-uppercase">
            {{ $label }}
        </span>
    </div>
    <span class="text-primary fw-bold">
        {{ $count }}
        <small class="text-muted"
               style="font-size: 0.6rem;">
            {{ $count_of }}
        </small>
    </span>
</div>
<div class="progress"
     style="height: 6px;">
    <div class="progress-bar bg-success"
         role="progressbar"
         style="width: {{ ($count / $max) * 100 }}%"></div>
</div>