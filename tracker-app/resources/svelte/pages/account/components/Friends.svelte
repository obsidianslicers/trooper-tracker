<script lang="ts">
    import Alert from "$lib/components/ui/Alert.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import type { Friend } from "../models";
    import { FriendsViewModel } from "../models";

    interface Props {
        friends: Friend[];
    }
    let { friends }: Props = $props();

    let vm = new FriendsViewModel(friends);
</script>

<SlimView>
    <h6 class="mb-3">Friend Accounts Linked to You</h6>

    {#if vm.friends.length === 0}
        <Alert>No friend accounts are currently linked to you.</Alert>
    {:else}
        <table class="table table-striped">
            <tbody>
                {#each vm.friends as friend}
                    <tr>
                        <td>
                            <div class="fw-semibold">
                                <a href={vm.getServiceRecordUrl(friend)}>
                                    {friend.legal_name}
                                </a>
                            </div>
                            <small class="text-muted">
                                {friend.display_name}
                            </small>
                        </td>
                    </tr>
                {/each}
            </tbody>
        </table>
    {/if}
</SlimView>
