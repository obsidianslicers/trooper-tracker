import { page } from '$app/state';

export function getQueryParam(url: URL, key: string): string | null {
    if (url.hash) {
        const hash = url.hash.substring(1); // Remove the '#' character
        const queryStart = hash.indexOf('?');
        if (queryStart !== -1) {
            const queryString = hash.substring(queryStart + 1);
            const params = new URLSearchParams(queryString);
            return params.get(key);
        }
    }

    return null;
}

export function getIntendedRoute(url: URL): string {
    let redirectTo = getQueryParam(url, 'redirectTo') ?? '#/';
    if (redirectTo && redirectTo.startsWith('/')) {
        redirectTo = `#${redirectTo}`;
    }
    return redirectTo ? redirectTo : '#/';
}

export function getHashRoute(): string {
    if (typeof window === 'undefined') {
        return '/';
    }

    const hashRoute = window.location.hash.startsWith('#')
        ? window.location.hash.slice(1)
        : window.location.hash;

    return hashRoute || '/';
}

export function getLoginRouteWithRedirect(currentRoute = getHashRoute()): string {
    const [currentPath] = currentRoute.split('?');

    if (currentPath.startsWith('/auth/login')) {
        return '#/login';
    }

    return `#/login?redirectTo=${encodeURIComponent(currentRoute)}`;
}

export function matchesRoute(pattern: string): boolean {
    return page.url.pathname.startsWith(pattern);
}
