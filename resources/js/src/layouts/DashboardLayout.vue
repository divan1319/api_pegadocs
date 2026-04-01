<template>
    <UMain>
        <div class="border-default border-b bg-background/80 backdrop-blur">
            <UContainer class="flex flex-wrap items-center justify-between gap-3 py-3">
                <div class="flex flex-wrap items-center gap-2">
                    <UButton to="/dashboard" variant="ghost" color="neutral" icon="i-lucide-layout-dashboard">
                        Workspaces
                    </UButton>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span v-if="user" class="text-muted text-sm">{{ user.email }}</span>
                    <UButton color="neutral" variant="outline" size="sm" @click="onLogout">
                        Cerrar sesión
                    </UButton>
                </div>
            </UContainer>
        </div>
        <RouterView />
    </UMain>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { ensureSanctumCsrfCookie } from '@/api/sanctum';
import { useAuth } from '@/composables/useAuth';

const router = useRouter();
const { user, logout } = useAuth();

onMounted(() => {
    void ensureSanctumCsrfCookie();
});

async function onLogout(): Promise<void> {
    await logout();
    await router.push('/login');
}
</script>
