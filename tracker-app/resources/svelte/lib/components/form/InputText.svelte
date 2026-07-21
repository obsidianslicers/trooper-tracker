<script lang="ts">
    import InputError from "./InputError.svelte";
    import InputLabel from "./InputLabel.svelte";
    interface Props {
        value?: string | null;
        type?: "text" | "date" | "datetime" | "time" | "password" | null;
        label?: string | null;
        placeholder?: string | null;
        errors?: string | string[];
        disabled?: boolean;
        change?: (() => void) | null;
    }

    const id = "id-" + crypto.randomUUID();

    let {
        value = $bindable(),
        type = "text",
        label = null,
        placeholder = null,
        errors = [],
        disabled = false,
        change = null,
    }: Props = $props();
</script>

<div class="form-floating">
    <input
        class={["form-control", errors.length > 0 ? "is-invalid" : ""]}
        {id}
        {type}
        {disabled}
        {placeholder}
        onchange={change}
        bind:value
    />
    <InputLabel {id} {label} />
    <InputError {errors} />
</div>
