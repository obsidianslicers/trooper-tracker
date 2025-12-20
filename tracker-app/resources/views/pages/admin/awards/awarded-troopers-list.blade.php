<div id="awarded-troopers-list">
    <div class="row mt-3">
        @forelse($troopers as $trooper)
            <div class="col-md-4">
                <div class="form-check">
                    <input class="form-check-input"
                           type="checkbox"
                           checked
                           disabled>
                    <label class="form-check-label">
                        {{ $trooper->name }}
                    </label>
                </div>
            </div>
        @empty
            <p class="text-muted">No troopers have this award yet.</p>
        @endforelse
    </div>
</div>