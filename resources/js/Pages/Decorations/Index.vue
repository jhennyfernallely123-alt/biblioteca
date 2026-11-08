<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DecorationCatalog from '@/Components/DecorationCatalog.vue';
import { useI18n } from '@/i18n';

defineProps({ decorations: { type: Array, default: () => [] } });

const { t } = useI18n();
</script>

<template>
    <Head :title="t('ui.decorations_heading')" />
    <AuthenticatedLayout>
        <h1 class="mb-2 text-3xl md:text-4xl">{{ t('ui.decorations_heading') }}</h1>
        <p class="mb-6 text-sm text-ink-muted">{{ t('ui.decorations_help') }}</p>

        <DecorationCatalog :decorations="decorations" />
    </AuthenticatedLayout>
</template>
