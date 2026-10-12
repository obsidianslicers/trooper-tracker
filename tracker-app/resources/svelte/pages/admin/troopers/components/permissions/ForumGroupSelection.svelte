<script lang="ts">
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputError from "$lib/components/form/InputError.svelte";
    import type { RequestPermissionsViewModel } from "../../models/vms";
    import ForumGroupLabel from "./ForumGroupLabel.svelte";

    interface Props {
        vm: RequestPermissionsViewModel;
    }

    let { vm }: Props = $props();
</script>

{#if !vm.lookup?.has_forum_account}
    <p class="text-muted small">
        This trooper has no linked forum account, so forum groups cannot be requested.
    </p>
{:else}
    <div class="row">
        <div class="col-md-6">
            <InputContainer>
                <h6>Add Forum Groups</h6>
                {#each vm.addable_forum_groups as group (group.id)}
                    <div class="form-check">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            id="add-group-{group.id}"
                            checked={vm.form.forum_group_ids_to_add.includes(group.id)}
                            onchange={() => vm.toggleGroupToAdd(group.id)}
                        />
                        <label class="form-check-label" for="add-group-{group.id}">
                            <ForumGroupLabel {group} />
                        </label>
                    </div>
                {:else}
                    <p class="text-muted small">No other forum groups are available.</p>
                {/each}
                <InputError errors={vm.errors.forum_group_ids_to_add} />
            </InputContainer>
        </div>
        <div class="col-md-6">
            <InputContainer>
                <h6>Remove Forum Groups</h6>
                {#each vm.current_forum_groups as group (group.id)}
                    <div class="form-check">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            id="remove-group-{group.id}"
                            checked={vm.form.forum_group_ids_to_remove.includes(group.id)}
                            onchange={() => vm.toggleGroupToRemove(group.id)}
                        />
                        <label class="form-check-label" for="remove-group-{group.id}">
                            <ForumGroupLabel {group} />
                        </label>
                    </div>
                {:else}
                    <p class="text-muted small">
                        This trooper is not in any requestable forum groups.
                    </p>
                {/each}
                <InputError errors={vm.errors.forum_group_ids_to_remove} />
            </InputContainer>
        </div>
    </div>
{/if}
