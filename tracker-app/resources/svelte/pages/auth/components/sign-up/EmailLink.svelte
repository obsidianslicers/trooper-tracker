<script lang="ts">
    import { getRoute } from "$lib/utils";
</script>

<div class="d-grid gap-2 mb-3">
    <a
        href={getRoute("auth.signup-email")}
        class="btn btn-outline-secondary w-100 d-flex justify-content-center align-items-center gap-2"
    >
        <i class="fa fa-fw fa-envelope"></i>
        <span>Sign Up with Email</span>
    </a>
</div>
