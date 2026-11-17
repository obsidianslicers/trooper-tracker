<script lang="ts">
    import { attachDatePicker, usesNativePicker, type DatePicker } from "../../../../js/custom/date-picker.js";
    import { onDestroy, onMount } from "svelte";
    import InputError from "./InputError.svelte";
    import InputLabel from "./InputLabel.svelte";

    interface Props {
        value?: string | null;
        label?: string | null;
        placeholder?: string | null;
        errors?: string | string[];
        disabled?: boolean;
        /** Pick a time as well as a date. */
        with_time?: boolean;
        change?: (() => void) | null;
    }

    const id = "id-" + crypto.randomUUID();
    // a phone gets its own date picker; other devices get the calendar popup
    const native = usesNativePicker();

    let {
        value = $bindable(),
        label = null,
        placeholder = "YYYY-MM-DD",
        errors = [],
        disabled = false,
        with_time = false,
        change = null,
    }: Props = $props();

    let input_element: HTMLInputElement | undefined = undefined;
    let picker: DatePicker | null = null;

    onMount(() => {
        if (!input_element || native) {
            return;
        }

        picker = attachDatePicker(input_element, {
            withTime: with_time,
            onChange: (next_value: string) => {
                value = next_value;
            },
        });
    });

    onDestroy(() => {
        picker?.destroy();
        picker = null;
    });

    // value set from outside: point the calendar at it (bind:value has updated the input)
    $effect(() => {
        void value;
        picker?.sync();
    });
</script>

<div class="form-floating">
    <input
        class={[
            "form-control",
            errors && errors.length > 0 ? "is-invalid" : "",
        ]}
        {id}
        bind:this={input_element}
        type={native ? (with_time ? "datetime-local" : "date") : "text"}
        {disabled}
        placeholder={native ? null : placeholder}
        onchange={change}
        bind:value
    />
    <InputLabel {id} {label} />
    <InputError {errors} />
</div>
