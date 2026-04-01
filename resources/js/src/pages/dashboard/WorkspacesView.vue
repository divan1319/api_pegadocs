<template>
    <UContainer class="space-y-8 py-10">
        <UPageHeader
            title="Tus workspaces"
            description="Crea un grupo o únete con el código que te compartieron."
        />

        <div class="grid grid-cols-1 gap-6 sm:gap-8 lg:grid-cols-2 lg:items-stretch">
            <UCard class="min-w-0">
                <template #header>
                    <h2 class="text-highlighted font-semibold">Nuevo workspace</h2>
                </template>
                <form
                    class="flex w-full min-w-0 flex-col gap-4 sm:gap-5"
                    @submit.prevent="onCreateWorkspace"
                >
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-1 md:items-start md:gap-x-4">
                        <UFormField label="Nombre" required class="min-w-0">
                            <UInput
                                v-model="createName"
                                class="block w-full min-w-0"
                                placeholder="Ej. Diseño 2026"
                            />
                        </UFormField>
                    </div>
                    <UFormField label="Descripción" class="min-w-0 w-full">
                        <UTextarea
                            v-model="createDescription"
                            class="block w-full min-w-0 min-h-30 resize-y"
                            :rows="4"
                            autoresize
                        />
                    </UFormField>
                    <div class="flex flex-col gap-3 pt-1 sm:flex-row sm:justify-end">
                        <UButton
                            type="submit"
                            class="w-full justify-center sm:w-auto"
                            :loading="createMut.isPending.value"
                        >
                            Crear workspace
                        </UButton>
                    </div>
                </form>
            </UCard>

            <UCard class="min-w-0">
                <template #header>
                    <h2 class="text-highlighted font-semibold">Unirme con código</h2>
                </template>
                <form
                    class="flex w-full min-w-0 flex-col gap-4 sm:gap-5"
                    @submit.prevent="onJoinWorkspace"
                >
                    <UFormField label="Código del workspace" required class="min-w-0 w-full">
                        <UInput
                            v-model="joinCode"
                            class="block w-full min-w-0"
                            placeholder="Pega el código"
                            autocomplete="off"
                            autocapitalize="off"
                            spellcheck="false"
                        />
                    </UFormField>
                    <div class="flex flex-col gap-3 pt-1 sm:flex-row sm:justify-end">
                        <UButton
                            type="submit"
                            color="neutral"
                            variant="outline"
                            class="w-full justify-center sm:w-auto"
                            :loading="joinMut.isPending.value"
                        >
                            Unirme
                        </UButton>
                    </div>
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
import { firstValidationFieldError } from '@/lib/validationErrors';

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
        const fromField = firstValidationFieldError(e, 'code');
        toast.add({
            title: 'No se pudo unir',
            description:
                fromField ??
                (axios.isAxiosError(e) ? String(e.response?.data?.message ?? e.message) : undefined),
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
