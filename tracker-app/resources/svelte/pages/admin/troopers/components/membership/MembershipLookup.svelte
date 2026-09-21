<script lang="ts">
    import Badge from "$lib/components/Badge.svelte";
    import Alert from "$lib/components/ui/Alert.svelte";
    import { MembershipLookupViewModel } from "../../models";

    interface Props {
        trooper_request_id: number;
    }
    let { trooper_request_id }: Props = $props();

    let vm = new MembershipLookupViewModel(trooper_request_id);
</script>

{#if vm.performing_lookup}
    <p>
        <i class="fa-solid fa-spinner fa-spin me-3"></i>
        Looking up membership ...
    </p>
{:else}
    {#if vm.lookup}
        {#if vm.lookup.identifier == null}
            <Alert>
                <p>
                    No identifier provided. Unable to perform membership lookup.
                </p>
            </Alert>
        {:else}
            {#if vm.lookup.service_name == null}
                <Alert type="warning">
                    <p>No service Lookup registered provided.</p>
                </Alert>
            {/if}
            {#if vm.lookup.existing_trooper_membership}
                <Alert type="danger">
                    <b>Duplicate Identifier Found</b>
                    <p>
                        <a href={vm.existing_trooper_route} target="_blank">
                            {vm.lookup.existing_trooper_membership.legal_name}
                        </a>
                        currently has <b>{vm.lookup.identifier}</b> registered
                        with
                        <b>{vm.lookup.primary_organization.name}</b>.
                    </p>
                </Alert>
            {/if}
            {#if vm.lookup.service_name === "TheLegionService" && vm.lookup.member}
                <Alert
                    type={vm.lookup.member.is_approved ? "success" : "warning"}
                >
                    {#if vm.lookup.member.thumbnail_url}
                        <img
                            src={vm.lookup.member.thumbnail_url}
                            alt={vm.lookup.member.full_name}
                            class="rounded flex-shrink-0"
                            style="width:40px;height:40px;object-fit:cover;"
                        />
                    {:else}
                        <i
                            class={[
                                "fa-solid fa-user-astronaut fa-xl mt-1 flex-shrink-0",
                                vm.lookup.member.is_approved
                                    ? "text-success"
                                    : "text-warning",
                            ]}
                        ></i>
                    {/if}
                    <div class="flex-grow-1 min-width-0">
                        <div
                            class="d-flex justify-content-between align-items-center flex-wrap gap-1"
                        >
                            <b>
                                {vm.lookup.member.full_name}
                                {vm.lookup.member.formatted_identifier}
                            </b>
                            <div class="d-flex gap-1">
                                {#if vm.lookup.member.status}
                                    <Badge
                                        label={vm.lookup.member.status}
                                        classes={vm.lookup.member.status ===
                                        "Active"
                                            ? "text-bg-success"
                                            : "text-bg-secondary"}
                                    />
                                {/if}
                                {#if vm.lookup.member.standing}
                                    <Badge
                                        label={vm.lookup.member.standing}
                                        classes={vm.lookup.member.standing ===
                                        "Good"
                                            ? "text-bg-success"
                                            : "text-bg-warning"}
                                    />
                                {/if}
                            </div>
                        </div>
                        <div class="text-muted">
                            {vm.lookup.member.formatted_identifier}
                            {#if vm.lookup.member.unit_name}
                                <span> &mdash; </span>
                            {/if}
                            {vm.lookup.member.unit_name}
                        </div>
                        {#if vm.lookup.member.profile_url}
                            <a
                                href={vm.lookup.member.profile_url}
                                target="_blank"
                                rel="noopener noreferrer"
                                class="small"
                            >
                                View Profile
                                <i
                                    class="fa-solid fa-arrow-up-right-from-square fa-xs"
                                ></i>
                            </a>
                        {/if}
                    </div>
                </Alert>
            {/if}
        {/if}
    {/if}
    <!-- 
@else
    <div class="alert alert-warning d-flex gap-2 align-items-start py-2 px-3 mt-2 mb-0 small">
        <i class="fa-solid fa-circle-question mt-1 flex-shrink-0"></i>
        <div>
            <span class="fw-semibold">Not found</span> &mdash;
            No member with identifier <strong>{{ $identifier }}</strong>
            @if($org_name) was found in {{ $org_name }}.@else was found.{/if}
        </div>
    </div>
 -->
{/if}
