import { http } from '@/api/http';
import { apiV1 } from '@/api/pegadocsPrefix';
import type { ApiCollection, ApiEnvelope, Assignment } from '@/types/pegadocs';

export async function fetchAssignmentsForWorkspace(workspaceId: number): Promise<Assignment[]> {
    const { data } = await http.get<ApiCollection<Assignment>>(
        `${apiV1}/workspaces/${workspaceId}/assignments`,
    );

    return data.data;
}

export async function fetchAssignment(id: number): Promise<Assignment> {
    const { data } = await http.get<ApiEnvelope<Assignment>>(`${apiV1}/assignments/${id}`);

    return data.data;
}

export type StoreAssignmentPayload = {
    name: string;
    code?: string | null;
    description?: string | null;
    deadline?: string | null;
    status?: Assignment['status'];
};

export async function createAssignment(
    workspaceId: number,
    payload: StoreAssignmentPayload,
): Promise<Assignment> {
    const { data } = await http.post<ApiEnvelope<Assignment>>(
        `${apiV1}/workspaces/${workspaceId}/assignments`,
        payload,
    );

    return data.data;
}

export async function patchAssignmentStatus(
    assignmentId: number,
    status: Assignment['status'],
): Promise<Assignment> {
    const { data } = await http.patch<ApiEnvelope<Assignment>>(
        `${apiV1}/assignments/${assignmentId}/status`,
        { status },
    );

    return data.data;
}

export async function deleteAssignment(assignmentId: number): Promise<void> {
    await http.delete(`${apiV1}/assignments/${assignmentId}`);
}

export type PatchAssignmentPayload = Partial<
    StoreAssignmentPayload & {
        active: boolean;
    }
>;

export async function patchAssignment(
    assignmentId: number,
    payload: PatchAssignmentPayload,
): Promise<Assignment> {
    const { data } = await http.patch<ApiEnvelope<Assignment>>(`${apiV1}/assignments/${assignmentId}`, payload);

    return data.data;
}
