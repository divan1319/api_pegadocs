import { http } from '@/api/http';
import { apiV1 } from '@/api/pegadocsPrefix';
import type { ApiCollection, ApiEnvelope, AssignmentMember } from '@/types/pegadocs';

export async function fetchAssignmentMembers(assignmentId: number): Promise<AssignmentMember[]> {
    const { data } = await http.get<ApiCollection<AssignmentMember>>(
        `${apiV1}/assignments/${assignmentId}/members`,
    );

    return data.data;
}

export async function addAssignmentMember(assignmentId: number, userId: number): Promise<AssignmentMember> {
    const { data } = await http.post<ApiEnvelope<AssignmentMember>>(
        `${apiV1}/assignments/${assignmentId}/members`,
        { user_id: userId },
    );

    return data.data;
}

export async function removeAssignmentMember(assignmentId: number, userId: number): Promise<void> {
    await http.delete(`${apiV1}/assignments/${assignmentId}/members/${userId}`);
}

export async function patchAssignmentMemberActive(
    assignmentId: number,
    userId: number,
    active: boolean,
): Promise<AssignmentMember> {
    const { data } = await http.patch<ApiEnvelope<AssignmentMember>>(
        `${apiV1}/assignments/${assignmentId}/members/${userId}`,
        { active },
    );

    return data.data;
}

export async function patchAssignmentMemberStatus(
    assignmentId: number,
    userId: number,
    status: AssignmentMember['status'],
): Promise<AssignmentMember> {
    const { data } = await http.patch<ApiEnvelope<AssignmentMember>>(
        `${apiV1}/assignments/${assignmentId}/members/${userId}/status`,
        { status },
    );

    return data.data;
}
