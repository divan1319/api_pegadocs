import axios from 'axios';

/**
 * Primer mensaje de validación Laravel (422) para un campo concreto.
 */
export function firstValidationFieldError(err: unknown, field: string): string | undefined {
    if (!axios.isAxiosError(err)) {
        return undefined;
    }

    const raw = err.response?.data as { errors?: Record<string, string[] | string> } | undefined;
    const errors = raw?.errors;
    const v = errors?.[field];

    if (Array.isArray(v) && v.length > 0) {
        return v[0];
    }

    if (typeof v === 'string') {
        return v;
    }

    return undefined;
}
