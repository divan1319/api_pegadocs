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

        <div v-if="assignmentPending || membersPending || submissionsPending" class="space-y-2">
            <USkeleton class="h-12 w-2/3" />
            <USkeleton class="h-32 w-full" />
        </div>

        <UAlert v-else-if="assignmentError" color="error" title="No se pudo cargar la tarea" />

        <template v-else-if="assignment">
            <UPageHeader :title="assignment.name" :description="assignment.description ?? undefined" />

            <div class="flex flex-wrap gap-2">
                <UBadge color="neutral">{{ assignment.status }}</UBadge>
                <span v-if="assignment.deadline" class="text-muted text-sm">
                    Entrega: {{ formatDate(assignment.deadline) }}
                </span>
            </div>

            <UCard v-if="canManage">
                <template #header>
                    <h2 class="text-highlighted font-semibold">Añadir participante</h2>
                </template>
                <p class="text-muted mb-3 text-sm">
                    El usuario debe ser ya miembro del workspace. Indica su ID numérico (por ahora).
                </p>
                <form class="flex flex-wrap items-end gap-3" @submit.prevent="onAddMember">
                    <UFormField label="ID de usuario" required>
                        <UInput v-model.number="newMemberUserId" type="number" min="1" class="w-40" />
                    </UFormField>
                    <UButton type="submit" :loading="addMemberMut.isPending.value">Añadir</UButton>
                </form>
            </UCard>

            <div class="space-y-6">
                <h2 class="text-highlighted text-lg font-semibold">Entregas por miembro</h2>
                <UCard v-for="m in members" :key="m.id">
                    <template #header>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium">
                                {{ m.user?.name ?? `Usuario #${m.userId}` }}
                            </span>
                            <UBadge color="neutral" variant="subtle">Participación: {{ m.status }}</UBadge>
                        </div>
                    </template>

                    <div v-if="m.userId === user?.id" class="mb-4 space-y-2">
                        <UFormField label="Subir archivo (PDF o imagen)">
                            <UInput type="file" accept=".pdf,image/*" @change="onFileInput($event, m.id)" />
                        </UFormField>
                        <UProgress v-if="uploadProgressFor === m.id" :model-value="uploadPercent" />
                        <p v-if="uploadProgressFor === m.id" class="text-muted text-xs">{{ uploadPercent }}%</p>
                    </div>

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
    </UContainer>
</template>

<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query';
import axios from 'axios';
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useToast } from '@nuxt/ui/composables';
import { fetchAssignment } from '@/api/assignments';
import { addAssignmentMember, fetchAssignmentMembers } from '@/api/assignmentMembers';
import {
    deleteSubmission,
    fetchSubmissions,
    patchSubmissionStatus,
    uploadSubmission,
} from '@/api/submissions';
import { useAuth } from '@/composables/useAuth';
import { submissionIsMergeable } from '@/lib/pdfMerge';
import type { Assignment, AssignmentMember, Submission } from '@/types/pegadocs';

const route = useRoute();
const toast = useToast();
const queryClient = useQueryClient();
const { user } = useAuth();

const assignmentId = computed(() => Number(route.params.assignmentId));

const newMemberUserId = ref<number | null>(null);
const uploadProgressFor = ref<number | null>(null);
const uploadPercent = ref(0);
const deletingSubmissionId = ref<number | null>(null);

const { data: assignment, isPending: assignmentPending, isError: assignmentError } = useQuery({
    queryKey: ['assignment', assignmentId],
    queryFn: () => fetchAssignment(assignmentId.value),
    enabled: computed(() => Number.isFinite(assignmentId.value) && assignmentId.value > 0),
});

const { data: members, isPending: membersPending } = useQuery({
    queryKey: ['assignment-members', assignmentId],
    queryFn: () => fetchAssignmentMembers(assignmentId.value),
    enabled: computed(() => Number.isFinite(assignmentId.value) && assignmentId.value > 0),
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

    const uid = user.value.id;
    const a = assignment.value;

    return a.createdBy === uid || a.workspaceOwnerId === uid;
});

const canMerge = computed(() => {
    if (!assignment.value || assignment.value.status !== 'open') {
        return false;
    }

    return submissions.value?.some((s) => submissionIsMergeable(s)) ?? false;
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

const addMemberMut = useMutation({
    mutationFn: async () => {
        if (newMemberUserId.value === null || newMemberUserId.value < 1) {
            throw new Error('ID inválido');
        }

        await addAssignmentMember(assignmentId.value, newMemberUserId.value);
    },
    onSuccess: async () => {
        await queryClient.invalidateQueries({ queryKey: ['assignment-members', assignmentId] });
        newMemberUserId.value = null;
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
