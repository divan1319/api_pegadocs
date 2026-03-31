import axios from 'axios';
import { ensureSanctumCsrfCookie } from './sanctum';
import { http } from './http';
import type { ApiUser, UserResourceResponse } from '@/types/user';

const prefix = '/api/v1';

export type LoginPayload = {
    email: string;
    password: string;
    remember?: boolean;
};

export type RegisterPayload = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export async function loginWithSession(payload: LoginPayload): Promise<ApiUser> {
    await ensureSanctumCsrfCookie();
    const { data } = await http.post<UserResourceResponse>(`${prefix}/auth/login`, {
        email: payload.email,
        password: payload.password,
        remember: payload.remember ?? false,
    });

    return data.data;
}

export async function registerWithSession(payload: RegisterPayload): Promise<ApiUser> {
    await ensureSanctumCsrfCookie();
    const { data } = await http.post<UserResourceResponse>(`${prefix}/auth/register`, {
        name: payload.name,
        email: payload.email,
        password: payload.password,
        password_confirmation: payload.password_confirmation,
    });

    return data.data;
}

export async function logoutSession(): Promise<void> {
    await ensureSanctumCsrfCookie();
    await http.post(`${prefix}/auth/logout`);
}

export async function fetchCurrentUser(): Promise<ApiUser | null> {
    try {
        const { data } = await http.get<UserResourceResponse>(`${prefix}/user`);

        return data.data;
    } catch (error: unknown) {
        if (isUnauthorized(error)) {
            return null;
        }

        throw error;
    }
}

function isUnauthorized(error: unknown): boolean {
    return axios.isAxiosError(error) && error.response?.status === 401;
}
