import Sortable from 'sortablejs';
import type { Action } from 'svelte/action';

interface SortableActionOptions {
    handle: string;
    onReorder: (orderedIds: string[]) => void;
}

export const sortable: Action<HTMLElement, SortableActionOptions> = (node, options) => {
    const instance = Sortable.create(node, {
        handle: options.handle,
        animation: 150,
        onEnd: () => {
            const ids = Array.from(node.querySelectorAll<HTMLElement>('[data-id]'))
                .map((el) => el.dataset.id!);

            options.onReorder(ids);
        },
    });

    return {
        destroy: () => instance.destroy(),
    };
};
