<template>
    <UMain>
        <UContainer class="flex min-h-[70vh] items-center justify-center py-10">
            <UPageCard
                class="w-full max-w-md"
                variant="outline"
                title="Crear cuenta"
                description="Regístrate para empezar. Tras registrarte iniciarás sesión automáticamente."
                icon="i-lucide-user-plus"
            >
                <UAuthForm
                    ref="authFormRef"
                    :fields="fields"
                    :loading="submitting"
                    :submit="{ label: 'Registrarme', block: true }"
                    class="w-full"
                    @submit="onSubmit"
                />

                <template #footer>
                    <p class="text-muted text-sm">
                        ¿Ya tienes cuenta?
                        <ULink to="/login" class="text-primary font-medium">Inicia sesión</ULink>
                    </p>
                </template>
            </UPageCard>
        </UContainer>
    </UMain>
</template>

<script setup lang="ts">
import { ref, useTemplateRef } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import { getAuthFormInnerApi } from '@/lib/authForm';
import { isLaravelValidationError, laravelErrorsToFormErrors } from '@/lib/laravelValidation';

const router = useRouter();
const { register } = useAuth();

const authFormRef = useTemplateRef('authFormRef');
const submitting = ref(false);

const fields = [
    {
        name: 'name',
        type: 'text' as const,
        label: 'Nombre',
        placeholder: 'Tu nombre',
        required: true,
    },
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
        name: 'password_confirmation',
        type: 'password' as const,
        label: 'Confirmar contraseña',
        required: true,
    },
];

async function onSubmit(
    event: SubmitEvent & {
        data: {
            name: string;
            email: string;
            password: string;
            password_confirmation: string;
        };
    },
): Promise<void> {
    submitting.value = true;
    const formApi = getAuthFormInnerApi(authFormRef.value);
    formApi?.clear();

    try {
        await register({
            name: event.data.name,
            email: event.data.email,
            password: event.data.password,
            password_confirmation: event.data.password_confirmation,
        });

        await router.push('/dashboard');
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
