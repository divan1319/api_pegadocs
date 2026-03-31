import { http } from './http';

/**
 * Inicializa la cookie XSRF para peticiones stateful (POST/PATCH/DELETE) a la API.
 */
export async function ensureSanctumCsrfCookie(): Promise<void> {
    await http.get('/sanctum/csrf-cookie');
}
