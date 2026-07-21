export class Exception extends Error {
    constructor(message: string) {
        super(message);
        this.name = 'Exception';
    }
}

export class ApiException extends Error {
    status: number;

    constructor(message: string, status: number) {
        super(message);
        this.name = 'ApiException';
        this.status = status;

        // Essential for custom errors in TS/JS to keep the prototype chain
        Object.setPrototypeOf(this, ApiException.prototype);
    }
}

export class ValidationException extends ApiException {
    errors: Record<string, string[]>;

    constructor(errors: Record<string, string[]>, message = 'Validation failed.') {
        super(message, 422);
        this.name = 'ValidationException';
        this.errors = errors;

        Object.setPrototypeOf(this, ValidationException.prototype);
    }
}

export class UnauthorizedException extends ApiException {
    constructor(message = 'Session expired. Please log in.') {
        super(message, 401);
        this.name = 'UnauthorizedException';
        Object.setPrototypeOf(this, UnauthorizedException.prototype);
    }
}

export class ForbiddenException extends ApiException {
    constructor(message = 'You are not authorized to perform this action.') {
        super(message, 403);
        this.name = 'ForbiddenException';
        Object.setPrototypeOf(this, ForbiddenException.prototype);
    }
}