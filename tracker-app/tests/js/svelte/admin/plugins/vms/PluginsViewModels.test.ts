import { describe, expect, it, vi } from 'vitest';

const { post, formPost } = vi.hoisted(() => ({ post: vi.fn(), formPost: vi.fn() }));

vi.mock('@inertiajs/svelte', () => ({
    router: { post },
    useForm: (data: Record<string, unknown>) => ({
        ...data,
        post: formPost,
        reset: vi.fn(),
        errors: { 'settings.webhook_url': 'The webhook url must be a URL.' },
        processing: false,
        isDirty: false,
    }),
}));
vi.mock('ziggy-js', () => ({
    route: (name: string, params: Record<string, unknown> = {}) =>
        `/${name}?${new URLSearchParams(params as Record<string, string>).toString()}`,
}));

import {
    ListViewModel,
    UpdateSettingsViewModel,
} from '../../../../../../resources/svelte/pages/admin/plugins/models/vms';
import type { PluginRow } from '../../../../../../resources/svelte/pages/admin/plugins/models/types';

const plugin = (overrides: Partial<PluginRow> = {}): PluginRow => ({
    slug: 'discord',
    name: 'Discord',
    description: null,
    author: null,
    capabilities: null,
    version: '1.0.0',
    pending_upgrade: null,
    source: 'Bundled',
    status: { label: 'Disabled', tone: 'secondary', title: null },
    requirements: null,
    last_error: null,
    has_settings: true,
    toggle: 'enable',
    can_enable: true,
    enable_confirm: null,
    is_installed: false,
    ...overrides,
});

const listVm = (plugins: PluginRow[]) =>
    new ListViewModel({ plugins, uploads: { is_enabled: true, max_megabytes: '10' }, missing: [] });

describe('ListViewModel', () => {
    it('enables a plain plugin straight away and shows its new state', () => {
        const vm = listVm([plugin()]);

        vm.toggle(vm.plugins[0]);

        const [url, , options] = post.mock.calls.at(-1)!;
        expect(url).toBe('/admin.plugins.enable?slug=discord');
        expect(vm.working).toBe('discord');

        const enabled = plugin({ toggle: 'disable', status: { label: 'Enabled', tone: 'success', title: null } });
        options.onSuccess({ props: { plugins: [enabled] } });
        options.onFinish();
        expect(vm.plugins[0].toggle).toBe('disable');
        expect(vm.working).toBeNull();
    });

    it('asks before turning on a plugin that can do things', () => {
        post.mockClear();
        const vm = listVm([plugin({ enable_confirm: 'Turn on Discord? It posts events.' })]);

        vm.toggle(vm.plugins[0]);

        expect(post).not.toHaveBeenCalled();
        expect(vm.is_confirming).toBe(true);
        expect(vm.pending?.message).toBe('Turn on Discord? It posts events.');

        vm.confirm();
        expect(post.mock.calls.at(-1)![0]).toBe('/admin.plugins.enable?slug=discord');
        expect(vm.is_confirming).toBe(false);
    });

    it('disables or cancels without asking', () => {
        const vm = listVm([plugin({ toggle: 'cancel' })]);

        vm.toggle(vm.plugins[0]);

        expect(post.mock.calls.at(-1)![0]).toBe('/admin.plugins.disable?slug=discord');
    });

    it('asks before uninstalling, and does nothing when cancelled', () => {
        post.mockClear();
        const vm = listVm([plugin({ is_installed: true })]);

        vm.confirmUninstall(vm.plugins[0]);
        expect(vm.pending?.danger).toBe(true);
        vm.cancel();
        expect(post).not.toHaveBeenCalled();

        vm.confirmUninstall(vm.plugins[0]);
        vm.confirm();
        expect(post.mock.calls.at(-1)![0]).toBe('/admin.plugins.uninstall?slug=discord');
    });

    it('uploads the picked archive as form data', () => {
        const vm = listVm([]);
        const archive = new File(['zip'], 'greeter.zip');

        vm.pickArchive(archive);
        vm.install(new Event('submit'));

        expect(vm.form.archive).toBe(archive);
        expect(formPost).toHaveBeenLastCalledWith('/admin.plugins.install?', expect.objectContaining({ forceFormData: true }));
    });
});

describe('UpdateSettingsViewModel', () => {
    const vm = new UpdateSettingsViewModel({
        plugin: { slug: 'discord', name: 'Discord' },
        settings: [
            { key: 'webhook_url', type: 'secret', label: 'Webhook URL', help: null, value: '', is_saved: true },
            { key: 'is_loud', type: 'boolean', label: 'Loud', help: null, value: '0', is_saved: false },
        ],
    });

    it('starts the form from the saved values', () => {
        expect(vm.form.settings).toEqual({ webhook_url: '', is_loud: '0' });
    });

    it('finds the errors for each setting', () => {
        expect(vm.errorsFor(vm.fields[0])).toBe('The webhook url must be a URL.');
        expect(vm.errorsFor(vm.fields[1])).toEqual([]);
    });

    it('saves to the plugin settings', () => {
        vm.submit(new Event('submit'));

        expect(formPost).toHaveBeenLastCalledWith('/admin.plugins.settings?slug=discord');
    });
});
