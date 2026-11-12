<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import CatalogBookForm from '@/Components/CatalogBookForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    shelves: { type: Array, required: true },
});

const { t } = useI18n();

const form = useForm({
    title: '',
    author: '',
    isbn: '',
    publisher: '',
    published_year: null,
    shelf_id: props.shelves[0]?.id ?? null,
    cover: null,
    cover_url: null,
});

function submit() {
    form.post('/catalog', { forceFormData: true });
}
</script>

<template>
    <Head :title="t('ui.catalog_add')" />
    <AuthenticatedLayout>
        <div class="mx-auto w-full max-w-3xl">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl sm:text-3xl">{{ t('ui.catalog_add') }}</h1>
                <Link
                    href="/catalog"
                    class="rounded-control bg-accent px-3 py-2 text-sm text-on-accent hover:bg-accent-strong sm:px-4 sm:text-base"
                >
                    {{ t('ui.back') }}
                </Link>
            </div>

            <div
                v-if="props.shelves.length === 0"
                class="rounded-card border border-edge bg-surface p-4 text-sm text-ink-muted"
            >
                {{ t('ui.catalog_no_shelves') }}
                <Link href="/shelves" class="text-accent underline">{{ t('ui.nav_shelves') }}</Link>
            </div>

            <form v-else class="w-full" @submit.prevent="submit">
                <CatalogBookForm :form="form" :shelves="props.shelves" show-shelf />

                <button
                    type="submit"
                    class="mt-4 rounded-control bg-accent px-4 py-2 text-on-accent hover:bg-accent-strong"
                    :disabled="form.processing"
                >
                    {{ t('ui.save') }}
                </button>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
