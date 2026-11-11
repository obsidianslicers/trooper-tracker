<script lang="ts">
    import Button from "$lib/components/ui/buttons/Button.svelte";
    import { untrack } from "svelte";
    import { ProfileCostumesViewModel, type ProfileCostumesPageData } from "../../models";

    interface Props {
        costumes: ProfileCostumesPageData;
    }
    let { costumes }: Props = $props();

    let vm = new ProfileCostumesViewModel(untrack(() => costumes));
</script>

<div class="container">
    <h5>Attached Costumes</h5>
    <ul>
        {#each costumes.attached as costume (costume.name)}
            <li>{costume.name} <small class="text-muted">{costume.organizations}</small></li>
        {:else}
            <li class="text-muted">None</li>
        {/each}
    </ul>

    <h5 class="mt-4">Costume Reviews</h5>
    <table class="table table-sm">
        <thead>
            <tr><th>Costume</th><th>Organization</th><th>Status</th><th>By</th><th></th></tr>
        </thead>
        <tbody>
            {#each costumes.reviews as review (review.id)}
                <tr>
                    <td>{review.costume}</td>
                    <td>{review.organization}</td>
                    <td>{review.status.replace("_", " ")}</td>
                    <td>{review.by ?? ""}</td>
                    <td class="text-end">
                        {#if review.can_review}
                            {#if review.status === "pending"}
                                <Button label="Approve" btnclass="btn-success btn-sm" click={() => vm.review(review.id, "approve")} />
                                <Button label="Deny" btnclass="btn-danger btn-sm" click={() => vm.review(review.id, "deny")} />
                            {:else if review.status === "revoked"}
                                <Button label="Undo" btnclass="btn-secondary btn-sm" click={() => vm.review(review.id, "undo-revoke")} />
                            {:else}
                                <Button label="Revoke" btnclass="btn-warning btn-sm" click={() => vm.review(review.id, "revoke")} />
                            {/if}
                        {/if}
                    </td>
                </tr>
            {/each}
        </tbody>
    </table>
</div>
