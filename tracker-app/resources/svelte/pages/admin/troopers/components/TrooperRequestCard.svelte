<script lang="ts">
    import CreateButton from "$lib/components/ui/buttons/CreateButton.svelte";
    import DeleteButton from "$lib/components/ui/buttons/DeleteButton.svelte";
    import type { TrooperRequest } from "../models/vms";
    import { PendingTrooperRequestViewModel } from "../models/vms";

    interface Props {
        request: TrooperRequest;
    }

    let { request }: Props = $props();

    let vm = new PendingTrooperRequestViewModel(request);
</script>

<div class="card h-100">
    <div class="card-header text-uppercase d-flex justify-content-between">
        {vm.request.trooper.legal_name}
        <span class="badge bg-secondary ms-2">
            {vm.request.primary_organization.name}
        </span>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-4">Legal Name:</dt>
            <dd class="col-8">{vm.request.trooper.legal_name}</dd>
            <dt class="col-4">Display Name:</dt>
            <dd class="col-8">{vm.request.trooper.display_name}</dd>
            <dt class="col-4">Email:</dt>
            <dd class="col-8">{vm.request.trooper.email}</dd>
            <dt class="col-4">Phone:</dt>
            <dd class="col-8">{vm.request.trooper.phone ?? "n/a"}</dd>
            <dt class="col-4">Primary Organization:</dt>
            <dd class="col-8">{vm.request.primary_organization.name}</dd>
            {#if vm.request.organization.id !== vm.request.primary_organization.id}
                <dt class="col-4">Requested Unit:</dt>
                <dd class="col-8">
                    {#if vm.request.organization.parent_name}
                        {vm.request.organization.parent_name} —
                    {/if}
                    {vm.request.organization.name}
                </dd>
            {/if}
            {#if vm.request.identifier}
                <dt class="col-4">Identifier:</dt>
                <dd class="col-8">{vm.request.identifier}</dd>
            {/if}
            {#if vm.request.denial_reason}
                <dt class="col-4">Denial Reason:</dt>
                <dd class="col-8">{vm.request.denial_reason}</dd>
            {/if}
        </dl>
        <!--
        <div hx-get="{{ route('admin.troopers.trooper-requests.member-lookup', $trooper_request) }}"
             hx-trigger="load"
             hx-swap="outerHTML">
            <div class="text-center text-muted py-1 small">
                <i class="fa-solid fa-spinner fa-spin me-1"></i> Checking member status&hellip;
            </div>
        </div>
    -->
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
                            hx-post="{{ route('admin.troopers.trooper-requests.approve-htmx', compact('trooper_request')) }}"
                            hx-swap="outerHTML"
                            hx-select="#join-request-{{ $trooper_request->id }}"
                            hx-target="#join-request-{{ $trooper_request->id }}"
                            hx-indicator="#transmission-bar-trooper-requests">
                        Approve
                    </button>
                </div>
                <form x-show="denying"
                      x-cloak
                      class="pt-2"
                      hx-post="{{ route('admin.troopers.trooper-requests.deny-htmx', compact('trooper_request')) }}"
                      hx-swap="outerHTML"
                      hx-select="#join-request-{{ $trooper_request->id }}"
                      hx-target="#join-request-{{ $trooper_request->id }}"
                      hx-indicator="#transmission-bar-trooper-requests">
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
        
        -->
    </div>
</div>
