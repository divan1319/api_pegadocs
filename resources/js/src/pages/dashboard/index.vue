<template>
    <UMain>
        <UContainer class="space-y-6 py-10">
            <UPageHeader title="Panel" :description="headerDescription" />

            <div class="flex flex-wrap gap-3">
                <UButton color="neutral" variant="soft" to="/">Volver al inicio</UButton>
                <UButton color="neutral" variant="outline" @click="onLogout">Cerrar sesión</UButton>
            </div>
        </UContainer>
    </UMain>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';

const router = useRouter();
const { user, logout } = useAuth();

const headerDescription = computed(() =>
    user.value ? `Conectado como ${user.value.email}` : '',
);

async function onLogout(): Promise<void> {
    await logout();
    await router.push('/login');
}
</script>