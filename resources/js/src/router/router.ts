import { createRouter, createWebHistory } from 'vue-router';
import { useAuth } from '@/composables/useAuth';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            component: () => import('@/pages/Home.vue'),
        },
        {
            path: '/login',
            component: () => import('@/pages/Login.vue'),
            meta: { guestOnly: true },
        },
        {
            path: '/register',
            component: () => import('@/pages/Register.vue'),
            meta: { guestOnly: true },
        },
        {
            path: '/dashboard',
            component: () => import('@/layouts/DashboardLayout.vue'),
            meta: { requiresAuth: true },
            children: [
                {
                    path: '',
                    component: () => import('@/pages/dashboard/WorkspacesView.vue'),
                },
                {
                    path: 'workspaces/:workspaceId',
                    component: () => import('@/pages/dashboard/WorkspaceDetailView.vue'),
                },
                {
                    path: 'assignments/:assignmentId',
                    component: () => import('@/pages/dashboard/AssignmentDetailView.vue'),
                },
                {
                    path: 'assignments/:assignmentId/merge',
                    component: () => import('@/pages/dashboard/AssignmentMergeView.vue'),
                },
            ],
        },
        {
            path: '/:pathMatch(.*)*',
            component: () => import('@/pages/404.vue'),
        },
    ],
});

router.beforeEach(async (to) => {
    const { user, resolveUser } = useAuth();

    if (to.meta.requiresAuth) {
        if (!user.value) {
            await resolveUser();
        }

        if (!user.value) {
            return { path: '/login', query: { redirect: to.fullPath } };
        }
    }

    if (to.meta.guestOnly) {
        if (!user.value) {
            await resolveUser();
        }

        if (user.value) {
            return { path: '/dashboard' };
        }
    }
});

export default router;
