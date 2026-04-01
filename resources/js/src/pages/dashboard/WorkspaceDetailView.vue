<template>
    <UContainer class="space-y-8 py-10">
        <div class="flex flex-wrap items-center gap-3">
            <UButton to="/dashboard" variant="ghost" color="neutral" icon="i-lucide-arrow-left">Volver</UButton>
        </div>

        <div v-if="workspacePending || assignmentsPending" class="space-y-2">
            <USkeleton class="h-12 w-2/3" />
            <USkeleton class="h-24 w-full" />
        </div>

        <UAlert v-else-if="workspaceError" color="error" title="No se pudo cargar el workspace" />

        <template v-else-if="workspace">
            <UPageHeader :title="workspace.name" :description="`Código: ${workspace.code}`" />

            <UCard>
                <template #header>
                    <h2 class="text-highlighted font-semibold">Nueva tarea</h2>
                </template>
                <form class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end" @submit.prevent="onCreateAssignment">
                    <UFormField label="Nombre" class="min-w-[200px] flex-1" required>
                        <UInput v-model="assignmentName" placeholder="Entrega final" />
                    </UFormField>
                    <UFormField label="Estado inicial" class="w-40">
                        <USelect
                            v-model="assignmentStatus"
                            :items="[
                                { label: 'Borrador', value: 'draft' },
                                { label: 'Abierta', value: 'open' },
                            ]"
                        />
                    </UFormField>
                    <UButton type="submit" :loading="createAssignmentMut.isPending.value">Crear</UButton>
                </form>
            </UCard>

            <div>
                <h2 class="text-highlighted mb-3 text-lg font-semibold">Tareas</h2>
                <ul v-if="assignments?.length" class="space-y-2">
                    <li v-for="a in assignments" :key="a.id">
                        <UCard>
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <UButton
                                        :to="`/dashboard/assignments/${a.id}`"
                                        variant="link"
                                        class="px-0 font-semibold"
                                    >
                                        {{ a.name }}
                                    </UButton>
                                    <p class="text-muted text-sm">Estado: {{ a.status }}</p>
                                </div>
                                <UButton
                                    v-if="canManageAssignment(a)"
                                    color="error"
                                    variant="ghost"
                                    size="sm"
                                    :loading="deletingAssignmentId === a.id"
                                    @click="onDeleteAssignment(a.id)"
                                >
                                    Eliminar
                                </UButton>
                            </div>
                        </UCard>
                    </li>
                </ul>
                <p v-else class="text-muted text-sm">No hay tareas todavía.</p>
            </div>
        </template>
    </UContainer>
</template>

<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query';
import axios from 'axios';
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useToast } from '@nuxt/ui/composables';
import { createAssignment, deleteAssignment, fetchAssignmentsForWorkspace } from '@/api/assignments';
import { fetchWorkspace } from '@/api/workspaces';
import { useAuth } from '@/composables/useAuth';
import type { Assignment } from '@/types/pegadocs';

const route = useRoute();
const toast = useToast();
const queryClient = useQueryClient();
const { user } = useAuth();

const workspaceId = computed(() => Number(route.params.workspaceId));

const assignmentName = ref('');
const assignmentStatus = ref<Assignment['status']>('draft');
const deletingAssignmentId = ref<number | null>(null);

const { data: workspace, isPending: workspacePending, isError: workspaceError } = useQuery({
    queryKey: ['workspace', workspaceId],
    queryFn: () => fetchWorkspace(workspaceId.value),
    enabled: computed(() => Number.isFinite(workspaceId.value) && workspaceId.value > 0),
});

const { data: assignments, isPending: assignmentsPending } = useQuery({
    queryKey: ['assignments', workspaceId],
    queryFn: () => fetchAssignmentsForWorkspace(workspaceId.value),
    enabled: computed(() => Number.isFinite(workspaceId.value) && workspaceId.value > 0),
});

function canManageAssignment(a: Assignment): boolean {
    if (!user.value) {
        return false;
    }

    const uid = user.value.id;

    return a.createdBy === uid || a.workspaceOwnerId === uid;
}

const createAssignmentMut = useMutation({
    mutationFn: async () => {
        await createAssignment(workspaceId.value, {
            name: assignmentName.value.trim(),
            status: assignmentStatus.value,
        });
    },
    onSuccess: async () => {
        await queryClient.invalidateQueries({ queryKey: ['assignments', workspaceId] });
        assignmentName.value = '';
        assignmentStatus.value = 'draft';
        toast.add({ title: 'Tarea creada', color: 'success' });
    },
    onError: (e: unknown) => {
        toast.add({
            title: 'Error al crear la tarea',
            description: axios.isAxiosError(e) ? String(e.response?.data?.message ?? e.message) : undefined,
            color: 'error',
        });
    },
});

function onCreateAssignment(): void {
    if (!assignmentName.value.trim()) {
        return;
    }

    createAssignmentMut.mutate();
}

async function onDeleteAssignment(id: number): Promise<void> {
    deletingAssignmentId.value = id;

    try {
        await deleteAssignment(id);
        await queryClient.invalidateQueries({ queryKey: ['assignments', workspaceId] });
        toast.add({ title: 'Tarea eliminada', color: 'success' });
    } catch (e: unknown) {
        toast.add({
            title: 'No se pudo eliminar',
            description: axios.isAxiosError(e) ? String(e.response?.data?.message ?? e.message) : undefined,
            color: 'error',
        });
    } finally {
        deletingAssignmentId.value = null;
    }
}
</script>
