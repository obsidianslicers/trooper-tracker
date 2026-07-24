<script lang="ts">
    import { LoginViewModel } from "$lib/domains/auth";
    import OAuthGoogleLink from "../OAuthGoogleLink.svelte";
    import OAuthXenforoLink from "../OAuthXenforoLink.svelte";

    interface Props {
        vm: LoginViewModel;
    }

    let { vm }: Props = $props();
</script>

{#if vm.oauth?.xenforo.configured || vm.oauth?.google.configured}
    <hr class="my-5" />

    <div class="d-grid gap-2 mb-3">
        <OAuthXenforoLink
            configured={vm.oauth?.xenforo.configured}
            name={vm.oauth?.xenforo.name}
            prefix="Login"
        />
        <OAuthGoogleLink
            configured={vm.oauth?.google.configured}
            prefix="Login"
        />
    </div>
{/if}
