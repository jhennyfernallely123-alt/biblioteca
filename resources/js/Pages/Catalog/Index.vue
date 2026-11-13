<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    books: { type: Object, required: true },
});

const { t } = useI18n();
const deleting = ref(null);

function goPage(page) {
    router.get(route('catalog.index'), { page }, { preserveScroll: true });
}

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

const pagination = computed(() => {
    const current = props.books.current_page;
    const last = props.books.last_page;

    if (last <= 1) {
        return [];
    }

    const items = [];
    const pushPage = (n) => items.push({ type: 'page', value: n, label: String(n) });
    const pushEllipsis = () => items.push({ type: 'ellipsis', value: null, label: '…' });

    const showFirst = current > 3;
    const showLast = current < last - 2;

    pushPage(1);

    if (showFirst) {
        pushEllipsis();
    }

    const start = Math.max(2, current - 1);
    const end = Math.min(last - 1, current + 1);

    for (let n = start; n <= end; n++) {
        if (n === 1 && showFirst) {
            continue;
        }
        if (n === last && showLast) {
            continue;
        }
        pushPage(n);
    }

    if (showLast) {
        pushEllipsis();
    }

    pushPage(last);

    return items;
});
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

            <p v-if="books.data.length === 0" class="rounded-card border border-edge bg-surface p-6 text-center text-ink-muted">
                {{ t('ui.catalog_empty') }}
            </p>

            <ul v-else class="space-y-2">
                <li
                    v-for="book in books.data"
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

            <div v-if="pagination.length" class="mt-6 flex items-center justify-center gap-1">
                <button
                    type="button"
                    :disabled="books.current_page === 1"
                    class="rounded-full border border-edge h-9 w-9 flex items-center justify-center text-sm transition-colors"
                    :class="{
                        'opacity-40 cursor-not-allowed text-ink-muted border-edge': books.current_page === 1,
                        'border-accent text-accent hover:bg-accent-soft': books.current_page > 1,
                    }"
                    @click="goPage(books.current_page - 1)"
                    aria-label="{{ t('ui.catalog_prev') }}"
                >
                    <span aria-hidden="true">←</span>
                </button>

                <template v-for="item in pagination" :key="item.type + '-' + item.value">
                    <span v-if="item.type === 'ellipsis'" class="text-ink-muted">
                        {{ item.label }}
                    </span>

                    <button
                        v-else
                        type="button"
                        :aria-current="item.value === books.current_page ? 'page' : undefined"
                        :aria-label="item.value === books.current_page ? t('ui.catalog_page', { current: item.value, last: books.last_page }) : item.label"
                        class="rounded-full border h-9 w-9 flex items-center justify-center text-sm font-medium transition-colors"
                        :class="{
                            'bg-accent text-on-accent border-accent shadow-glow-accent': item.value === books.current_page,
                            'border-edge text-ink hover:bg-surface-raised hover:border-accent': item.value !== books.current_page,
                        }"
                        @click="goPage(item.value)"
                    >
                        {{ item.label }}
                    </button>
                </template>

                <button
                    type="button"
                    :disabled="books.current_page === books.last_page"
                    class="rounded-full border border-edge h-9 w-9 flex items-center justify-center text-sm transition-colors"
                    :class="{
                        'opacity-40 cursor-not-allowed text-ink-muted border-edge': books.current_page === books.last_page,
                        'border-accent text-accent hover:bg-accent-soft': books.current_page < books.last_page,
                    }"
                    @click="goPage(books.current_page + 1)"
                    aria-label="{{ t('ui.catalog_next') }}"
                >
                    <span aria-hidden="true">→</span>
                </button>
            </div>
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
                        class="rounded-control bg-accent px-4 py-2 text-on-accent hover:bg-accent-strong"
                        @click="destroy"
                    >
                        {{ t('ui.delete') }}
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
