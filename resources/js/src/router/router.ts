import { createRouter, createWebHistory } from "vue-router";

const router = createRouter({
    history: createWebHistory(),
    routes: [
        //ruta home
        {
            path: '/',
            component: () => import('@/pages/Home.vue'),
        },
        //ruta 404
        {
            path: '/:pathMatch(.*)*',
            component: () => import('@/pages/404.vue'),
        },
    ],
});

export default router;