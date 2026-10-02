<script lang="ts">
    import Alert from "$lib/components/ui/Alert.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";

    pageState.title = "Pending Membership";
</script>

<SlimView>
    <Alert type="warning">
        <strong>Your application is under review.</strong>
        <div class="mt-2">
            A moderator will review your submission and you will receive an
            email when a decision has been made.
        </div>
    </Alert>
</SlimView>
