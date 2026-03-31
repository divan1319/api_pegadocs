/// <reference types="vite/client" />

declare global {
    interface Window {
        axios: import('axios').AxiosInstance;
    }
}

export {};
