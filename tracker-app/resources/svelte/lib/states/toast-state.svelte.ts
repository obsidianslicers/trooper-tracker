export type ToastType =
    | 'primary'
    | 'secondary'
    | 'success'
    | 'danger'
    | 'warning'
    | 'info'
    | 'light'
    | 'dark';

export interface ToastOptions {
    type?: ToastType;
    delay?: number;
    allowDismiss?: boolean;
}

export interface ToastMessage {
    id: number;
    text: string;
    type: ToastType;
    delay: number;
    allow_dismiss: boolean;
}

interface ToastDefaults {
    type: ToastType;
    delay: number;
    allowDismiss: boolean;
}

class ToastState {
    #messages = $state<ToastMessage[]>([]);
    #next_id = 1;
    #timeouts = new Map<number, ReturnType<typeof setTimeout>>();

    readonly #defaults: ToastDefaults = {
        type: 'info',
        delay: 4000,
        allowDismiss: true,
    };

    get all(): ToastMessage[] {
        return this.#messages;
    }

    show(message: string, options: ToastOptions = {}): ToastMessage {
        const toast: ToastMessage = {
            id: this.#next_id++,
            text: message,
            type: options.type ?? this.#defaults.type,
            delay: options.delay ?? this.#defaults.delay,
            allow_dismiss: options.allowDismiss ?? this.#defaults.allowDismiss,
        };

        this.#messages.push(toast);

        if (toast.delay > 0) {
            const timeout_id = setTimeout((): void => {
                this.dismiss(toast.id);
            }, toast.delay);

            this.#timeouts.set(toast.id, timeout_id);
        }

        return toast;
    }

    dismiss(id: number): void {
        const timeout_id = this.#timeouts.get(id);

        if (timeout_id) {
            clearTimeout(timeout_id);
            this.#timeouts.delete(id);
        }

        this.#messages = this.#messages.filter((toast) => toast.id !== id);
    }

    clear(): void {
        this.#timeouts.forEach((timeout_id) => clearTimeout(timeout_id));
        this.#timeouts.clear();
        this.#messages = [];
    }

    primary(message: string, options: ToastOptions = {}): ToastMessage {
        return this.show(message, { ...options, type: 'primary' });
    }

    secondary(message: string, options: ToastOptions = {}): ToastMessage {
        return this.show(message, { ...options, type: 'secondary' });
    }

    success(message: string, options: ToastOptions = {}): ToastMessage {
        return this.show(message, { ...options, type: 'success' });
    }

    danger(message: string, options: ToastOptions = {}): ToastMessage {
        return this.show(message, { delay: 7500, ...options, type: 'danger' });
    }

    warning(message: string, options: ToastOptions = {}): ToastMessage {
        return this.show(message, { delay: 5000, ...options, type: 'warning' });
    }

    info(message: string, options: ToastOptions = {}): ToastMessage {
        return this.show(message, { ...options, type: 'info' });
    }

    light(message: string, options: ToastOptions = {}): ToastMessage {
        return this.show(message, { ...options, type: 'light' });
    }

    dark(message: string, options: ToastOptions = {}): ToastMessage {
        return this.show(message, { ...options, type: 'dark' });
    }
}

export default new ToastState();
