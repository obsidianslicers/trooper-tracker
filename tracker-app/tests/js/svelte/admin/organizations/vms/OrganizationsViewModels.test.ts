import { describe, expect, it, vi } from 'vitest';

const { post, formPost, transform } = vi.hoisted(() => {
    const formPost = vi.fn();
    const transform = vi.fn();
    return { post: vi.fn(), formPost, transform };
});

vi.mock('@inertiajs/svelte', () => ({
    router: { post },
    useForm: (data: Record<string, unknown>) => {
        const form = { ...data, post: formPost, errors: {}, processing: false, isDirty: false, transform };
        transform.mockImplementation((callback: (d: Record<string, unknown>) => unknown) => {
            formPost.mockImplementation(() => callback({ ...data }));
            return form;
        });
        return form;
    },
}));
vi.mock('ziggy-js', () => ({
    route: (name: string, params: Record<string, unknown> = {}) =>
        `/${name}?${new URLSearchParams(params as Record<string, string>).toString()}`,
}));

import {
    CreateViewModel,
    UpdateViewModel,
} from '../../../../../../resources/svelte/pages/admin/organizations/models/vms';
import type { UpdatePageData } from '../../../../../../resources/svelte/pages/admin/organizations/models/types';

const pageData = (): UpdatePageData => ({
    organization: {
        id: 7,
        name: 'E2E Garrison',
        synchronizer: null,
        is_club: false,
        parent: { name: '501st Legion', label: 'Parent Organization' },
        logo_url: '/logo.png?v=1',
        trooper_stamps: {} as UpdatePageData['organization']['trooper_stamps'],
    },
    synchronizer_options: null,
    plugin_fields: '',
});

describe('CreateViewModel', () => {
    it('creates the organization under its parent', () => {
        const vm = new CreateViewModel({ parent: { id: 3, name: '501st Legion' } });

        vm.form.name = 'Squad';
        vm.submit(new Event('submit'));

        expect(formPost).toHaveBeenLastCalledWith('/admin.organizations.store?parent=3');
    });
});

describe('UpdateViewModel', () => {
    it('starts with no roster sync picked when the organization has none', () => {
        const vm = new UpdateViewModel(pageData());

        expect(vm.form.synchronizer).toBe('');
        expect(vm.form.name).toBe('E2E Garrison');
    });

    it('sends the fields plugins add along with the form', () => {
        const vm = new UpdateViewModel(pageData());
        const inputs = [
            { name: 'discord_role', value: '123', type: 'text', checked: false },
            { name: 'announce', value: '1', type: 'checkbox', checked: false },
            { name: 'mirror', value: '1', type: 'checkbox', checked: true },
        ];
        vm.plugin_fields = { querySelectorAll: () => inputs } as unknown as HTMLElement;

        vm.submit(new Event('submit'));

        expect(formPost).toHaveBeenLastCalledWith('/admin.organizations.update?organization=7');
        const sent = transform.mock.calls.at(-1)![0]({ name: 'E2E Garrison', synchronizer: '' });
        expect(sent).toEqual({ name: 'E2E Garrison', synchronizer: '', discord_role: '123', mirror: '1' });
    });

    it('uploads a picked logo and shows the new one', () => {
        const vm = new UpdateViewModel(pageData());
        const file = new File(['png'], 'logo.png', { type: 'image/png' });

        vm.uploadLogo(file);

        expect(vm.uploading).toBe(true);
        const [url, data, options] = post.mock.calls.at(-1)!;
        expect(url).toBe('/admin.organizations.image.update?organization=7');
        expect(data).toEqual({ logo: file });
        expect(options.forceFormData).toBe(true);

        const updated = { ...pageData().organization, logo_url: '/logo.png?v=2' };
        options.onSuccess({ props: { organization: updated } });
        options.onFinish();
        expect(vm.organization.logo_url).toBe('/logo.png?v=2');
        expect(vm.uploading).toBe(false);
    });

    it('shows why a logo was refused', () => {
        const vm = new UpdateViewModel(pageData());

        vm.uploadLogo(new File(['x'], 'logo.txt'));
        post.mock.calls.at(-1)![2].onError({ logo: 'The logo must be an image.' });

        expect(vm.logo_errors).toEqual(['The logo must be an image.']);
    });

    it('does nothing when no file was picked', () => {
        post.mockClear();
        const vm = new UpdateViewModel(pageData());

        vm.uploadLogo(undefined);

        expect(post).not.toHaveBeenCalled();
    });
});
