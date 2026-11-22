import { Tooltip } from "bootstrap";

/** A Bootstrap tooltip with the given text, for `use:tooltip={text}`. */
export function tooltip(element: HTMLElement, title: string) {
    element.setAttribute("data-bs-title", title);
    const instance = Tooltip.getOrCreateInstance(element);

    return {
        update(next: string) {
            instance.setContent({ ".tooltip-inner": next });
        },
        destroy() {
            instance.dispose();
        },
    };
}
