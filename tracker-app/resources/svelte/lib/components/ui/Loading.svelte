<script lang="ts">
    const statements: string[] = [
        'Calibrating meters... Not that it helps your aim.',
        'Vader is coming. Look busy.',
        'Filing paperwork for your lost blaster... again.',
        'Yes, the armor is supposed to pinch there.',
        'Processing transfers to the Death Star...',
        'Deleting rebel trash from your HoloNet history.',
        'Sweeping Endor for Ewoks. Watch your ankles.',
        'Who turned off the tractor beam again?!',
        'Choking on your lack of faith...',
        'Searching for your respect for authority...',
        "TK-421? Why aren't you at your post?",
        'Lowering your salary for that last mission.',
        "Replacing another 'broken' TIE fighter...",
        'Buffing scuffs out of Lord Vader’s boots.',
        'Re-educating citizens. Move along.',
        'Minimizing thermal exhaust ports...',
        'Consulting the Emperor. Please grovel.',
        'Counting how many times you missed today.',
        'Sending a probe droid to find your data...',
        'The Empire does not wait, but you must.',
        'Stand by, Trooper.',
    ];
    const ix = Math.floor(Math.random() * statements.length);

    interface Props {
        loading?: boolean;
        message?: string;
    }

    let { loading = true, message = 'Loading ... ' + statements[ix] }: Props =
        $props();
</script>

{#if loading}
    <div class="text-center py-4">
        <i class="fa-solid fa-spinner fa-spin fa-3x text-muted mx-3"></i>
        <p class="mt-3 text-muted">{message}</p>
    </div>
{/if}
