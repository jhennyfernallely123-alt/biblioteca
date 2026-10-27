<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Shelf from '@/Components/Shelf.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useI18n } from '@/i18n';

defineProps({ shelves: { type: Array, required: true } });

const { t } = useI18n();
</script>

<template>
    <Head :title="t('ui.library_title')" />
    <AuthenticatedLayout>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-3xl md:text-4xl">{{ t('ui.library_title') }}</h1>
            <Link href="/books/create" class="rounded-control bg-oliva px-4 py-2 text-crema hover:bg-oliva-hover">
                {{ t('ui.add_book') }}
            </Link>
        </div>

        <div class="space-y-8">
            <Shelf v-for="shelf in shelves" :key="shelf.id" :shelf="shelf" />
        </div>
    </AuthenticatedLayout>
</template>
