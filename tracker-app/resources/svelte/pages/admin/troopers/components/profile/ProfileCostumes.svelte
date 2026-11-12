<script lang="ts">
    import ConfirmationModal from "$lib/components/ConfirmationModal.svelte";
    import Button from "$lib/components/ui/buttons/Button.svelte";
    import { untrack } from "svelte";
    import {
        ProfileCostumesViewModel,
        type ProfileCostumesPageData,
    } from "../../models";

    interface Props {
        costumes: ProfileCostumesPageData;
    }
    let { costumes }: Props = $props();

    let vm = new ProfileCostumesViewModel(untrack(() => costumes));
</script>

<div class="container">
    <table class="table table-sm">
        <thead>
            <tr>
                <th>Approved</th>
                <th>Costume</th>
                <th>Organization</th>
                <th>Status</th>
                <th>By</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            {#each costumes as review (review.id)}
                <tr>
                    <td>
                        {#if review.is_approved}
                            <i class="fa fa-fw fa-check text-success"></i>
                        {/if}
                    </td>
                    <td>{review.costume}</td>
                    <td>{review.organization}</td>
                    <td>{review.status.replace("_", " ")}</td>
                    <td>{review.by ?? ""}</td>
                    <td class="text-end">
                        {#if review.can_review}
                            {#if review.status === "pending"}
                                <Button
                                    label="Approve"
                                    btnclass="btn-success btn-sm"
                                    click={() =>
                                        vm.approve(review.id)}
                                />
                                <Button
                                    label="Deny"
                                    btnclass="btn-danger btn-sm"
                                    click={() => vm.deny(review.id)}
                                />
                            {:else if review.status === "revoked"}
                                <Button
                                    label="Undo"
                                    btnclass="btn-secondary btn-sm"
                                    click={() =>
                                        vm.undoRevoke(review.id)}
                                />
                            {:else}
                                <Button
                                    label="Revoke"
                                    btnclass="btn-warning btn-sm"
                                    click={() => vm.revoke(review.id)}
                                />
                            {/if}
                        {/if}
                    </td>
                </tr>
            {/each}
        </tbody>
    </table>
</div>

<ConfirmationModal
    show={vm.is_confirming}
    title={vm.confirm_title}
    message={vm.confirm_message}
    danger={true}
    onConfirm={vm.confirm}
    onCancel={vm.cancel}
/>