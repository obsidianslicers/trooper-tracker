import Sortable from "sortablejs";

type SortableOptions = {
    onReorderComplete: (ordered_ids: number[]) => void;
};

export function sortable(options: SortableOptions) {
    return (node: HTMLElement) => {
        const instance = Sortable.create(node, {
            handle: ".move-handle",
            ghostClass: "sortable-ghost",
            animation: 150,
            onEnd: () => {
                const nodes = node.querySelectorAll<HTMLElement>("[data-id]");

                const ordered_ids = Array.from(nodes)
                    .map((element) => Number(element.dataset.id))
                    .filter((id) => !Number.isNaN(id));

                options.onReorderComplete(ordered_ids);
            },
        });

        return () => instance.destroy();
    };
}