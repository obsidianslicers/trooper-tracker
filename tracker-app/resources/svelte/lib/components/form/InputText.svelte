<script lang="ts">
    import InputError from "./InputError.svelte";
    import InputLabel from "./InputLabel.svelte";
    interface Props {
        value?: string | null;
        type?: "text" | "date" | "datetime" | "time" | "password" | null;
        searching?: boolean | null;
        label?: string | null;
        placeholder?: string | null;
        errors?: string | string[];
        disabled?: boolean;
        onchange?: (() => void) | null;
        oninput?: (() => void) | null;
        onfocus?: (() => void) | null;
        onblur?: (() => void) | null;
    }

    const id = "id-" + crypto.randomUUID();

    let {
        value = $bindable(),
        type = "text",
        searching = false,
        label = null,
        placeholder = null,
        errors = [],
        disabled = false,
        onchange = null,
        oninput = null,
        onfocus = null,
        onblur = null,
    }: Props = $props();
</script>

<div class="form-floating">
    <input
        class={[
            "form-control",
            errors && errors.length > 0 ? "is-invalid" : "",
            searching ? "searching" : "",
        ]}
        {id}
        {type}
        {disabled}
        {placeholder}
        {onchange}
        {oninput}
        {onfocus}
        {onblur}
        bind:value
        autocomplete="off"
    />
    <InputLabel {id} {label} />
    <InputError {errors} />
</div>
