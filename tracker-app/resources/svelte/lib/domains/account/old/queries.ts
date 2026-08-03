import type { MessageRequest } from '$lib/gateway';
import { api } from '$lib/gateway';
import type { Account } from './types';

export async function getAccount(): Promise<Account> {
    const req: MessageRequest = {
        type: 'Account.GetAccount',
        payload: {},
    };

    return api.dispatch<Account>(req);
}