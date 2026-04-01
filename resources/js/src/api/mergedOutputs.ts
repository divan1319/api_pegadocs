import { http } from '@/api/http';
import { apiV1 } from '@/api/pegadocsPrefix';
import type { ApiCollection, ApiEnvelope, MergedOutput } from '@/types/pegadocs';

export type UploadProgressHandler = (percent: number) => void;

export async function fetchMergedOutputs(assignmentId: number): Promise<MergedOutput[]> {
    const { data } = await http.get<ApiCollection<MergedOutput>>(
        `${apiV1}/assignments/${assignmentId}/merged-outputs`,
    );

    return data.data;
}

export async function uploadMergedOutput(
    assignmentId: number,
    pdfBlob: Blob,
    submissionIdsOrdered: number[],
    onProgress?: UploadProgressHandler,
): Promise<MergedOutput> {
    const form = new FormData();
    form.append('file', pdfBlob, 'merged.pdf');
    submissionIdsOrdered.forEach((id, index) => {
        form.append(`submission_ids[${index}]`, String(id));
    });

    const { data } = await http.post<ApiEnvelope<MergedOutput>>(
        `${apiV1}/assignments/${assignmentId}/merged-outputs`,
        form,
        {
            onUploadProgress: (e) => {
                if (onProgress !== undefined && e.total !== undefined && e.total > 0) {
                    onProgress(Math.round((e.loaded * 100) / e.total));
                }
            },
        },
    );

    return data.data;
}
