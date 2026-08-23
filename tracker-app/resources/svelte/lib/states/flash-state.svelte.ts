export type FlashType = 'success' | 'info' | 'warning' | 'danger';

export interface FlashOptions {
    type?: FlashType;
    delay?: number;
    allowDismiss?: boolean;
}

export interface FlashMessage {
    id: number;
    text: string;
    type: FlashType;
    delay: number;
    allow_dismiss: boolean;
}

interface FlashDefaults {
    type: FlashType;
    delay: number;
    allowDismiss: boolean;
}

class FlashState {
    #messages = $state<FlashMessage[]>([]);
    #next_id = 1;
    #timeouts = new Map<number, ReturnType<typeof setTimeout>>();

    readonly #defaults: FlashDefaults = {
        type: 'info',
        delay: 7500,
        allowDismiss: true,
    };

    get all(): FlashMessage[] {
        return this.#messages;
    }

    show(message: string, options: FlashOptions = {}): FlashMessage {
        const flash_message: FlashMessage = {
            id: this.#next_id++,
            text: message,
            type: options.type ?? this.#defaults.type,
            delay: options.delay ?? this.#defaults.delay,
            allow_dismiss: options.allowDismiss ?? this.#defaults.allowDismiss,
        };

        this.#messages.push(flash_message);

        if (flash_message.delay > 0) {
            const timeout_id = setTimeout((): void => {
                this.dismiss(flash_message.id);
            }, flash_message.delay);

            this.#timeouts.set(flash_message.id, timeout_id);
        }

        return flash_message;
    }

    dismiss(id: number): void {
        const timeout_id = this.#timeouts.get(id);

        if (timeout_id) {
            clearTimeout(timeout_id);
            this.#timeouts.delete(id);
        }

        this.#messages = this.#messages.filter((message) => message.id !== id);
    }

    clear(): void {
        this.#timeouts.forEach((timeout_id) => clearTimeout(timeout_id));
        this.#timeouts.clear();
        this.#messages = [];
    }

    success(message: string, options: FlashOptions = {}): FlashMessage {
        return this.show(message, { ...options, type: 'success' });
    }

    info(message: string, options: FlashOptions = {}): FlashMessage {
        return this.show(message, { ...options, type: 'info' });
    }

    warning(message: string, options: FlashOptions = {}): FlashMessage {
        return this.show(message, { delay: 5000, ...options, type: 'warning' });
    }

    danger(message: string, options: FlashOptions = {}): FlashMessage {
        return this.show(message, { delay: 7500, ...options, type: 'danger' });
    }
}

export default new FlashState();
