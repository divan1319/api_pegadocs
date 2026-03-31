import axios from 'axios';

/**
 * Cliente HTTP: sesión por cookie + CSRF Sanctum (XSRF-TOKEN → X-XSRF-TOKEN).
 * Deja `VITE_API_BASE_URL` vacío si el HTML y la API comparten origen (p. ej. solo `php artisan serve`).
 * Si Vite corre en otro puerto, apunta la base a Laravel y activa CORS con credenciales en el backend.
 *
 * @see https://laravel.com/docs/sanctum#spa-authentication
 */
export const http = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || '',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
    withXSRFToken: true,
});
