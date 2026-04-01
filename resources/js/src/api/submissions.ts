import { http } from '@/api/http';
import { apiV1 } from '@/api/pegadocsPrefix';
import type { ApiCollection, ApiEnvelope, Submission } from '@/types/pegadocs';

export async function fetchSubmissions(assignmentId: number): Promise<Submission[]> {
    const { data } = await http.get<ApiCollection<Submission>>(
        `${apiV1}/assignments/${assignmentId}/submissions`,
    );

    return data.data;
}

export type UploadProgressHandler = (percent: number) => void;

export async function uploadSubmission(
    assignmentId: number,
    assignmentMemberId: number,
    file: File,
    onProgress?: UploadProgressHandler,
): Promise<Submission> {
    const form = new FormData();
    form.append('assignment_member_id', String(assignmentMemberId));
    form.append('file', file);

    const { data } = await http.post<ApiEnvelope<Submission>>(
        `${apiV1}/assignments/${assignmentId}/submissions`,
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

export async function patchSubmissionStatus(
    submissionId: number,
    status: Submission['status'],
): Promise<Submission> {
    const { data } = await http.patch<ApiEnvelope<Submission>>(
        `${apiV1}/submissions/${submissionId}/status`,
        { status },
    );

    return data.data;
}

export async function deleteSubmission(submissionId: number): Promise<void> {
    await http.delete(`${apiV1}/submissions/${submissionId}`);
}
