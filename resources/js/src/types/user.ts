/**
 * Forma de `data` en respuestas JSON con UserResource.
 */
export type ApiUser = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
};

export type UserResourceResponse = {
    data: ApiUser;
};
