/** Deeply reactive like Inertia's form, so bound inputs update the view in a test. */
export function proxyState<T extends object>(data: T): T {
    const state = $state(data);

    return state;
}
