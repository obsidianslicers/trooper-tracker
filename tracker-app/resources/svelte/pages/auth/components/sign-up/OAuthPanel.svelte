<script lang="ts">
    import { SignUpViewModel } from "$lib/domains/auth";
    import OAuthGoogleLink from "../OAuthGoogleLink.svelte";
    import OAuthXenforoLink from "../OAuthXenforoLink.svelte";

    interface Props {
        vm: SignUpViewModel;
    }

    let { vm }: Props = $props();
</script>

{#if vm.oauth?.xenforo.configured || vm.oauth?.google.configured}
    <div class="d-grid gap-2 mb-3">
        <OAuthXenforoLink
            configured={vm.oauth?.xenforo.configured}
            name={vm.oauth?.xenforo.name}
            prefix="Sign Up"
        />
        <OAuthGoogleLink
            configured={vm.oauth?.google.configured}
            prefix="Sign Up"
        />
    </div>
{/if}
