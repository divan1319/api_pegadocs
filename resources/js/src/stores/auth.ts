import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import {
    fetchCurrentUser,
    loginWithSession,
    logoutSession,
    registerWithSession,
    type LoginPayload,
    type RegisterPayload,
} from '@/api/auth';
import type { ApiUser } from '@/types/user';

export const useAuthStore = defineStore('auth', () => {
    const user = ref<ApiUser | null>(null);
    const resolvingUser = ref(false);

    const isAuthenticated = computed(() => user.value !== null);

    async function resolveUser(): Promise<void> {
        if (resolvingUser.value) {
            return;
        }

        resolvingUser.value = true;

        try {
            user.value = await fetchCurrentUser();
        } finally {
            resolvingUser.value = false;
        }
    }

    async function login(payload: LoginPayload): Promise<ApiUser> {
        const u = await loginWithSession(payload);
        user.value = u;

        return u;
    }

    async function register(payload: RegisterPayload): Promise<ApiUser> {
        const u = await registerWithSession(payload);
        user.value = u;

        return u;
    }

    async function logout(): Promise<void> {
        try {
            await logoutSession();
        } catch {
            // Sesión ya inválida o error de red: igualmente limpiamos el cliente.
        } finally {
            user.value = null;
        }
    }

    return {
        user,
        resolvingUser,
        isAuthenticated,
        resolveUser,
        login,
        register,
        logout,
    };
});
