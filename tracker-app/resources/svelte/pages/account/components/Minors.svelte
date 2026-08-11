<script lang="ts">
    import Alert from "$lib/components/ui/Alert.svelte";
    import type { Minor } from "$lib/domains/account";
    import { MinorsViewModel } from "$lib/domains/account";

    interface Props {
        minors: Minor[];
    }
    let { minors }: Props = $props();

    let vm = new MinorsViewModel(minors);
</script>

{#if vm.minors.length === 0}
    <Alert>No cadet accounts are currently linked to you.</Alert>
{:else}
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Cadet</th>
            </tr>
        </thead>
        <tbody>
            {#each vm.minors as minor}
                <tr>
                    <td>
                        <div class="fw-semibold">
                            <a href={vm.getServiceRecordUrl(minor)}>
                                {minor.legal_name}
                            </a>
                        </div>
                        <small class="text-muted">{minor.display_name}</small>
                    </td>
                </tr>
            {/each}
        </tbody>
    </table>
{/if}
