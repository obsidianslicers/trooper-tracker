@if ($trooper_costume->trashed() && $trooper_costume->isRevoked())
    <form method="POST"
          class="d-inline"
          action="{{ route('admin.troopers.costumes.undo-revoke', ['trooper_costume' => $trooper_costume->id]) }}"
          onsubmit="return confirm('Undo the revoke? The costume is approved again and club syncs manage it as usual.');">
        @csrf
        <button type="submit"
                class="btn btn-sm btn-outline-secondary">
            {{ __('admin.Undo') }}
        </button>
    </form>
@elseif ($trooper_costume->isPending())
    <form method="POST"
          class="d-inline"
          action="{{ route('admin.troopers.costumes.approve', ['trooper_costume' => $trooper_costume->id]) }}">
        @csrf
        <button type="submit"
                class="btn btn-sm btn-success">
            {{ __('admin.Approve') }}
        </button>
    </form>
    <form method="POST"
          class="d-inline"
          action="{{ route('admin.troopers.costumes.deny', ['trooper_costume' => $trooper_costume->id]) }}">
        @csrf
        <button type="submit"
                class="btn btn-sm btn-outline-danger">
            {{ __('admin.Deny') }}
        </button>
    </form>
@else
    <form method="POST"
          class="d-inline"
          action="{{ route('admin.troopers.costumes.revoke', ['trooper_costume' => $trooper_costume->id]) }}"
          onsubmit="return confirm('Revoke this costume? The trooper can no longer use it for sign-ups, and club syncs will not restore it.');">
        @csrf
        <button type="submit"
                class="btn btn-sm btn-outline-danger">
            {{ __('admin.Revoke') }}
        </button>
    </form>
@endif
