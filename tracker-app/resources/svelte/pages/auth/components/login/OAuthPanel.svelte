<script lang="ts">
    import { LoginViewModel } from "../../models";
    import OAuthLinks from "../OAuthLinks.svelte";

    interface Props {
        vm: LoginViewModel;
    }

    let { vm }: Props = $props();
</script>

{#if vm.oauth?.providers.length}
    <hr class="my-5" />

    <div class="d-grid gap-2 mb-3">
        <OAuthLinks oauth={vm.oauth} prefix="Login" />
    </div>
{/if}
