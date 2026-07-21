import type { MessageRequest } from '$lib/gateway';
import { api } from '$lib/gateway';
import authStateSvelte from '$lib/states/auth-state.svelte';
import type { Details } from './types';

export async function updateDetails(details: Details): Promise<void> {
    const req: MessageRequest = {
        type: 'Account.UpdateDetails',
        payload: details,
    };

    return await api.dispatch<void>(req).then(async () => {
        //  update local
        await authStateSvelte.updateProfile(details);
    });
}