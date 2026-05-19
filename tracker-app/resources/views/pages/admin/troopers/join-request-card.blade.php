<div id="join-request-{{ $join_request->id }}"
     class="card h-100 shadow-sm">
    <div class="card-header text-uppercase">
        {{ $join_request->trooper->display_name }}
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-4">Display Name:</dt>
            <dd class="col-8">{{ $join_request->trooper->display_name }}</dd>
            <dt class="col-4">Email:</dt>
            <dd class="col-8">{{ $join_request->trooper->email }}</dd>
            <dt class="col-4">Phone:</dt>
            <dd class="col-8">{{ $join_request->trooper->phone ?? 'n/a' }}</dd>
            <dt class="col-4">Requested Unit:</dt>
            <dd class="col-8">
                {{ $join_request->organization->parent?->name ? $join_request->organization->parent->name . ' — ' : '' }}{{ $join_request->organization->name }}
            </dd>
            @if($join_request->identifier)
                <dt class="col-4">Identifier:</dt>
                <dd class="col-8">{{ $join_request->identifier }}</dd>
            @endif
        </dl>
    </div>
    <div class="card-footer d-flex justify-content-between">
        @if($join_request->membership_status === \App\Enums\MembershipStatus::ACTIVE)
            <div class="w-100">
                <x-message type="success"
                           icon="fa-brands fa-empire"
                           class="w-100">
                    Request Approved!
                </x-message>
            </div>
        @elseif($join_request->membership_status === \App\Enums\MembershipStatus::DENIED)
            <div class="w-100">
                <x-message type="danger">
                    Request Denied
                </x-message>
            </div>
        @else
            <button class="btn btn-danger btn-sm"
                    type="button"
                    hx-post="{{ route('admin.troopers.join-requests.deny-htmx', compact('join_request')) }}"
                    hx-swap="outerHTML"
                    hx-select="#join-request-{{ $join_request->id }}"
                    hx-target="#join-request-{{ $join_request->id }}"
                    hx-indicator="#transmission-bar-join-requests">
                Deny
            </button>
            <button class="btn btn-success btn-sm"
                    type="button"
                    hx-post="{{ route('admin.troopers.join-requests.approve-htmx', compact('join_request')) }}"
                    hx-swap="outerHTML"
                    hx-select="#join-request-{{ $join_request->id }}"
                    hx-target="#join-request-{{ $join_request->id }}"
                    hx-indicator="#transmission-bar-join-requests">
                Approve
            </button>
        @endif
    </div>
</div>
