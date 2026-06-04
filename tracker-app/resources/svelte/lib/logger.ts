import { dev } from '$app/environment';

export const logger = {
    log: (...args: unknown[]) => {
        if (dev) console.log(...args);
    },
    error: (...args: unknown[]) => {
        // Keep errors even in production for debugging Capacitor
        console.error(...args);
    }
};