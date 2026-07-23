<script lang="ts">
    import flatpickr from "flatpickr";
    import type { Instance } from "flatpickr/dist/types/instance";
    import { onDestroy, onMount } from "svelte";
    import InputError from "./InputError.svelte";
    import InputLabel from "./InputLabel.svelte";

    interface Props {
        value?: string | null;
        label?: string | null;
        placeholder?: string | null;
        errors?: string | string[];
        disabled?: boolean;
        change?: (() => void) | null;
    }

    const id = "id-" + crypto.randomUUID();

    let {
        value = $bindable(),
        label = null,
        placeholder = "YYYY-MM-DD",
        errors = [],
        disabled = false,
        change = null,
    }: Props = $props();

    let input_element: HTMLInputElement | undefined = undefined;
    let picker: Instance | null = null;

    onMount(() => {
        if (!input_element) {
            return;
        }

        picker = flatpickr(input_element, {
            dateFormat: "Y-m-d",
            defaultDate: value ?? undefined,
            clickOpens: !disabled,
            onChange: (_selected_dates, date_string) => {
                value = date_string;
            },
        });
    });

    onDestroy(() => {
        picker?.destroy();
        picker = null;
    });

    $effect(() => {
        if (!picker) {
            return;
        }

        const next_value = value ?? "";
        const current_value = input_element?.value ?? "";

        if (next_value === current_value) {
            return;
        }

        if (next_value.length === 0) {
            picker.clear(false);
            return;
        }

        picker.setDate(next_value, false, "Y-m-d");
    });

    $effect(() => {
        picker?.set("clickOpens", !disabled);
    });
</script>

<div class="form-floating">
    <input
        class={["form-control", errors.length > 0 ? "is-invalid" : ""]}
        {id}
        bind:this={input_element}
        type="text"
        {disabled}
        {placeholder}
        onchange={change}
        bind:value
    />
    <InputLabel {id} {label} />
    <InputError {errors} />
</div>
