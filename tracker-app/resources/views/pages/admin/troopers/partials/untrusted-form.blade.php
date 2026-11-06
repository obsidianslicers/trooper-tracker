<x-card>
    <h5 class="mb-3">Do not trust</h5>

    @if($trooper->is_untrusted)
        <p class="text-muted">
            Clearing the flag doesn't approve anything already waiting. Those troops stay in
            the Troop Verifications queue.
        </p>
        <form method="POST"
              action="{{ route('admin.troopers.trusted', compact('trooper')) }}">
            @csrf
            <button type="submit"
                    class="btn btn-outline-secondary">
                Clear do not trust
            </button>
        </form>
    @else
        <p class="text-muted">
            A moderator who covers the club will have to confirm each troop this trooper
            confirms before it counts. The trooper only sees that a troop is waiting for a
            moderator.
        </p>
        <form method="POST"
              action="{{ route('admin.troopers.untrusted', compact('trooper')) }}">
            @csrf
            <x-input-container>
                <x-label>
                    Reason (only admins and moderators see this):
                </x-label>
                <x-input-text :property="'untrusted_reason'" />
            </x-input-container>
            <div class="form-check mb-3">
                <input class="form-check-input"
                       type="checkbox"
                       value="1"
                       id="review_costumes"
                       name="review_costumes">
                <label class="form-check-label"
                       for="review_costumes">
                    Send costumes they added themselves back to officer review
                </label>
            </div>
            <button type="submit"
                    class="btn btn-warning">
                Mark do not trust
            </button>
        </form>
    @endif
</x-card>
