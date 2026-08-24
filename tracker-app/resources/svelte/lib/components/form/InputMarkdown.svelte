<script lang="ts">
    import EasyMDE from "easymde";
    import InputError from "./InputError.svelte";
    import InputLabel from "./InputLabel.svelte";

    interface Props {
        value?: string | null;
        label?: string | null;
        errors?: string | string[];
        rows?: number;
    }

    let {
        value = $bindable(""),
        label = null,
        errors = [],
        rows = 10,
    }: Props = $props();

    const id = "id-" + crypto.randomUUID();
    let textarea: HTMLTextAreaElement;

    $effect(() => {
        const editor = new EasyMDE({ element: textarea, initialValue: value ?? "" });

        editor.codemirror.on("change", () => {
            value = editor.value();
        });

        return () => {
            editor.toTextArea();
        };
    });
</script>

<InputLabel {id} {label} />
<textarea bind:this={textarea} {id} {rows}></textarea>
<InputError {errors} />
