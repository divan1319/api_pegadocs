<template>
    <UMain>
        <UContainer class="space-y-6 py-10">
            <UPageHeader title="Inicio" description="PegaDocs — API con sesión por cookie (Sanctum)." />

            <div v-if="user" class="flex flex-wrap items-center gap-3">
                <span class="text-muted text-sm">Sesión: {{ user.name }} ({{ user.email }})</span>
                <UButton color="neutral" variant="soft" to="/dashboard">Ir al panel</UButton>
                <UButton color="neutral" variant="ghost" @click="onLogout">Cerrar sesión</UButton>
            </div>

            <div v-else class="flex flex-wrap gap-3">
                <UButton to="/login">Iniciar sesión</UButton>
                <UButton to="/register" color="neutral" variant="outline">Registrarse</UButton>
            </div>
        </UContainer>
    </UMain>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';

const router = useRouter();
const { user, logout } = useAuth();

async function onLogout(): Promise<void> {
    await logout();
    await router.push('/');
}
</script>