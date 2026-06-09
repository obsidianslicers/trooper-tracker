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
        {{-- Handlers and Visitors are not required to select a unit --}}
        @if($trooper->membership_role == \App\Enums\MembershipRole::HANDLER && $trooper->trooper_assignments->isEmpty())
            <x-message type="warning"
                       icon="fa-solid fa-triangle-exclamation"
                       class="mb-3">
                This trooper is registered as a Handler and not required to select a unit.
            </x-message>
        @else
            <x-table>
                <thead>
                    <tr>
                        <th>Organization</th>
                        <th>ID</th>
                    </tr>
                </thead>
                @foreach($trooper->organizations as $organization)
                    <tr>
                        <td>
                            <i class="fa fa-fw"></i>
                            @if($organization->requires_guardian)
                                <i class="fa-solid fa-shield-halved text-warning my-1 me-1"></i>
                            @endif
                            {{ $organization->name }}
                        </td>
                        <td>
                            {{ $organization->pivot->identifier }}
                            @if($organization->pivot->synchronized_at != null)
                                <i class="fa fa-fw fa-circle-check text-success float-end my-1"></i>
                            @endif
                        </td>
                    </tr>
                @endforeach
                @if($trooper->is_visitor)
                    <tr>
                        <th colspan="2">Selected Unit</th>
                    </tr>
                    <tr>
                        <td colspan="2"
                            class="text-muted fst-italic">
                            <i class="fa fa-fw fa-circle-info"></i>
                            Visitor &mdash; no unit required.
                        </td>
                    </tr>
                @else
                    <tr>
                        <th colspan="2">Selected Unit</th>
                    </tr>
                    @foreach($trooper->trooper_assignments as $assignment)
                        <tr>
                            <td colspan="2">
                                <i class="fa fa-fw"></i>
                                @if($assignment->organization->type != \App\Enums\OrganizationType::ORGANIZATION && $assignment->organization->parent !== null)
                                    {{ $assignment->organization->parent->name }}
                                    -
                                @endif
                                {{ $assignment->organization->name }}
                            </td>
                        </tr>
                        @foreach($trooper->trooper_assignments->filter(fn($a) => $a->organization->parent_id == $assignment->organization_id) as $reg_asg)
                            <tr>
                                <td class="ps-{{ $reg_asg->organization->depth }}">
                                    <i class="fa fa-fw fa-caret-right"></i>
                                    {{ $reg_asg->organization->name }}
                                </td>
                                <td>{{ $reg_asg->membership_role }}</td>
                            </tr>
                            @foreach($trooper->trooper_assignments->filter(fn($a) => $a->organization->parent_id == $reg_asg->organization_id) as $unit_asg)
                                <tr>
                                    <td class="ps-{{ $unit_asg->organization->depth }}">
                                        <i class="fa fa-fw fa-caret-right"></i>
                                        <i class="fa fa-fw fa-caret-right"></i>
                                        {{ $unit_asg->organization->name }}
                                    </td>
                                    <td>{{ $unit_asg->membership_role }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    @endforeach
                @endif
            </x-table>
        @endif

    </div>
    <div class="card-footer d-flex justify-content-between">
        @if($trooper->is_active)
            <div class="w-100">
                <x-message type="success"
                           icon="fa-brands fa-empire"
                           class="w-100">
                    Let the Trooping begin!
                </x-message>
            </div>
        @elseif($trooper->is_denied)
            <div class="w-100">
                <x-message type="danger">
                    Denied Trooper Status
                </x-message>
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