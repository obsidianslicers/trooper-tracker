<script lang="ts">
    import ToastMessages from "./lib/components/ToastMessages.svelte";

    interface RootAppProps {
        inertiaApp: any;
        appProps: any;
    }

    let { inertiaApp, appProps }: RootAppProps = $props();

    // Secure the reactive Svelte 5 constructor instance context cleanly
    let InertiaComponent = $derived(inertiaApp);
</script>

<ToastMessages />

<InertiaComponent {...appProps} />
