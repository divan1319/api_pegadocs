import { http } from '@/api/http';
import { apiV1 } from '@/api/pegadocsPrefix';
import type { ApiCollection, ApiEnvelope, Workspace, WorkspaceMember } from '@/types/pegadocs';

export async function fetchWorkspaces(): Promise<Workspace[]> {
    const { data } = await http.get<ApiCollection<Workspace>>(`${apiV1}/workspaces`);

    return data.data;
}

export async function fetchWorkspace(id: number): Promise<Workspace> {
    const { data } = await http.get<ApiEnvelope<Workspace>>(`${apiV1}/workspaces/${id}`);

    return data.data;
}

export async function fetchWorkspaceMembers(workspaceId: number): Promise<WorkspaceMember[]> {
    const { data } = await http.get<ApiCollection<WorkspaceMember>>(
        `${apiV1}/workspaces/${workspaceId}/members`,
    );

    return data.data;
}

export type StoreWorkspacePayload = {
    name: string;
    code?: string | null;
    description?: string | null;
};

export async function createWorkspace(payload: StoreWorkspacePayload): Promise<Workspace> {
    const { data } = await http.post<ApiEnvelope<Workspace>>(`${apiV1}/workspaces`, payload);

    return data.data;
}

export async function joinWorkspace(code: string): Promise<Workspace> {
    const { data } = await http.post<ApiEnvelope<Workspace>>(`${apiV1}/workspaces/join`, { code });

    return data.data;
}

export async function deleteWorkspace(id: number): Promise<void> {
    await http.delete(`${apiV1}/workspaces/${id}`);
}
