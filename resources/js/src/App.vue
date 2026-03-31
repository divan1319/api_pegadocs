<template>
    <UApp>
        <RouterView />
    </UApp>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { useAuth } from '@/composables/useAuth';

const { resolveUser } = useAuth();

onMounted(() => {
    void resolveUser();
});
</script>