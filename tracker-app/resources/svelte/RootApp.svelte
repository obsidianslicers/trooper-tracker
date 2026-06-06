<script lang="ts">
    import PageTitle from "$lib/components/PageTitle.svelte";
    import ToastMessages from "$lib/components/ToastMessages.svelte";
    import pageState from "$lib/states/page-state.svelte";

    interface RootAppProps {
        inertiaApp: any;
        appProps: any;
    }

    let { inertiaApp, appProps }: RootAppProps = $props();

    // Secure the reactive Svelte 5 constructor instance context cleanly
    let InertiaComponent = $derived(inertiaApp);
</script>

<svelte:head>
    <title>{pageState.title ?? ""}</title>
</svelte:head>

<ToastMessages />
<PageTitle />

<InertiaComponent {...appProps} />
