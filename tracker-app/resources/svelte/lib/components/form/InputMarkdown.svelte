<script lang="ts">
    import type { Smiley } from "$lib/domains/types.svelte";
    import { t } from "$lib/i18n";
    import EasyMDE from "easymde";
    import { untrack } from "svelte";
    import InputError from "./InputError.svelte";
    import InputLabel from "./InputLabel.svelte";

    interface Props {
        value?: string | null;
        label?: string | null;
        errors?: string | string[];
        rows?: number;
        /** When given, a toolbar button opens a panel of smilies to insert. */
        smilies?: Smiley[];
    }

    let {
        value = $bindable(""),
        label = null,
        errors = [],
        rows = 10,
        smilies = [],
    }: Props = $props();

    const id = "id-" + crypto.randomUUID();
    let textarea: HTMLTextAreaElement;
    let editor: EasyMDE | null = null;
    let show_smilies = $state(false);

    $effect(() => {
        editor = new EasyMDE({ element: textarea, initialValue: untrack(() => value) ?? "" });

        editor.codemirror.on("change", () => {
            value = editor?.value() ?? "";
        });

        if (untrack(() => smilies.length) > 0) {
            addSmiliesButton(editor);
        }

        return () => {
            editor?.toTextArea();
            editor = null;
        };
    });

    function addSmiliesButton(editor: EasyMDE) {
        const toolbar = (editor as unknown as { gui?: { toolbar?: HTMLElement } }).gui?.toolbar;

        if (!toolbar) {
            return;
        }

        const separator = document.createElement("i");
        separator.className = "separator";

        const button = document.createElement("button");
        button.type = "button";
        button.title = t("Smilies");
        button.setAttribute("aria-label", t("Smilies"));
        button.setAttribute("aria-controls", `${id}-smilies`);
        button.innerHTML = '<i class="far fa-smile"></i>';
        button.addEventListener("click", () => (show_smilies = !show_smilies));

        toolbar.append(separator, button);
    }

    function insert(smiley: Smiley) {
        editor?.codemirror.replaceSelection(smiley.code);
        editor?.codemirror.focus();
    }
</script>

<InputLabel {id} {label} />
<textarea bind:this={textarea} {id} {rows}></textarea>
{#if show_smilies}
    <div id="{id}-smilies" class="smilies-panel mt-2 p-2 border rounded">
        {#each smilies as smiley}
            <button
                type="button"
                class="btn btn-link p-0 me-1"
                title="{smiley.title} — {smiley.code}"
                onclick={() => insert(smiley)}
            >
                <img src={smiley.image_url} alt={smiley.title} class="smiley" />
            </button>
        {/each}
    </div>
{/if}
<InputError {errors} />

<style>
    .smilies-panel {
        max-height: 180px;
        overflow-y: auto;
        line-height: 2.2;
    }

    .smiley {
        height: 1.6rem;
    }
</style>
