<script lang="ts">
    import { getRoute } from "$lib/utils";
    import type { LoginProvider } from "../models/types";

    interface Props {
        prefix?: string;
        provider: LoginProvider;
    }
    let { prefix = "Login", provider }: Props = $props();
</script>

<a
    href={getRoute("auth.oauth-redirect", { provider: provider.key })}
    class="btn btn-outline-secondary w-100 d-flex justify-content-center align-items-center gap-2"
>
    <img
        src={provider.icon_url}
        alt="{provider.name} Logo"
        style="width: 18px; height: 18px;"
        class="me-3"
    />
    <span>{prefix} with {provider.name}</span>
</a>
