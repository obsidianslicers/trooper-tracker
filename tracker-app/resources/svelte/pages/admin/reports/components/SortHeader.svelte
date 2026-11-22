<!--
    A column heading that sorts the report by its column.
-->
<script lang="ts">
    import type { SortLink } from "../models/summaries";

    interface Props {
        label: string;
        link: SortLink;
        end?: boolean;
    }
    let { label, link, end = false }: Props = $props();
</script>

<th scope="col" class={[end && "text-end"]}>
    <a href={link.url} class="text-reset text-decoration-none">
        {label}{link.arrow ? ` ${link.arrow}` : ""}
    </a>
</th>
