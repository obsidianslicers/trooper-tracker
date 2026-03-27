<div id="trooper-approval-{{ $trooper->id }}"
     class="card h-100 shadow-sm">
    <div class="card-header text-uppercase">
        {{ $trooper->display_name }}
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-4">Legal Name:</dt>
            <dd class="col-8">{{ $trooper->legal_name }}</dd>
            <dt class="col-4">Display Name:</dt>
            <dd class="col-8">{{ $trooper->display_name }}</dd>
            <dt class="col-4">Email:</dt>
            <dd class="col-8">{{ $trooper->email }}</dd>
            @if($trooper->is_minor)
                <dt class="col-4 text-danger fw-bold">Parent/Guardian:</dt>
                <dd class="col-8">{{ $trooper->guardian->email }}</dd>
                <dt class="col-4 text-danger fw-bold">Legal Name:</dt>
                <dd class="col-8">{{ $trooper->guardian->legal_name }}</dd>
                <dt class="col-4 text-danger fw-bold">Display Name:</dt>
                <dd class="col-8">{{ $trooper->guardian->display_name }}</dd>
            @endif
            <dt class="col-4">Phone:</dt>
            <dd class="col-8">{{ $trooper->phone ?? 'n/a' }}</dd>
            <dt class="col-4">Role:</dt>
            <dd class="col-8">{{ to_title($trooper->membership_role->name) }}</dd>
        </dl>
        <hr />
        {{-- ONLY HANDLERS CAN GET OUT OF PICKING A UNIT --}}
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
                <thead>
                    <tr>
                        <th colspan="2">Selected Unit</th>
                    </tr>
                </thead>
                @foreach($trooper->trooper_assignments as $assignment)
                    <tr>
                        <td colspan="2">
                            <i class="fa fa-fw"></i>
                            {{ $assignment->organization->parent->name }}
                            -
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
            <button class="btn btn-danger btn-sm"
                    type="button"
                    hx-post="{{ route('admin.troopers.deny-htmx', compact('trooper')) }}"
                    hx-swap="outerHTML"
                    hx-select="#trooper-approval-{{ $trooper->id }}"
                    hx-target="#trooper-approval-{{ $trooper->id }}"
                    hx-indicator="#transmission-bar-approvals">
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
        @endif
    </div>
</div>