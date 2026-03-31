import { storeToRefs } from 'pinia';
import { useAuthStore } from '@/stores/auth';

/**
 * Acceso al store de autenticación (Pinia) con la misma API que antes.
 */
export function useAuth() {
    const store = useAuthStore();
    const { user, resolvingUser, isAuthenticated } = storeToRefs(store);

    return {
        user,
        resolvingUser,
        isAuthenticated,
        resolveUser: () => store.resolveUser(),
        login: (payload: Parameters<typeof store.login>[0]) => store.login(payload),
        register: (payload: Parameters<typeof store.register>[0]) => store.register(payload),
        logout: () => store.logout(),
    };
}
