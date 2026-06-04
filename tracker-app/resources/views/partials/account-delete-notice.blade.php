@auth
    @if(auth()->user()->deletion_requested_at)
        <div class="alert alert-danger alert-dismissible d-flex align-items-center justify-content-between mt-2 gap-3">
            <div>
                <strong>Account deletion scheduled.</strong>
                Your account will be permanently deleted on
                <strong>{{ auth()->user()->deletion_requested_at->addDays(30)->toFormattedDateString() }}</strong>.
            </div>
            <form method="POST"
                  action="{{ route('account.delete.cancel') }}"
                  class="mb-0">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="btn btn-sm btn-outline-light text-nowrap">
                    Cancel Deletion
                </button>
            </form>
        </div>
    @endif
@endauth