@props(['trooper', 'award'])

<div class="badge bg-primary text-white me-2 mb-2 d-inline-flex align-items-center">
    <input type="hidden" name="trooper_ids[]" value="{{ $trooper->id }}">
    <span>{{ $trooper->name }}</span>
    <button type="button" class="btn btn-sm btn-close btn-close-white ms-2" onclick="this.parentElement.remove()"></button>
</div>