<template>
    <UContainer class="space-y-8 py-10">
        <div class="flex flex-wrap items-center gap-3">
            <UButton to="/dashboard" variant="ghost" color="neutral" icon="i-lucide-arrow-left">Volver</UButton>
        </div>

        <div v-if="workspacePending || assignmentsPending || membersPending" class="space-y-2">
            <USkeleton class="h-12 w-2/3" />
            <USkeleton class="h-24 w-full" />
        </div>

        <UAlert v-else-if="workspaceError" color="error" title="No se pudo cargar el workspace" />

        <template v-else-if="workspace">
            <UPageHeader :title="workspace.name" :description="`Código: ${workspace.code}`" />

            <UCard>
                <template #header>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-highlighted font-semibold">Miembros del workspace</h2>
                        <UBadge v-if="workspaceMembers?.length" color="neutral" variant="subtle">
                            {{ workspaceMembers.length }}
                        </UBadge>
                    </div>
                </template>
                <div
                    v-if="workspaceMembers?.length"
                    class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="m in workspaceMembers"
                        :key="m.id"
                        class="border-default bg-muted/20 hover:bg-muted/40 rounded-xl border p-4 transition-colors"
                    >
                        <UUser
                            size="lg"
                            orientation="vertical"
                            class="w-full"
                            :name="m.user?.name ?? `Usuario #${m.userId}`"
                            :description="memberSubtitle(m)"
                            :avatar="{ text: initials(m.user?.name ?? '?') }"
                            :chip="memberChip(m)"
                        />
                    </div>
                </div>
                <p v-else class="text-muted text-sm">No hay miembros listados.</p>
            </UCard>

            <UCard v-if="isWorkspaceOwner">
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

            <UAlert
                v-else
                color="info"
                variant="subtle"
                title="Solo el dueño del workspace puede crear tareas"
                description="Puedes participar en las tareas en las que te hayan añadido. Si necesitas una tarea nueva, pídeselo al dueño."
            />

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
import { fetchWorkspace, fetchWorkspaceMembers } from '@/api/workspaces';
import { useAuth } from '@/composables/useAuth';
import type { Assignment, WorkspaceMember } from '@/types/pegadocs';

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

const { data: workspaceMembers, isPending: membersPending } = useQuery({
    queryKey: ['workspace-members', workspaceId],
    queryFn: () => fetchWorkspaceMembers(workspaceId.value),
    enabled: computed(() => Number.isFinite(workspaceId.value) && workspaceId.value > 0),
});

const isWorkspaceOwner = computed(() => {
    if (!user.value || !workspace.value) {
        return false;
    }

    return user.value.id === workspace.value.ownerId;
});

function initials(name: string): string {
    const parts = name.trim().split(/\s+/).filter(Boolean);

    if (parts.length === 0) {
        return '?';
    }

    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }

    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
}

function memberSubtitle(m: WorkspaceMember): string {
    const email = m.user?.email;

    if (email) {
        return email;
    }

    return m.role === 'owner' ? 'Rol: owner' : 'Rol: member';
}

function memberChip(m: WorkspaceMember): { label: string; color: 'primary' | 'neutral' } {
    if (workspace.value && m.userId === workspace.value.ownerId) {
        return { label: 'Dueño', color: 'primary' };
    }

    if (m.role === 'owner') {
        return { label: 'Admin', color: 'neutral' };
    }

    return { label: 'Miembro', color: 'neutral' };
}

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
