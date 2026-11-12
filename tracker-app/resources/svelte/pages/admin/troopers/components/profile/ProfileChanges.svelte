<script lang="ts">
    import type { ProfileChange } from "../../models";

    interface Props {
        changes: ProfileChange[];
    }
    let { changes }: Props = $props();
</script>

<p class="text-muted">Changes over the last 30 days.</p>
<table class="table table-sm">
    <thead>
        <tr><th>What</th><th>Field</th><th>Old</th><th>New</th><th>When</th><th>Who</th></tr>
    </thead>
    <tbody>
        {#each changes as change, index (index)}
            <tr>
                <td>{change.label ?? change.what}</td>
                <td>{change.field}</td>
                <td>{change.old_value ?? ""}</td>
                <td>{change.new_value ?? ""}</td>
                <td>{change.when}</td>
                <td>{change.who}</td>
            </tr>
        {:else}
            <tr><td colspan="6" class="text-muted">No recent changes.</td></tr>
        {/each}
    </tbody>
</table>
