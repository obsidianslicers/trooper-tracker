@auth
    @if(auth()->user()->deletion_requested_at)
        <div class="alert alert-danger alert-dismissible d-flex align-items-center justify-content-between mt-2 gap-3">
            <div>
                <strong>{{ __('layout.Account deletion scheduled.') }}</strong>
                {!! __('layout.Your account will be permanently deleted on :date.', [
                    'date' => '<strong>'.e(auth()->user()->deletion_requested_at->addDays(30)->localFormat('M j, Y')).'</strong>',
                ]) !!}
            </div>
            <form method="POST"
                  action="{{ route('account.cancel-deletion') }}"
                  class="mb-0">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="btn btn-sm btn-outline-light text-nowrap">
                    {{ __('layout.Cancel Deletion') }}
                </button>
            </form>
        </div>
    @endif
@endauth