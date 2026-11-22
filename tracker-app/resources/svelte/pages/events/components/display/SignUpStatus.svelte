<!-- A sign-up's status: its label in the status colour, as a word with no icon. -->
<script lang="ts">
    import type { SignUpStatus } from "../../models";

    let { status, block = false, classes = "" }: { status: SignUpStatus; block?: boolean; classes?: string } = $props();
</script>

<span class={[status.color, "fw-semibold", block && "d-block mb-1", classes]}>{status.label}</span>
