import { getConfig, type Configuration } from '$lib/domains/app';
import type { Option } from '$lib/domains/types.svelte';
import cacheStateSvelte from './cache-state.svelte';

class ConfigState {
    #configKey = 'app_config_cache';
    #config = $state<Configuration | null>(null);
    #initialized = $state(false);

    get ready(): boolean {
        return this.#initialized && this.#config !== null;
    }

    get name(): string {
        return this.#config?.branding.name || 'Troop Tracker';
    }

    get authConfigured(): boolean {
        return this.emailPasswordEnabled || this.xenForoOauthEnabled || this.googleOauthEnabled;
    }

    get emailPasswordEnabled(): boolean {
        return this.#config?.auth.emailPassword.enabled || false;
    }

    get xenForoOauthEnabled(): boolean {
        return Boolean(this.#config?.auth.xenforo.enabled && this.#config?.auth.xenforo.configured);
    }

    get googleOauthEnabled(): boolean {
        return Boolean(this.#config?.auth.google.enabled && this.#config?.auth.google.configured);
    }

    get oauthEnabled(): boolean {
        return this.xenForoOauthEnabled || this.googleOauthEnabled;
    }

    get xenForoOauthUrl(): string | null {
        if (this.xenForoOauthEnabled) {
            return this.#config?.auth.xenforo.url ?? null;
        }
        return null;
    }

    get googleOauthUrl(): string | null {
        if (this.googleOauthEnabled) {
            return this.#config?.auth.google.url ?? null;
        }
        return null;
    }

    getEnumOptions(enumName: string): Option[] {
        return this.#config?.meta.enums[enumName] || [];
    }

    async initialize(): Promise<ConfigState> {
        try {
            const cacheItem = await cacheStateSvelte.get<Configuration>(this.#configKey);
            if (cacheItem) {
                this.#config = cacheItem;
                return this;
            }

            const result = await getConfig();
            this.#config = result || null;

            // Cache the config
            if (this.#config) {
                await cacheStateSvelte.set(this.#configKey, this.#config);
            }
        }
        catch (err) {
            const opt = err instanceof Error ? { cause: err } : undefined;
            throw new Error('Failed to load application configuration.', opt);
        }
        finally {
            this.#initialized = true;
        }
        return this;
    };
}

export default new ConfigState();