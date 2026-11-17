<!-- Shown once, on the account page that follows account setup -->
<script lang="ts">
    import { t } from "$lib/i18n";
    import { getRoute } from "$lib/utils";
</script>

<section class="card border-success mb-4" aria-labelledby="setup-welcome-title">
    <div class="card-body p-4 d-flex flex-column flex-md-row align-items-md-center gap-3">
        <div class="flex-grow-1">
            <h2 id="setup-welcome-title" class="h4 mb-2">
                <i class="fa fa-fw fa-circle-check text-success"></i>
                {t("Your account is set up")}
            </h2>
            <p class="mb-0">
                {t(
                    "Now that your account is set up, you can check your profile information below, or go straight to the upcoming events."
                )}
            </p>
        </div>
        <a class="btn btn-primary btn-lg text-nowrap" href={getRoute("events.list")}>
            {t("See Upcoming Events")}
            <i class="fa fa-fw fa-arrow-right"></i>
        </a>
    </div>
</section>
