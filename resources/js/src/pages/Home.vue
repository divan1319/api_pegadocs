<template>
    <UMain>
        <UPageHero
            orientation="horizontal"
            class="from-primary/5 via-default to-default border-default relative overflow-hidden border-b bg-linear-to-b"
            headline="Pegadocs"
            title="Ensambla PDFs en equipo, sin el caos de los archivos sueltos"
            description="Cuando un trabajo grupal exige que cada persona entregue su sección, alguien siempre termina uniendo PDFs a mano. Pegadocs estructura el proceso: cada miembro sube su parte, ordenas con arrastrar y soltar en el navegador —al estilo iLovePDF— y exportas el documento final."
            :links="heroLinks"
        >
            <div
                class="border-muted bg-elevated/50 text-muted relative hidden min-h-56 flex-col justify-center gap-4 rounded-xl border p-6 shadow-sm lg:flex"
                aria-hidden="true"
            >
                <div
                    class="ring-default flex items-center gap-3 rounded-lg bg-default/80 p-3 shadow-xs ring-1"
                >
                    <UIcon name="i-lucide-file-text" class="text-primary size-8 shrink-0" />
                    <div class="min-w-0 text-left">
                        <p class="text-highlighted text-sm font-medium">Informe_grupo.pdf</p>
                        <p class="text-xs">Listo para exportar</p>
                    </div>
                </div>
                <div
                    class="ring-default -ml-2 flex -rotate-4 items-center gap-2 rounded-lg bg-default/90 p-2 shadow-xs ring-1"
                >
                    <UBadge color="neutral" variant="subtle" size="sm">Parte A</UBadge>
                    <UBadge color="neutral" variant="subtle" size="sm">Parte B</UBadge>
                    <UBadge color="primary" variant="subtle" size="sm">Orden →</UBadge>
                </div>
                <p class="text-center text-xs">Ordena páginas con drag &amp; drop antes de exportar</p>
            </div>
        </UPageHero>

        <UPageSection
            headline="Cómo encaja en tu flujo"
            title="De entregas sueltas a un solo PDF"
            description="Pensado para equipos que ya colaboran y solo necesitan cerrar el entregable final sin fricción."
            orientation="vertical"
            :features="featureItems"
        />


        <UPageCTA
            :title="ctaTitle"
            :description="ctaDescription"
            :links="ctaLinks"
            class="border-default border-t"
        />

        <UFooter class="border-default border-t">
            <p class="text-muted text-sm">
                © 2026 Iván López. Pegadocs — licencia de uso personal y educativo; no distribución pública
                sin autorización.
            </p>
        </UFooter>
    </UMain>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';

const router = useRouter();
const { user, logout } = useAuth();

const featureItems = [
    {
        icon: 'i-lucide-users',
        title: 'Colaboración clara',
        description:
            'Organiza el trabajo en workspaces: cada persona sabe qué debe aportar y cuándo.',
    },
    {
        icon: 'i-lucide-upload',
        title: 'Cada quien sube su parte',
        description:
            'Las secciones llegan al mismo lugar; se acabó perseguir adjuntos por correo o chats.',
    },
    {
        icon: 'i-lucide-grip-vertical',
        title: 'Orden al instante',
        description:
            'Reordena y revisa el PDF final con arrastrar y soltar en el navegador, antes de descargar.',
    },
    {
        icon: 'i-lucide-circle-check',
        title: 'Un solo archivo final',
        description:
            'Exporta un PDF unificado listo para entregar, sin herramientas de terceros extra.',
    },
];

async function onLogout(): Promise<void> {
    await logout();
    await router.push('/');
}

const heroLinks = computed(() => {
    if (user.value) {
        return [
            { label: 'Ir al panel', to: '/dashboard' },
            {
                label: 'Cerrar sesión',
                color: 'neutral',
                variant: 'outline',
                onClick: () => {
                    void onLogout();
                },
            },
        ];
    }

    return [
        { label: 'Crear cuenta', to: '/register' },
        { label: 'Iniciar sesión', to: '/login', color: 'neutral', variant: 'outline' },
    ];
});

const ctaTitle = computed(() =>
    user.value ? 'Sigue donde lo dejaste' : 'Empieza a ensamblar con tu equipo',
);

const ctaDescription = computed(() =>
    user.value
        ? `Sesión como ${user.value.name}. Abre el panel para ver tus workspaces y tareas.`
        : 'Regístrate, crea o únete a un workspace y deja que Pegadocs unifique las piezas en un solo PDF.',
);

const ctaLinks = computed(() => {
    if (user.value) {
        return [{ label: 'Abrir panel', to: '/dashboard' }];
    }

    return [
        { label: 'Registrarse gratis', to: '/register' },
        { label: 'Ya tengo cuenta', to: '/login', color: 'neutral', variant: 'outline' },
    ];
});
</script>
