import axios from 'axios';

export type LaravelValidationBody = {
    message?: string;
    errors?: Record<string, string[]>;
};

/**
 * Convierte errores 422 de Laravel al formato que espera Nuxt UI Form (`setErrors`).
 */
export function laravelErrorsToFormErrors(
    errors: Record<string, string[]>,
): { name: string; message: string }[] {
    return Object.entries(errors).flatMap(([name, messages]) =>
        messages.map((message) => ({ name, message })),
    );
}

export function isLaravelValidationError(error: unknown): error is {
    response: { status: number; data: LaravelValidationBody };
} {
    return (
        axios.isAxiosError(error) &&
        error.response?.status === 422 &&
        error.response.data !== undefined &&
        typeof error.response.data === 'object' &&
        error.response.data !== null &&
        'errors' in error.response.data
    );
}
