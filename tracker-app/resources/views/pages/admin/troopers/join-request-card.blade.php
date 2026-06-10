<div id="join-request-{{ $join_request->id }}"
     class="card h-100 shadow-sm">
    <div class="card-header text-uppercase">
        {{ $join_request->trooper->display_name }}
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-4">Legal Name:</dt>
            <dd class="col-8">{{ $join_request->trooper->legal_name }}</dd>
            <dt class="col-4">Display Name:</dt>
            <dd class="col-8">{{ $join_request->trooper->display_name }}</dd>
            <dt class="col-4">Email:</dt>
            <dd class="col-8">{{ $join_request->trooper->email }}</dd>
            <dt class="col-4">Phone:</dt>
            <dd class="col-8">{{ $join_request->trooper->phone ?? 'n/a' }}</dd>
            <dt class="col-4">Primary Club:</dt>
            <dd class="col-8">{{ $join_request->primaryOrganization->name }}</dd>
            <dt class="col-4">Requested Unit:</dt>
            <dd class="col-8">
                {{ $join_request->organization->parent?->name ? $join_request->organization->parent->name . ' — ' : '' }}{{ $join_request->organization->name }}
            </dd>
            @if($join_request->identifier)
                <dt class="col-4">Identifier:</dt>
                <dd class="col-8">{{ $join_request->identifier }}</dd>
            @endif
            @if($join_request->denial_reason)
                <dt class="col-4">Denial Reason:</dt>
                <dd class="col-8">{{ $join_request->denial_reason }}</dd>
            @endif
        </dl>
    </div>
    <div class="card-footer d-flex justify-content-between">
        @if($join_request->status === \App\Enums\JoinRequestStatus::APPROVED)
            <div class="alert alert-success d-flex align-items-center gap-2 mb-0 py-2 px-3 w-100">
                <i class="fa-solid fa-circle-check"></i>
                <div>
                    <div class="fw-semibold">Approved</div>
                    <div class="small">Membership request is active.</div>
                </div>
            </div>
        @elseif($join_request->status === \App\Enums\JoinRequestStatus::DENIED)
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-0 py-2 px-3 w-100">
                <i class="fa-solid fa-circle-xmark"></i>
                <div>
                    <div class="fw-semibold">Denied</div>
                    <div class="small">Membership request was not approved.</div>
                </div>
            </div>
        @else
            <div x-data="{ denying: false }"
                 class="w-100">
                <div x-show="!denying"
                     class="d-flex justify-content-between">
                    <button type="button"
                            class="btn btn-danger btn-sm"
                            x-on:click="denying = true">
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
                </div>
                <form x-show="denying"
                      x-cloak
                      class="pt-2"
                      hx-post="{{ route('admin.troopers.join-requests.deny-htmx', compact('join_request')) }}"
                      hx-swap="outerHTML"
                      hx-select="#join-request-{{ $join_request->id }}"
                      hx-target="#join-request-{{ $join_request->id }}"
                      hx-indicator="#transmission-bar-join-requests">
                    @csrf
                    <textarea name="denial_reason"
                              class="form-control form-control-sm mb-2"
                              rows="2"
                              placeholder="Reason for denial (optional)..."></textarea>
                    <div class="d-flex justify-content-between">
                        <button type="button"
                                class="btn btn-secondary btn-sm"
                                x-on:click="denying = false">
                            Cancel
                        </button>
                        <button type="submit"
                                class="btn btn-danger btn-sm">
                            Confirm Deny
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
