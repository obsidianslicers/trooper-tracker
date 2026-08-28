<script lang="ts">
    import Alert from "$lib/components/ui/Alert.svelte";
    import CreateButton from "$lib/components/ui/buttons/CreateButton.svelte";
    import DeleteButton from "$lib/components/ui/buttons/DeleteButton.svelte";
    import type { TrooperApproval } from "../models/vms";
    import { PendingTrooperApprovalViewModel } from "../models/vms";

    interface Props {
        approval: TrooperApproval;
    }

    let { approval }: Props = $props();

    let vm = new PendingTrooperApprovalViewModel(approval);
</script>

<div class="card h-100 shadow-sm">
    <div class="card-header text-uppercase d-flex justify-content-between">
        {vm.approval.legal_name}
        {#if vm.approval.is_visitor}
            <span class="badge bg-info ms-2 float-end">Visitor</span>
        {:else}
            <span class="badge bg-secondary ms-2 float-end">
                {vm.approval.membership_role}
            </span>
        {/if}
    </div>
    <div class="card-body">
        {#if vm.visitation_expired}
            <Alert type="warning" icon="fa-solid fa-clock-rotate-left">
                Access renewal, previous access expired
                <b>{vm.approval.visitor_expires_diff_for_humans}</b>.
            </Alert>
        {/if}
        <dl class="row mb-0">
            <dt class="col-md-4">Legal Name:</dt>
            <dd class="col-md-8">{vm.approval.legal_name}</dd>
            <dt class="col-md-4">Display Name:</dt>
            <dd class="col-md-8">{vm.approval.display_name}</dd>
            <dt class="col-md-4">Email:</dt>
            <dd class="col-md-8 text-wrap">{vm.approval.email}</dd>
            <dt class="col-md-4">Phone:</dt>
            <dd class="col-md-8">{vm.approval.phone ?? "n/a"}</dd>
            <dt class="col-md-4">Role:</dt>
            <dd class="col-md-8">{vm.approval.membership_role}</dd>
            {#if vm.approval.is_minor && vm.approval.guardian}
                <dt class="col-md-4 text-warning fw-bold">Parent/Guardian:</dt>
                <dd class="col-md-8 text-warning">
                    {vm.approval.guardian.email}
                </dd>
                <dt class="col-md-4 text-warning fw-bold">Legal Name:</dt>
                <dd class="col-md-8 text-warning">
                    {vm.approval.guardian.legal_name}
                </dd>
                <dt class="col-md-4 text-warning fw-bold">Display Name:</dt>
                <dd class="col-md-8 text-warning">
                    {vm.approval.guardian.display_name}
                </dd>
            {/if}
        </dl>
        <hr />
        {#if vm.approval.trooper_requests.length > 0}
            <h6 class="text-uppercase mb-2">Membership Requests</h6>
            {#each vm.approval.trooper_requests as request}
                <div class="border rounded p-2 mb-2">
                    <dl class="row mb-0 small">
                        <dt class="col-4">Primary Organization:</dt>
                        <dd class="col-8">
                            {#if request.primary_organization.requires_guardian}
                                <i
                                    class="fa-solid fa-shield-halved text-warning my-1 me-1"
                                ></i>
                            {/if}
                            {request.primary_organization.name}
                        </dd>
                        <dt class="col-4">Requested Unit:</dt>
                        <dd class="col-8">
                            {#if request.organization.parent_name}
                                {request.organization.parent_name} —
                            {/if}
                            {request.organization.name}
                        </dd>
                        <dt class="col-4">Identifier:</dt>
                        <dd class="col-8 mb-0">
                            {request.identifier || "n/a"}
                        </dd>
                    </dl>
                    <!--
                <div class="border rounded p-2 mb-2">
                    <div hx-get="{{ route('admin.troopers.trooper-requests.member-lookup', $trooper_request) }}"
                         hx-trigger="load"
                         hx-swap="outerHTML">
                        <div class="text-center text-muted py-1 small">
                            <i class="fa-solid fa-spinner fa-spin me-1"></i>
                            Checking member status&hellip;
                        </div>
                    </div>
                </div>
                    -->
                </div>
            {/each}
        {:else if vm.approval.membership_role === "handler"}
            <Alert type="warning" icon="fa-triangle-exclamation">
                This trooper is registered as a Handler and not required to
                select a unit.
            </Alert>
        {:else}
            <Alert type="info" icon="fa-solid fa-circle-info">
                This trooper has not requested membership in any organization.
            </Alert>
        {/if}
    </div>
    <div class="card-footer d-flex justify-content-between">
        <div class="w-100">
            {#if !vm.denying}
                <div class="d-flex justify-content-between">
                    <DeleteButton
                        label="Deny"
                        outline={false}
                        icon={null}
                        click={() => (vm.denying = true)}
                    />
                    <CreateButton
                        label="Approve"
                        outline={false}
                        icon={null}
                        click={() => (vm.denying = true)}
                    />
                </div>
            {/if}
        </div>
        <!--
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
        -->
    </div>
</div>
