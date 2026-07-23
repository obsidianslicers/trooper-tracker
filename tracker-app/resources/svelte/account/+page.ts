import { AccountViewModel } from '$lib/domains/account';
import breadCrumbStateSvelte from '$lib/states/bread-crumb-state.svelte';
import type { PageLoad } from './$types';

export const load: PageLoad = () => {
    const vm = new AccountViewModel();

    breadCrumbStateSvelte.title('Account');

    return { vm: vm.load() };
};