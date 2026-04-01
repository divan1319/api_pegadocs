<template>
    <UContainer class="space-y-8 py-10">
        <UPageHeader
            title="Tus workspaces"
            description="Crea un grupo o únete con el código que te compartieron."
        />

        <div class="grid gap-8 lg:grid-cols-2">
            <UCard>
                <template #header>
                    <h2 class="text-highlighted font-semibold">Nuevo workspace</h2>
                </template>
                <form class="space-y-4" @submit.prevent="onCreateWorkspace">
                    <UFormField label="Nombre" required>
                        <UInput v-model="createName" placeholder="Ej. Diseño 2026" />
                    </UFormField>
                    <UFormField label="Código (opcional)" hint="Si lo dejas vacío, se genera uno automático.">
                        <UInput v-model="createCode" placeholder="mi-grupo" />
                    </UFormField>
                    <UFormField label="Descripción">
                        <UTextarea v-model="createDescription" autoresize />
                    </UFormField>
                    <UButton type="submit" :loading="createMut.isPending.value">Crear workspace</UButton>
                </form>
            </UCard>

            <UCard>
                <template #header>
                    <h2 class="text-highlighted font-semibold">Unirme con código</h2>
                </template>
                <form class="space-y-4" @submit.prevent="onJoinWorkspace">
                    <UFormField label="Código del workspace" required>
                        <UInput v-model="joinCode" placeholder="Pega el código" />
                    </UFormField>
                    <UButton type="submit" color="neutral" variant="subtle" :loading="joinMut.isPending.value">
                        Unirme
                    </UButton>
                </form>
            </UCard>
        </div>

        <div v-if="isPending" class="space-y-2">
            <USkeleton class="h-10 w-full" />
            <USkeleton class="h-10 w-full" />
        </div>

        <UAlert v-else-if="isError" color="error" title="No se pudieron cargar los workspaces" />

        <div v-else class="space-y-3">
            <h2 class="text-highlighted text-lg font-semibold">Lista</h2>
            <ul v-if="workspaces?.length" class="space-y-2">
                <li v-for="w in workspaces" :key="w.id">
                    <UCard>
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <UButton
                                    :to="`/dashboard/workspaces/${w.id}`"
                                    variant="link"
                                    class="px-0 text-base font-semibold"
                                >
                                    {{ w.name }}
                                </UButton>
                                <p class="text-muted text-sm">Código: {{ w.code }}</p>
                            </div>
                            <UButton
                                v-if="user?.id === w.ownerId"
                                color="error"
                                variant="ghost"
                                size="sm"
                                :loading="deletingId === w.id"
                                @click="onDeleteWorkspace(w.id)"
                            >
                                Eliminar
                            </UButton>
                        </div>
                    </UCard>
                </li>
            </ul>
            <p v-else class="text-muted text-sm">Aún no tienes workspaces. Crea uno o únete con un código.</p>
        </div>
    </UContainer>
</template>

<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query';
import axios from 'axios';
import { ref } from 'vue';
import { useToast } from '@nuxt/ui/composables';
import { createWorkspace, deleteWorkspace, fetchWorkspaces, joinWorkspace } from '@/api/workspaces';
import { useAuth } from '@/composables/useAuth';

const toast = useToast();
const queryClient = useQueryClient();
const { user } = useAuth();

const createName = ref('');
const createCode = ref('');
const createDescription = ref('');
const joinCode = ref('');
const deletingId = ref<number | null>(null);

const { data: workspaces, isPending, isError } = useQuery({
    queryKey: ['workspaces'],
    queryFn: fetchWorkspaces,
});

const createMut = useMutation({
    mutationFn: async () => {
        await createWorkspace({
            name: createName.value.trim(),
            code: createCode.value.trim() || null,
            description: createDescription.value.trim() || null,
        });
    },
    onSuccess: async () => {
        await queryClient.invalidateQueries({ queryKey: ['workspaces'] });
        createName.value = '';
        createCode.value = '';
        createDescription.value = '';
        toast.add({ title: 'Workspace creado', color: 'success' });
    },
    onError: (e: unknown) => {
        toast.add({
            title: 'No se pudo crear',
            description: axios.isAxiosError(e) ? String(e.response?.data?.message ?? e.message) : undefined,
            color: 'error',
        });
    },
});

const joinMut = useMutation({
    mutationFn: async () => joinWorkspace(joinCode.value.trim()),
    onSuccess: async () => {
        await queryClient.invalidateQueries({ queryKey: ['workspaces'] });
        joinCode.value = '';
        toast.add({ title: 'Te uniste al workspace', color: 'success' });
    },
    onError: (e: unknown) => {
        toast.add({
            title: 'No se pudo unir',
            description: axios.isAxiosError(e) ? String(e.response?.data?.message ?? e.message) : undefined,
            color: 'error',
        });
    },
});

async function onCreateWorkspace(): Promise<void> {
    if (!createName.value.trim()) {
        return;
    }

    createMut.mutate();
}

async function onJoinWorkspace(): Promise<void> {
    if (!joinCode.value.trim()) {
        return;
    }

    joinMut.mutate();
}

async function onDeleteWorkspace(id: number): Promise<void> {
    deletingId.value = id;

    try {
        await deleteWorkspace(id);
        await queryClient.invalidateQueries({ queryKey: ['workspaces'] });
        toast.add({ title: 'Workspace eliminado', color: 'success' });
    } catch (e: unknown) {
        toast.add({
            title: 'No se pudo eliminar',
            description: axios.isAxiosError(e) ? String(e.response?.data?.message ?? e.message) : undefined,
            color: 'error',
        });
    } finally {
        deletingId.value = null;
    }
}
</script>
