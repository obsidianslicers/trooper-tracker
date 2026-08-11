<script lang="ts">
    import Alert from "$lib/components/ui/Alert.svelte";
    import type { Friend } from "$lib/domains/account";
    import { FriendsViewModel } from "$lib/domains/account";

    interface Props {
        friends: Friend[];
    }
    let { friends }: Props = $props();

    let vm = new FriendsViewModel(friends);
</script>

{#if vm.friends.length === 0}
    <Alert>No friend accounts are currently linked to you.</Alert>
{:else}
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Friend</th>
            </tr>
        </thead>
        <tbody>
            {#each vm.friends as friend}
                <tr>
                    <td>
                        <div class="fw-semibold">
                            <a href={vm.getServiceRecordUrl(friend)}>
                                {friend.legal_name}
                            </a>
                        </div>
                        <small class="text-muted">{friend.display_name}</small>
                    </td>
                </tr>
            {/each}
        </tbody>
    </table>
{/if}
