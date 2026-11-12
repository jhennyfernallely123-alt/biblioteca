<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    books: { type: Array, required: true },
});

const { t } = useI18n();
const deleting = ref(null);

function confirmDelete(book) {
    deleting.value = book;
}

function destroy() {
    if (!deleting.value) {
        return;
    }

    router.delete(`/catalog/${deleting.value.id}`, {
        onFinish: () => {
            deleting.value = null;
        },
    });
}
</script>

<template>
    <Head :title="t('ui.catalog_heading')" />
    <AuthenticatedLayout>
        <div class="mx-auto w-full max-w-3xl">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl sm:text-3xl">{{ t('ui.catalog_heading') }}</h1>
                <Link
                    href="/catalog/create"
                    class="rounded-control bg-accent px-3 py-2 text-sm text-on-accent hover:bg-accent-strong sm:px-4 sm:text-base"
                >
                    {{ t('ui.catalog_add') }}
                </Link>
            </div>

            <p v-if="books.length === 0" class="rounded-card border border-edge bg-surface p-6 text-center text-ink-muted">
                {{ t('ui.catalog_empty') }}
            </p>

            <ul v-else class="space-y-2">
                <li
                    v-for="book in books"
                    :key="book.id"
                    class="flex items-start gap-3 rounded-control border border-edge bg-surface p-3"
                >
                    <span class="relative flex h-20 w-14 shrink-0 items-center justify-center overflow-hidden rounded bg-surface-raised font-serif text-xl text-accent shadow-soft">
                        {{ book.title.charAt(0) }}
                        <img
                            v-if="book.cover_url"
                            :src="book.cover_url"
                            :alt="t('ui.cover_alt', { title: book.title })"
                            class="absolute inset-0 h-full w-full object-cover"
                            loading="lazy"
                            decoding="async"
                        >
                    </span>

                    <div class="min-w-0 flex-1">
                        <strong class="block font-serif text-base leading-snug">{{ book.title }}</strong>
                        <span class="block text-sm text-ink-muted">{{ book.author || t('ui.unknown_author') }}</span>
                        <span v-if="book.publisher || book.published_year" class="mt-1 block text-xs text-ink-muted/80">
                            {{ [book.publisher, book.published_year].filter(Boolean).join(' · ') }}
                        </span>
                        <span v-if="book.isbn" class="mt-1 block text-xs text-ink-muted/80">{{ book.isbn }}</span>
                    </div>

                    <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                        <Link
                            :href="`/catalog/${book.id}/edit`"
                            class="rounded-control border border-edge px-3 py-2 text-sm text-ink hover:bg-surface-raised"
                        >
                            {{ t('ui.edit_book_title') }}
                        </Link>
                        <button
                            type="button"
                            class="rounded-control border border-danger px-3 py-2 text-sm text-danger hover:bg-danger/10"
                            @click="confirmDelete(book)"
                        >
                            {{ t('ui.delete') }}
                        </button>
                    </div>
                </li>
            </ul>
        </div>

        <Modal :show="deleting !== null" max-width="md" @close="deleting = null">
            <div class="p-6">
                <h2 class="font-serif text-lg">{{ t('ui.catalog_delete_title') }}</h2>
                <p class="mt-2 text-sm text-ink-muted">{{ t('ui.catalog_delete_confirm') }}</p>

                <div class="mt-4 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-control border border-edge px-4 py-2 text-ink hover:bg-surface-raised"
                        @click="deleting = null"
                    >
                        {{ t('ui.cancel') }}
                    </button>
                    <button
                        type="button"
                        class="rounded-control bg-danger px-4 py-2 text-on-accent hover:bg-danger-strong"
                        @click="destroy"
                    >
                        {{ t('ui.delete') }}
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
