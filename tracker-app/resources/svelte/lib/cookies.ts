/**
 * Small cookie helpers for remembering a trooper's choices (such as event filters) across
 * pages. Blade pages read the same cookies through resources/js/custom/utils.js, so values
 * are stored as-is (ids and commas) rather than URL-encoded.
 */
export function getCookie(name: string): string | null {
    if (typeof document === 'undefined') {
        return null;
    }

    const parts = `; ${document.cookie}`.split(`; ${name}=`);

    if (parts.length !== 2) {
        return null;
    }

    return decodeURIComponent(parts.pop()!.split(';').shift() ?? '');
}

export function setCookie(name: string, value: string, days: number = 30): void {
    if (typeof document === 'undefined') {
        return;
    }

    const expires = new Date(Date.now() + days * 24 * 60 * 60 * 1000).toUTCString();

    document.cookie = `${name}=${value};expires=${expires};path=/`;
}
