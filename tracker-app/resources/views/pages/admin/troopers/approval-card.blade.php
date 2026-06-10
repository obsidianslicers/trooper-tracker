<div id="trooper-approval-{{ $trooper->id }}"
     class="card h-100 shadow-sm">
    <div class="card-header text-uppercase d-flex justify-content-between align-items-center">
        {{ $trooper->display_name }}
        @if($trooper->is_visitor)
            <span class="badge bg-info ms-2">Visitor</span>
        @endif
    </div>
    <div class="card-body">
        @if($trooper->is_visitor && $trooper->visitor_expires_at !== null && $trooper->visitor_expires_at->isPast())
            <x-message type="warning"
                       icon="fa-solid fa-clock-rotate-left"
                       class="mb-3">
                Access renewal &mdash; previous access expired on {{ $trooper->visitor_expires_at->format('M j, Y') }}.
            </x-message>
        @endif
        <dl class="row mb-0">
            <dt class="col-md-4">Legal Name:</dt>
            <dd class="col-md-8">{{ $trooper->legal_name }}</dd>
            <dt class="col-md-4">Display Name:</dt>
            <dd class="col-md-8">{{ $trooper->display_name }}</dd>
            <dt class="col-md-4">Email:</dt>
            <dd class="col-md-8 text-wrap">{{ $trooper->email }}</dd>
            <dt class="col-md-4">Phone:</dt>
            <dd class="col-md-8">{{ $trooper->phone ?? 'n/a' }}</dd>
            <dt class="col-md-4">Role:</dt>
            <dd class="col-md-8">{{ to_title($trooper->membership_role->name) }}</dd>
            @if($trooper->is_minor)
                <dt class="col-md-4 text-warning fw-bold">Parent/Guardian:</dt>
                <dd class="col-md-8 text-warning">{{ $trooper->guardian->email }}</dd>
                <dt class="col-md-4 text-warning fw-bold">Legal Name:</dt>
                <dd class="col-md-8 text-warning">{{ $trooper->guardian->legal_name }}</dd>
                <dt class="col-md-4 text-warning fw-bold">Display Name:</dt>
                <dd class="col-md-8 text-warning">{{ $trooper->guardian->display_name }}</dd>
            @endif
        </dl>
        <hr />
        @if($trooper->is_active || $trooper->is_denied)
            <p class="text-muted small mb-0">
                Review complete.
            </p>
        {{-- Handlers are not required to select a unit --}}
        @elseif($trooper->membership_role == \App\Enums\MembershipRole::HANDLER && $trooper->join_requests->isEmpty())
            <x-message type="warning"
                       icon="fa-solid fa-triangle-exclamation"
                       class="mb-3">
                This trooper is registered as a Handler and not required to select a unit.
            </x-message>
        @else
            @forelse($trooper->join_requests as $join_request)
                <div class="border rounded p-2 mb-2">
                    <dl class="row mb-0 small">
                        <dt class="col-4">Primary Club:</dt>
                        <dd class="col-8">
                            @if($join_request->primaryOrganization->requires_guardian)
                                <i class="fa-solid fa-shield-halved text-warning my-1 me-1"></i>
                            @endif
                            {{ $join_request->primaryOrganization->name }}
                        </dd>
                        <dt class="col-4">Requested Unit:</dt>
                        <dd class="col-8">
                            {{ $join_request->organization->parent?->name ? $join_request->organization->parent->name . ' — ' : '' }}{{ $join_request->organization->name }}
                        </dd>
                        <dt class="col-4">Identifier:</dt>
                        <dd class="col-8 mb-0">{{ $join_request->identifier ?? 'n/a' }}</dd>
                    </dl>
                </div>
            @empty
                <x-message type="info"
                           icon="fa-solid fa-circle-info"
                           class="mb-0">
                    No organization membership was requested.
                </x-message>
            @endforelse
        @endif

    </div>
    <div class="card-footer d-flex justify-content-between">
        @if($trooper->is_active)
            <div class="alert alert-success d-flex align-items-center gap-2 mb-0 py-2 px-3 w-100">
                <i class="fa-solid fa-circle-check"></i>
                <div>
                    <div class="fw-semibold">Approved</div>
                    <div class="small">Trooper account is active.</div>
                </div>
            </div>
        @elseif($trooper->is_denied)
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-0 py-2 px-3 w-100">
                <i class="fa-solid fa-circle-xmark"></i>
                <div>
                    <div class="fw-semibold">Denied</div>
                    <div class="small">Trooper account was not approved.</div>
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
                            hx-post="{{ route('admin.troopers.approve-htmx', compact('trooper')) }}"
                            hx-swap="outerHTML"
                            hx-select="#trooper-approval-{{ $trooper->id }}"
                            hx-target="#trooper-approval-{{ $trooper->id }}"
                            hx-indicator="#transmission-bar-approvals">
                        Approve
                    </button>
                </div>
                <form x-show="denying"
                      x-cloak
                      class="pt-2"
                      hx-post="{{ route('admin.troopers.deny-htmx', compact('trooper')) }}"
                      hx-swap="outerHTML"
                      hx-select="#trooper-approval-{{ $trooper->id }}"
                      hx-target="#trooper-approval-{{ $trooper->id }}"
                      hx-indicator="#transmission-bar-approvals">
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
