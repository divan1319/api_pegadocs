<template>
    <UContainer class="space-y-8 py-10">
        <div class="flex flex-wrap items-center gap-3">
            <UButton
                v-if="assignment"
                :to="`/dashboard/workspaces/${assignment.workspaceId}`"
                variant="ghost"
                color="neutral"
                icon="i-lucide-arrow-left"
            >
                Volver al workspace
            </UButton>
            <UButton
                v-if="assignment && canMerge"
                :to="`/dashboard/assignments/${assignmentId}/merge`"
                icon="i-lucide-files"
            >
                Unir PDFs
            </UButton>
        </div>

        <div
            v-if="assignmentPending || membersPending || submissionsPending || workspaceMembersPending"
            class="space-y-2"
        >
            <USkeleton class="h-12 w-2/3" />
            <USkeleton class="h-32 w-full" />
        </div>

        <UAlert v-else-if="assignmentError" color="error" title="No se pudo cargar la tarea" />

        <template v-else-if="assignment">
            <div class="flex flex-wrap items-start gap-3">
                <UPageHeader :title="assignment.name" :description="assignment.description ?? undefined" class="flex-1" />
                <UBadge v-if="!assignment.active" color="warning" variant="subtle" class="shrink-0">Tarea inactiva</UBadge>
            </div>

            <div class="flex flex-wrap gap-2">
                <UBadge color="neutral">{{ assignment.status }}</UBadge>
                <span v-if="assignment.deadline" class="text-muted text-sm">
                    Entrega: {{ formatDate(assignment.deadline) }}
                </span>
            </div>

            <UCard v-if="canManage">
                <template #header>
                    <h2 class="text-highlighted font-semibold">Configuración de la tarea</h2>
                </template>
                <form class="flex max-w-xl flex-col gap-4" @submit.prevent="onSaveAssignment">
                    <UFormField label="Nombre" required>
                        <UInput v-model="assignmentNameEdit" />
                    </UFormField>
                    <UFormField
                        label="Tarea activa"
                        description="Si la desactivas, los participantes dejan de verla y acceder (el dueño del workspace sigue viéndola)."
                    >
                        <USwitch v-model="assignmentActiveEdit" />
                    </UFormField>
                    <UButton type="submit" :loading="saveAssignmentMut.isPending.value">Guardar cambios</UButton>
                </form>
            </UCard>

            <UCard>
                <template #header>
                    <div>
                        <h2 class="text-highlighted font-semibold">Participantes de esta tarea</h2>
                        <p class="text-muted mt-1 text-sm">
                            Quienes pueden entregar archivos. Más abajo verás el detalle de subidas por persona.
                        </p>
                    </div>
                </template>
                <div v-if="members?.length" class="flex flex-col gap-3">
                    <div
                        v-for="m in members"
                        :key="m.id"
                        class="border-default bg-muted/15 flex max-w-full flex-wrap items-center gap-2 rounded-xl border px-3 py-2"
                    >
                        <span class="truncate text-sm font-medium">
                            {{ m.user?.name ?? `Usuario #${m.userId}` }}
                        </span>
                        <UBadge color="neutral" variant="subtle" size="xs" class="shrink-0">
                            {{ m.status }}
                        </UBadge>
                        <UBadge v-if="!m.active" color="warning" variant="subtle" size="xs" class="shrink-0">
                            Participación desactivada
                        </UBadge>
                        <div v-if="canManage" class="ml-auto flex flex-wrap gap-1">
                            <UButton
                                v-if="m.active"
                                color="error"
                                variant="soft"
                                size="xs"
                                @click="openTaskMemberModal(m, 'deactivate')"
                            >
                                Desactivar en tarea
                            </UButton>
                            <UButton
                                v-else
                                color="primary"
                                variant="soft"
                                size="xs"
                                @click="openTaskMemberModal(m, 'reactivate')"
                            >
                                Reactivar en tarea
                            </UButton>
                        </div>
                    </div>
                </div>
                <p v-else class="text-muted text-sm">Aún no hay participantes en esta tarea.</p>
            </UCard>

            <UCard v-if="canManage">
                <template #header>
                    <h2 class="text-highlighted font-semibold">Añadir participante</h2>
                </template>
                <p class="text-muted mb-4 text-sm">
                    Solo miembros activos del workspace que aún no están en esta tarea (o que fueron quitados del listado
                    activo).
                </p>
                <form class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end" @submit.prevent="onAddMember">
                    <UFormField label="Miembro del workspace" class="min-w-[260px] flex-1" required>
                        <USelect
                            v-model="selectedWorkspaceUserId"
                            class="w-full"
                            placeholder="Selecciona una persona…"
                            :items="addMemberSelectItems"
                            value-key="value"
                            label-key="label"
                        />
                    </UFormField>
                    <UButton
                        type="submit"
                        :disabled="selectedWorkspaceUserId === undefined || addMemberSelectItems.length === 0"
                        :loading="addMemberMut.isPending.value"
                    >
                        Añadir a la tarea
                    </UButton>
                </form>
                <p v-if="addMemberSelectItems.length === 0" class="text-muted mt-3 text-sm">
                    No hay miembros activos del workspace disponibles para añadir.
                </p>
            </UCard>

            <div class="space-y-6">
                <h2 class="text-highlighted text-lg font-semibold">Entregas por miembro</h2>
                <UCard v-for="m in members" :key="m.id">
                    <template #header>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium">
                                {{ m.user?.name ?? `Usuario #${m.userId}` }}
                            </span>
                            <div class="flex flex-wrap gap-2">
                                <UBadge color="neutral" variant="subtle">Participación: {{ m.status }}</UBadge>
                                <UBadge v-if="!m.active" color="warning" variant="subtle">Desactivada</UBadge>
                            </div>
                        </div>
                    </template>

                    <div v-if="m.userId === user?.id && m.active" class="mb-4 space-y-2">
                        <UFormField label="Subir archivo (PDF o imagen)">
                            <UInput type="file" accept=".pdf,image/*" @change="onFileInput($event, m.id)" />
                        </UFormField>
                        <UProgress v-if="uploadProgressFor === m.id" :model-value="uploadPercent" />
                        <p v-if="uploadProgressFor === m.id" class="text-muted text-xs">{{ uploadPercent }}%</p>
                    </div>
                    <UAlert
                        v-else-if="m.userId === user?.id && !m.active"
                        color="warning"
                        variant="subtle"
                        title="Tu participación está desactivada"
                        description="El dueño del workspace puede reactivarla desde esta misma página."
                    />

                    <ul v-if="submissionsForMember(m.id).length" class="space-y-3">
                        <li
                            v-for="s in submissionsForMember(m.id)"
                            :key="s.id"
                            class="border-default flex flex-col gap-2 border-t pt-3 first:border-t-0 first:pt-0"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <a
                                    :href="s.fileUrl"
                                    target="_blank"
                                    rel="noopener"
                                    class="text-primary text-sm font-medium hover:underline"
                                >
                                    {{ s.fileName }}
                                </a>
                                <UBadge color="neutral">{{ s.status }}</UBadge>
                            </div>
                            <div v-if="canManage" class="flex flex-wrap items-center gap-2">
                                <label class="text-muted text-xs">Estado revisión</label>
                                <select
                                    class="border-default rounded-md border px-2 py-1 text-sm"
                                    :value="s.status"
                                    @change="onSubmissionStatus(s.id, ($event.target as HTMLSelectElement).value)"
                                >
                                    <option value="pending_review">Pendiente</option>
                                    <option value="reviewed">Revisada</option>
                                    <option value="accepted">Aceptada</option>
                                    <option value="rejected">Rechazada</option>
                                </select>
                                <UButton
                                    color="error"
                                    variant="ghost"
                                    size="xs"
                                    :loading="deletingSubmissionId === s.id"
                                    @click="onDeleteSubmission(s.id)"
                                >
                                    Borrar archivo
                                </UButton>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="text-muted text-sm">Sin archivos aún.</p>
                </UCard>
            </div>
        </template>

        <UModal
            v-model:open="taskMemberModalOpen"
            :title="taskMemberModalTitle"
            :description="taskMemberModalDescription"
        >
            <template #footer="{ close }">
                <div class="flex w-full flex-wrap justify-end gap-2">
                    <UButton variant="ghost" color="neutral" @click="closeTaskMemberModal(close)">Cancelar</UButton>
                    <UButton
                        :color="taskMemberModalMode === 'reactivate' ? 'primary' : 'error'"
                        :loading="taskMemberActionLoading"
                        @click="confirmTaskMember(close)"
                    >
                        Confirmar
                    </UButton>
                </div>
            </template>
        </UModal>
    </UContainer>
</template>

<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query';
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useToast } from '@nuxt/ui/composables';
import { fetchAssignment, patchAssignment } from '@/api/assignments';
import {
    addAssignmentMember,
    fetchAssignmentMembers,
    patchAssignmentMemberActive,
    removeAssignmentMember,
} from '@/api/assignmentMembers';
import { fetchWorkspaceMembers } from '@/api/workspaces';
import {
    deleteSubmission,
    fetchSubmissions,
    patchSubmissionStatus,
    uploadSubmission,
} from '@/api/submissions';
import { useAuth } from '@/composables/useAuth';
import { submissionIsMergeable } from '@/lib/pdfMerge';
import type { Assignment, AssignmentMember, Submission, WorkspaceMember } from '@/types/pegadocs';

const route = useRoute();
const toast = useToast();
const queryClient = useQueryClient();
const { user } = useAuth();

const assignmentId = computed(() => Number(route.params.assignmentId));

const selectedWorkspaceUserId = ref<number | undefined>(undefined);
const uploadProgressFor = ref<number | null>(null);
const uploadPercent = ref(0);
const deletingSubmissionId = ref<number | null>(null);
const assignmentNameEdit = ref('');
const assignmentActiveEdit = ref(true);

const taskMemberModalOpen = ref(false);
const taskMemberModalMode = ref<'deactivate' | 'reactivate'>('deactivate');
const taskMemberTargetUserId = ref<number | null>(null);
const taskMemberTargetName = ref('');
const taskMemberActionLoading = ref(false);

const { data: assignment, isPending: assignmentPending, isError: assignmentError } = useQuery({
    queryKey: ['assignment', assignmentId],
    queryFn: () => fetchAssignment(assignmentId.value),
    enabled: computed(() => Number.isFinite(assignmentId.value) && assignmentId.value > 0),
});

watch(
    () => assignment.value,
    (a) => {
        if (a) {
            assignmentNameEdit.value = a.name;
            assignmentActiveEdit.value = a.active;
        }
    },
    { immediate: true },
);

const { data: members, isPending: membersPending } = useQuery({
    queryKey: ['assignment-members', assignmentId],
    queryFn: () => fetchAssignmentMembers(assignmentId.value),
    enabled: computed(() => Number.isFinite(assignmentId.value) && assignmentId.value > 0),
});

const workspaceId = computed(() => assignment.value?.workspaceId ?? null);

const { data: workspaceMembers, isPending: workspaceMembersPending } = useQuery({
    queryKey: ['workspace-members', workspaceId],
    queryFn: () => fetchWorkspaceMembers(workspaceId.value!),
    enabled: computed(() => workspaceId.value !== null && workspaceId.value > 0),
});

/** Participantes con fila en la tarea (activos o no): no deben aparecer en el selector de alta salvo que estén inactivos y el dueño los vuelva a añadir vía reactivación en UI. */
const assignmentParticipantUserIds = computed(() => new Set(members.value?.map((m) => m.userId) ?? []));

const addMemberSelectItems = computed(() => {
    const ws = workspaceMembers.value ?? [];

    return ws
        .filter((wm) => wm.active && !assignmentParticipantUserIds.value.has(wm.userId))
        .map((wm) => ({
            label: formatWorkspaceMemberOptionLabel(wm),
            value: wm.userId,
        }));
});

const { data: submissions, isPending: submissionsPending } = useQuery({
    queryKey: ['submissions', assignmentId],
    queryFn: () => fetchSubmissions(assignmentId.value),
    enabled: computed(() => Number.isFinite(assignmentId.value) && assignmentId.value > 0),
});

const canManage = computed(() => {
    if (!user.value || !assignment.value) {
        return false;
    }

    return assignment.value.workspaceOwnerId === user.value.id;
});

const canMerge = computed(() => {
    if (!assignment.value || assignment.value.status !== 'open' || !assignment.value.active) {
        return false;
    }

    return submissions.value?.some((s) => submissionIsMergeable(s)) ?? false;
});

const taskMemberModalTitle = computed(() =>
    taskMemberModalMode.value === 'reactivate' ? 'Reactivar participación' : 'Desactivar participación en la tarea',
);

const taskMemberModalDescription = computed(() => {
    const name = taskMemberTargetName.value || 'esta persona';

    if (taskMemberModalMode.value === 'reactivate') {
        return `${name} volverá a poder ver la tarea y subir entregas si sigue siendo miembro activo del workspace.`;
    }

    return `${name} dejará de poder participar en esta tarea (las entregas existentes se conservan). Puedes reactivar la participación más adelante.`;
});

function submissionsForMember(memberPk: number): Submission[] {
    return submissions.value?.filter((s) => s.assignmentMemberId === memberPk) ?? [];
}

function formatDate(iso: string): string {
    try {
        return new Date(iso).toLocaleString();
    } catch {
        return iso;
    }
}

function formatWorkspaceMemberOptionLabel(wm: WorkspaceMember): string {
    const name = wm.user?.name?.trim() || `Usuario #${wm.userId}`;
    const email = wm.user?.email;

    if (email) {
        return `${name} · ${email}`;
    }

    return name;
}

function openTaskMemberModal(m: AssignmentMember, mode: 'deactivate' | 'reactivate'): void {
    taskMemberModalMode.value = mode;
    taskMemberTargetUserId.value = m.userId;
    taskMemberTargetName.value = m.user?.name?.trim() || `Usuario #${m.userId}`;
    taskMemberModalOpen.value = true;
}

function closeTaskMemberModal(close?: () => void): void {
    taskMemberModalOpen.value = false;
    taskMemberTargetUserId.value = null;
    close?.();
}

async function confirmTaskMember(close?: () => void): Promise<void> {
    const uid = taskMemberTargetUserId.value;
    if (uid === null) {
        return;
    }

    taskMemberActionLoading.value = true;

    try {
        if (taskMemberModalMode.value === 'deactivate') {
            await removeAssignmentMember(assignmentId.value, uid);
            toast.add({ title: 'Participación desactivada en la tarea', color: 'success' });
        } else {
            await patchAssignmentMemberActive(assignmentId.value, uid, true);
            toast.add({ title: 'Participación reactivada', color: 'success' });
        }

        await queryClient.invalidateQueries({ queryKey: ['assignment-members', assignmentId] });
        closeTaskMemberModal(close);
    } catch (e: unknown) {
        toast.add({
            title: 'No se pudo actualizar',
            description: axios.isAxiosError(e)
                ? String(e.response?.data?.message ?? JSON.stringify(e.response?.data))
                : String(e),
            color: 'error',
        });
    } finally {
        taskMemberActionLoading.value = false;
    }
}

const saveAssignmentMut = useMutation({
    mutationFn: async () => {
        if (!assignment.value) {
            return;
        }

        await patchAssignment(assignment.value.id, {
            name: assignmentNameEdit.value.trim(),
            active: assignmentActiveEdit.value,
        });
    },
    onSuccess: async () => {
        await queryClient.invalidateQueries({ queryKey: ['assignment', assignmentId] });
        await queryClient.invalidateQueries({ queryKey: ['assignments'] });
        toast.add({ title: 'Tarea actualizada', color: 'success' });
    },
    onError: (e: unknown) => {
        toast.add({
            title: 'No se pudo guardar',
            description: axios.isAxiosError(e) ? String(e.response?.data?.message ?? e.message) : undefined,
            color: 'error',
        });
    },
});

function onSaveAssignment(): void {
    if (!assignmentNameEdit.value.trim()) {
        return;
    }

    saveAssignmentMut.mutate();
}

const addMemberMut = useMutation({
    mutationFn: async () => {
        const id = selectedWorkspaceUserId.value;
        if (id === undefined || id < 1) {
            throw new Error('Selecciona un miembro');
        }

        await addAssignmentMember(assignmentId.value, id);
    },
    onSuccess: async () => {
        await queryClient.invalidateQueries({ queryKey: ['assignment-members', assignmentId] });
        selectedWorkspaceUserId.value = undefined;
        toast.add({ title: 'Participante añadido', color: 'success' });
    },
    onError: (e: unknown) => {
        toast.add({
            title: 'No se pudo añadir',
            description: axios.isAxiosError(e)
                ? String(e.response?.data?.message ?? JSON.stringify(e.response?.data))
                : String(e),
            color: 'error',
        });
    },
});

function onAddMember(): void {
    addMemberMut.mutate();
}

async function onFileInput(ev: Event, assignmentMemberId: number): Promise<void> {
    const input = ev.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';

    if (!file) {
        return;
    }

    uploadProgressFor.value = assignmentMemberId;
    uploadPercent.value = 0;

    try {
        await uploadSubmission(assignmentId.value, assignmentMemberId, file, (p) => {
            uploadPercent.value = p;
        });
        await queryClient.invalidateQueries({ queryKey: ['submissions', assignmentId] });
        await queryClient.invalidateQueries({ queryKey: ['assignment-members', assignmentId] });
        toast.add({ title: 'Archivo subido', color: 'success' });
    } catch (e: unknown) {
        toast.add({
            title: 'Error al subir',
            description: axios.isAxiosError(e)
                ? String(e.response?.data?.message ?? JSON.stringify(e.response?.data))
                : String(e),
            color: 'error',
        });
    } finally {
        uploadProgressFor.value = null;
    }
}

async function onSubmissionStatus(submissionId: number, status: string): Promise<void> {
    try {
        await patchSubmissionStatus(submissionId, status as Submission['status']);
        await queryClient.invalidateQueries({ queryKey: ['submissions', assignmentId] });
        toast.add({ title: 'Estado actualizado', color: 'success' });
    } catch (e: unknown) {
        toast.add({
            title: 'No se pudo actualizar',
            description: axios.isAxiosError(e) ? String(e.response?.data?.message ?? e.message) : undefined,
            color: 'error',
        });
    }
}

async function onDeleteSubmission(submissionId: number): Promise<void> {
    deletingSubmissionId.value = submissionId;

    try {
        await deleteSubmission(submissionId);
        await queryClient.invalidateQueries({ queryKey: ['submissions', assignmentId] });
        toast.add({ title: 'Entrega eliminada', color: 'success' });
    } catch (e: unknown) {
        toast.add({
            title: 'No se pudo eliminar',
            description: axios.isAxiosError(e) ? String(e.response?.data?.message ?? e.message) : undefined,
            color: 'error',
        });
    } finally {
        deletingSubmissionId.value = null;
    }
}
</script>
