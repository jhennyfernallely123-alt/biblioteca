<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardAlbum from '@/Components/CardAlbum.vue';
import { useI18n } from '@/i18n';

defineProps({
    cards: { type: Array, required: true },
    progress: { type: Object, required: true },
    ink: { type: Number, default: 0 },
});

const { t } = useI18n();
</script>

<template>
    <Head :title="t('ui.cards_album')" />

    <AuthenticatedLayout>
        <header class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <h1 class="font-serif text-3xl text-accent">{{ t('ui.cards_album') }}</h1>
            <div class="card-ink" :title="t('ui.cards_ink_help')">
                <span class="text-sm text-ink-muted">{{ t('ui.cards_ink') }}</span>
                <strong class="text-2xl text-accent">{{ ink }}</strong>
                <span class="sr-only">{{ t('ui.cards_ink_help') }}</span>
            </div>
        </header>

        <CardAlbum :cards="cards" :progress="progress" />
    </AuthenticatedLayout>
</template>
