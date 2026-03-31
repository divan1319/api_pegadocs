<template>
    <UMain>
        <UContainer class="flex min-h-[70vh] items-center justify-center py-10">
            <UPageCard
                class="w-full max-w-md"
                variant="outline"
                title="Iniciar sesión"
                description="Accede con tu correo y contraseña. La sesión se guarda en una cookie segura."
                icon="i-lucide-log-in"
            >
                <UAuthForm
                    ref="authFormRef"
                    :fields="fields"
                    :loading="submitting"
                    :submit="{ label: 'Entrar', block: true }"
                    class="w-full"
                    @submit="onSubmit"
                />

                <template #footer>
                    <p class="text-muted text-sm">
                        ¿No tienes cuenta?
                        <ULink to="/register" class="text-primary font-medium">Regístrate</ULink>
                    </p>
                </template>
            </UPageCard>
        </UContainer>
    </UMain>
</template>

<script setup lang="ts">
import { ref, useTemplateRef } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import { getAuthFormInnerApi } from '@/lib/authForm';
import { isLaravelValidationError, laravelErrorsToFormErrors } from '@/lib/laravelValidation';

const router = useRouter();
const route = useRoute();
const { login } = useAuth();

const authFormRef = useTemplateRef('authFormRef');
const submitting = ref(false);

const fields = [
    {
        name: 'email',
        type: 'email' as const,
        label: 'Correo electrónico',
        placeholder: 'tu@ejemplo.com',
        required: true,
    },
    {
        name: 'password',
        type: 'password' as const,
        label: 'Contraseña',
        required: true,
    },
    {
        name: 'remember',
        type: 'checkbox' as const,
        label: 'Recordarme en este dispositivo',
        defaultValue: false,
    },
];

async function onSubmit(
    event: SubmitEvent & {
        data: { email: string; password: string; remember?: boolean };
    },
): Promise<void> {
    submitting.value = true;
    const formApi = getAuthFormInnerApi(authFormRef.value);
    formApi?.clear();

    try {
        await login({
            email: event.data.email,
            password: event.data.password,
            remember: Boolean(event.data.remember),
        });

        const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/dashboard';
        await router.push(redirect);
    } catch (error: unknown) {
        if (isLaravelValidationError(error) && error.response.data.errors) {
            formApi?.setErrors(laravelErrorsToFormErrors(error.response.data.errors));
        } else {
            throw error;
        }
    } finally {
        submitting.value = false;
    }
}
</script>
