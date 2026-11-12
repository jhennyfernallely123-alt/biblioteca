<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import CatalogBookForm from '@/Components/CatalogBookForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    book: { type: Object, required: true },
});

const { t } = useI18n();

const form = useForm({
    title: props.book.title ?? '',
    author: props.book.author ?? '',
    isbn: props.book.isbn ?? '',
    publisher: props.book.publisher ?? '',
    published_year: props.book.published_year ?? null,
    cover: null,
    cover_url: null,
});

function submit() {
    form.patch(`/catalog/${props.book.id}`, {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="t('ui.catalog_edit_title')" />
    <AuthenticatedLayout>
        <div class="mx-auto w-full max-w-3xl">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl sm:text-3xl">{{ t('ui.catalog_edit_title') }}</h1>
                <Link
                    href="/catalog"
                    class="rounded-control bg-accent px-3 py-2 text-sm text-on-accent hover:bg-accent-strong sm:px-4 sm:text-base"
                >
                    {{ t('ui.back') }}
                </Link>
            </div>

            <form class="w-full" @submit.prevent="submit">
                <CatalogBookForm :form="form" :existing-cover-url="book.cover_url" />

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
