<script lang="ts">
    import { t } from "$lib/i18n";
    import { getRoute } from "$lib/utils";
    import type { LoginProvider } from "../models/types";

    interface Props {
        action?: "login" | "signup";
        provider: LoginProvider;
    }
    let { action = "login", provider }: Props = $props();
</script>

<a
    href={getRoute("auth.oauth-redirect", { provider: provider.key })}
    class="btn btn-outline-secondary w-100 d-flex justify-content-center align-items-center gap-2"
>
    <img
        src={provider.icon_url}
        alt={t(":provider Logo", { provider: provider.name })}
        style="width: 18px; height: 18px;"
        class="me-3"
    />
    <span>
        {action === "signup"
            ? t("Sign Up with :provider", { provider: provider.name })
            : t("Login with :provider", { provider: provider.name })}
    </span>
</a>
