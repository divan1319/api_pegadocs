import { unref } from 'vue';

type FormApi = {
    setErrors: (errors: { name: string; message: string }[]) => void;
    clear: (name?: string | RegExp) => void;
};

/**
 * Obtiene la API del UForm interno expuesta por UAuthForm (`formRef`).
 */
export function getAuthFormInnerApi(authFormExposed: unknown): FormApi | null {
    if (!authFormExposed || typeof authFormExposed !== 'object') {
        return null;
    }

    const formRef = (authFormExposed as { formRef?: unknown }).formRef;
    const formInstance = unref(formRef);

    if (
        formInstance &&
        typeof formInstance === 'object' &&
        'setErrors' in formInstance &&
        typeof (formInstance as FormApi).setErrors === 'function'
    ) {
        return formInstance as FormApi;
    }

    return null;
}
