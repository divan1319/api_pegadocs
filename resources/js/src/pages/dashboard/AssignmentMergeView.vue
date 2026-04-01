<template>
    <UContainer class="space-y-8 py-10">
        <div class="flex flex-wrap items-center gap-3">
            <UButton
                :to="`/dashboard/assignments/${assignmentId}`"
                variant="ghost"
                color="neutral"
                icon="i-lucide-arrow-left"
            >
                Volver a la tarea
            </UButton>
        </div>

        <div v-if="assignmentPending || submissionsPending" class="space-y-2">
            <USkeleton class="h-12 w-2/3" />
            <USkeleton class="h-40 w-full" />
        </div>

        <UAlert v-else-if="assignmentError" color="error" title="No se pudo cargar la tarea" />

        <template v-else-if="assignment">
            <UPageHeader
                title="Unir PDFs"
                description="Arrastra para ordenar las entregas aceptadas. PDFs nativos, PDF convertido en servidor (Imagick) o imágenes PNG/JPEG embebidas en el navegador."
            />

            <UAlert v-if="!mergeable.length" color="warning" title="No hay entregas aceptadas fusionables" />

            <UCard v-else>
                <template #header>
                    <h2 class="text-highlighted font-semibold">Orden del documento final</h2>
                </template>
                <ol class="space-y-2">
                    <li
                        v-for="id in orderedIds"
                        :key="id"
                        draggable="true"
                        class="border-default bg-muted/30 flex cursor-grab items-center justify-between gap-2 rounded-lg border px-3 py-2 active:cursor-grabbing"
                        @dragstart="onDragStart(id)"
                        @dragover.prevent
                        @drop="onDrop(id)"
                    >
                        <span class="text-sm font-medium">{{ labelFor(id) }}</span>
                        <UBadge color="neutral" variant="subtle">Arrastrar</UBadge>
                    </li>
                </ol>
                <div class="mt-6 space-y-2">
                    <UButton :loading="generating" @click="onGenerateAndUpload">Generar y guardar en el servidor</UButton>
                    <UProgress v-if="generating || uploadPercent > 0" :model-value="uploadPercent" />
                    <p v-if="generating || uploadPercent > 0" class="text-muted text-xs">{{ phaseLabel }}</p>
                </div>
            </UCard>

            <UCard v-if="outputs?.length">
                <template #header>
                    <h2 class="text-highlighted font-semibold">PDFs generados</h2>
                </template>
                <ul class="space-y-2">
                    <li v-for="o in outputs" :key="o.id">
                        <a
                            :href="o.fileUrl"
                            target="_blank"
                            rel="noopener"
                            class="text-primary text-sm font-medium hover:underline"
                        >
                            Versión {{ o.id }} — {{ formatDate(o.generatedAt) }}
                        </a>
                    </li>
                </ul>
            </UCard>
        </template>
    </UContainer>
</template>

<script setup lang="ts">
import { useQuery, useQueryClient } from '@tanstack/vue-query';
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useToast } from '@nuxt/ui/composables';
import { fetchAssignment } from '@/api/assignments';
import { fetchMergedOutputs, uploadMergedOutput } from '@/api/mergedOutputs';
import { fetchSubmissions } from '@/api/submissions';
import { mergeSubmissionPdfs, submissionIsMergeable } from '@/lib/pdfMerge';
import type { Submission } from '@/types/pegadocs';

const route = useRoute();
const toast = useToast();
const queryClient = useQueryClient();

const assignmentId = computed(() => Number(route.params.assignmentId));

const orderedIds = ref<number[]>([]);
const dragSourceId = ref<number | null>(null);
const generating = ref(false);
const uploadPercent = ref(0);
const phaseLabel = ref('');

const { data: assignment, isPending: assignmentPending, isError: assignmentError } = useQuery({
    queryKey: ['assignment', assignmentId],
    queryFn: () => fetchAssignment(assignmentId.value),
    enabled: computed(() => Number.isFinite(assignmentId.value) && assignmentId.value > 0),
});

const { data: submissions, isPending: submissionsPending } = useQuery({
    queryKey: ['submissions', assignmentId],
    queryFn: () => fetchSubmissions(assignmentId.value),
    enabled: computed(() => Number.isFinite(assignmentId.value) && assignmentId.value > 0),
});

const { data: outputs } = useQuery({
    queryKey: ['merged-outputs', assignmentId],
    queryFn: () => fetchMergedOutputs(assignmentId.value),
    enabled: computed(() => Number.isFinite(assignmentId.value) && assignmentId.value > 0),
});

const mergeable = computed<Submission[]>(() => {
    return submissions.value?.filter((s) => submissionIsMergeable(s)) ?? [];
});

watch(
    mergeable,
    (list) => {
        orderedIds.value = list.map((s) => s.id);
    },
    { immediate: true },
);

function submissionById(id: number): Submission | undefined {
    return submissions.value?.find((s) => s.id === id);
}

function labelFor(id: number): string {
    const s = submissionById(id);

    return s ? s.fileName : `#${id}`;
}

function onDragStart(id: number): void {
    dragSourceId.value = id;
}

function onDrop(targetId: number): void {
    const from = dragSourceId.value;
    dragSourceId.value = null;

    if (from === null || from === targetId) {
        return;
    }

    const list = [...orderedIds.value];
    const fi = list.indexOf(from);
    const ti = list.indexOf(targetId);

    if (fi < 0 || ti < 0) {
        return;
    }

    list.splice(fi, 1);
    list.splice(ti, 0, from);
    orderedIds.value = list;
}

function formatDate(iso: string): string {
    try {
        return new Date(iso).toLocaleString();
    } catch {
        return iso;
    }
}

async function onGenerateAndUpload(): Promise<void> {
    if (!mergeable.value.length || !orderedIds.value.length) {
        return;
    }

    generating.value = true;
    uploadPercent.value = 0;
    phaseLabel.value = 'Generando PDF…';

    try {
        const orderedSubs = orderedIds.value
            .map((id) => submissionById(id))
            .filter((s): s is Submission => s !== undefined);

        const bytes = await mergeSubmissionPdfs(orderedSubs);
        const blob = new Blob([bytes], { type: 'application/pdf' });

        phaseLabel.value = 'Subiendo al servidor…';

        await uploadMergedOutput(assignmentId.value, blob, orderedIds.value, (p) => {
            uploadPercent.value = p;
        });

        await queryClient.invalidateQueries({ queryKey: ['merged-outputs', assignmentId] });
        toast.add({ title: 'PDF unido guardado', color: 'success' });
    } catch (e: unknown) {
        toast.add({
            title: 'Error al generar o subir',
            description: axios.isAxiosError(e) ? String(e.response?.data?.message ?? e.message) : String(e),
            color: 'error',
        });
    } finally {
        generating.value = false;
        uploadPercent.value = 0;
        phaseLabel.value = '';
    }
}
</script>
