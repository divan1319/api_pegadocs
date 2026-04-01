import type { ApiUser } from '@/types/user';

export type ApiEnvelope<T> = { data: T };

export type ApiCollection<T> = { data: T[] };

export type Workspace = {
    id: number;
    ownerId: number;
    name: string;
    code: string;
    description: string | null;
    active: boolean;
    createdAt: string;
    updatedAt: string;
};

export type WorkspaceMember = {
    id: number;
    workspaceId: number;
    userId: number;
    role: string;
    active: boolean;
    createdAt: string;
    updatedAt: string;
    user?: ApiUser;
};

export type Assignment = {
    id: number;
    workspaceId: number;
    createdBy: number;
    workspaceOwnerId?: number;
    name: string;
    code: string;
    description: string | null;
    deadline: string | null;
    status: 'draft' | 'open' | 'closed';
    active: boolean;
    createdAt: string;
    updatedAt: string;
};

export type AssignmentMember = {
    id: number;
    assignmentId: number;
    userId: number;
    status: 'pending' | 'uploaded' | 'approved';
    active: boolean;
    createdAt: string;
    updatedAt: string;
    user?: ApiUser;
};

export type Submission = {
    id: number;
    assignmentId: number;
    assignmentMemberId: number;
    fileName: string;
    fileUrl: string;
    convertedPdfUrl: string | null;
    fileType: 'pdf' | 'image' | 'other';
    fileSize: number;
    status: 'pending_review' | 'reviewed' | 'accepted' | 'rejected';
    createdAt: string;
    updatedAt: string;
};

export type MergedOutput = {
    id: number;
    assignmentId: number;
    generatedBy: number;
    fileUrl: string;
    generatedAt: string;
    createdAt: string;
    updatedAt: string;
};
