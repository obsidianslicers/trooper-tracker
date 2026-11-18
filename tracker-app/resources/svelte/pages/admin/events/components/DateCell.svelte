<!--
    A compact date field for table rows, with the site's date picker.
-->
<script lang="ts">
    import { attachDatePicker, usesNativePicker, type DatePicker } from "../../../../../js/custom/date-picker.js";
    import { onDestroy, onMount } from "svelte";

    interface Props {
        value: string;
        name: string;
        label: string;
        disabled?: boolean;
        invalid?: boolean;
        onchange?: () => void;
    }
    let { value = $bindable(), name, label, disabled = false, invalid = false, onchange }: Props = $props();

    const native = usesNativePicker();
    let input: HTMLInputElement | undefined = undefined;
    let picker: DatePicker | null = null;

    onMount(() => {
        if (input && !native) {
            picker = attachDatePicker(input, {
                onChange: (next: string) => {
                    value = next;
                },
            });
        }
    });

    onDestroy(() => picker?.destroy());
</script>

<input
    bind:this={input}
    type={native ? "date" : "text"}
    class={["form-control form-control-sm", invalid ? "is-invalid" : ""]}
    placeholder={native ? null : "YYYY-MM-DD"}
    aria-label={label}
    {name}
    {disabled}
    {onchange}
    bind:value
/>
