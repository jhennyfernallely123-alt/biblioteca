<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AddBooksModal from '@/Pages/LiteraryUniverses/AddBooksModal.vue';
import Modal from '@/Components/Modal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    universe: { type: Object, required: true },
    books: { type: Array, required: true },
    canManage: { type: Boolean, default: false },
    openAdd: { type: Boolean, default: false },
});

const { t } = useI18n();

onMounted(() => {
    if (props.openAdd && props.canManage) {
        addingBooks.value = true;
    }
});

const removing = ref(null);
const deletingUniverse = ref(false);
const addingBooks = ref(false);

const readCount = computed(
    () => props.books.filter((book) => book.reading_status === 'finished').length,
);

const progress = computed(() => {
    if (props.books.length === 0) {
        return 0;
    }

    const total = props.books.reduce((sum, book) => sum + (Number(book.reading_progress) || 0), 0);

    return Math.round(total / props.books.length);
});

function moveBook(book, direction) {
    const list = [...props.books];
    const index = list.findIndex((item) => item.id === book.id);
    const target = direction === 'up' ? index - 1 : index + 1;

    if (index < 0 || target < 0 || target >= list.length) {
        return;
    }

    [list[index], list[target]] = [list[target], list[index]];

    const orders = {};
    list.forEach((item, position) => {
        orders[item.id] = position + 1;
    });

    router.patch(`/universes/${props.universe.id}/reorder`, { orders }, { preserveScroll: true });
}

function confirmRemove(book) {
    removing.value = book;
}

function removeBook() {
    if (!removing.value) {
        return;
    }

    router.delete(`/universes/${props.universe.id}/books/${removing.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            removing.value = null;
        },
    });
}

function destroyUniverse() {
    router.delete(`/universes/${props.universe.id}`, {
        onFinish: () => {
            deletingUniverse.value = false;
        },
    });
}

function statusClass(status) {
    if (status === 'finished') {
        return 'bg-success/10 text-success';
    }

    if (status === 'reading') {
        return 'bg-accent-soft text-accent-strong';
    }

    return 'bg-surface-sunken text-ink-muted';
}

function statusLabel(status) {
    if (status === 'finished') {
        return t('ui.status_finished');
    }

    if (status === 'reading') {
        return t('ui.status_reading');
    }

    return t('ui.status_none');
}
</script>

<template>
    <Head :title="t('ui.universe_detail', { name: universe.name })" />
    <AuthenticatedLayout>
        <div class="mx-auto w-full max-w-3xl">
            <Link
                href="/universes"
                class="mb-4 inline-flex items-center gap-1.5 text-sm text-ink-muted hover:text-accent"
            >
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path d="M11 4 5 10l6 6" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                {{ t('ui.universe_back') }}
            </Link>

            <section class="mb-6 overflow-hidden rounded-card border border-edge bg-surface shadow-soft">
                <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-start sm:p-5">
                    <div class="relative flex h-40 w-28 shrink-0 items-center justify-center overflow-hidden rounded-card bg-bosque font-serif text-4xl text-pergamino/80 shadow-cover">
                        {{ universe.name.charAt(0) }}
                        <img
                            v-if="books[0]?.cover_url"
                            :src="books[0].cover_url"
                            :alt="t('ui.cover_alt', { title: universe.name })"
                            class="absolute inset-0 h-full w-full object-cover"
                            loading="lazy"
                            decoding="async"
                        >
                    </div>

                    <div class="min-w-0 flex-1">
                        <span class="inline-flex rounded-full border border-edge bg-surface-sunken px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-ink-muted">
                            {{ universe.type_label }}
                        </span>
                        <h1 class="mt-2 font-serif text-2xl leading-tight text-heading sm:text-3xl">
                            {{ universe.name }}
                        </h1>
                        <p v-if="universe.description" class="mt-1 text-sm text-ink-muted">
                            {{ universe.description }}
                        </p>

                        <div class="mt-4 grid grid-cols-3 gap-2">
                            <div class="rounded-control bg-surface-raised px-3 py-2">
                                <span class="block font-serif text-lg leading-none text-heading">{{ universe.total_books }}</span>
                                <span class="mt-1 block text-[11px] uppercase tracking-wide text-ink-soft">{{ t('ui.universe_books_label') }}</span>
                            </div>
                            <div class="rounded-control bg-surface-raised px-3 py-2">
                                <span class="block font-serif text-lg leading-none text-heading">{{ readCount }}</span>
                                <span class="mt-1 block text-[11px] uppercase tracking-wide text-ink-soft">{{ t('ui.universe_read_label') }}</span>
                            </div>
                            <div class="rounded-control bg-surface-raised px-3 py-2">
                                <span class="block font-serif text-lg leading-none text-heading">{{ progress }}%</span>
                                <span class="mt-1 block text-[11px] uppercase tracking-wide text-ink-soft">{{ t('ui.universe_progress_short') }}</span>
                            </div>
                        </div>

                        <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-surface-sunken">
                            <div class="h-full rounded-full bg-accent transition-all" :style="{ width: progress + '%' }"></div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-edge bg-surface-raised px-4 py-2.5">
                    <Link
                        v-if="canManage"
                        :href="`/universes/${universe.id}/edit`"
                        class="rounded-control border border-edge bg-surface px-3 py-1.5 text-sm text-ink hover:border-accent hover:text-accent"
                    >
                        {{ t('ui.edit_universe') }}
                    </Link>
                    <button
                        v-if="canManage"
                        type="button"
                        class="rounded-control border border-danger px-3 py-1.5 text-sm text-danger hover:bg-danger/10"
                        @click="deletingUniverse = true"
                    >
                        {{ t('ui.delete') }}
                    </button>
                </div>
            </section>

            <section v-if="canManage" class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-card border border-edge bg-surface p-4 shadow-soft">
                <div>
                    <h2 class="font-serif text-base text-heading">{{ t('ui.add_book_to_universe') }}</h2>
                    <p class="mt-0.5 text-xs text-ink-muted">{{ t('ui.universe_add_hint') }}</p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-control bg-accent px-4 py-2 text-sm font-medium text-on-accent hover:bg-accent-strong"
                    @click="addingBooks = true"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M10 4v12M4 10h12" stroke-linecap="round" />
                    </svg>
                    {{ t('ui.universe_add_books') }}
                </button>
            </section>

            <h2 class="mb-3 font-serif text-lg text-heading">{{ t('ui.universe_books_section') }}</h2>

            <div
                v-if="books.length === 0"
                class="rounded-card border border-dashed border-edge bg-surface p-8 text-center"
            >
                <p class="font-serif text-base text-heading">{{ t('ui.universe_empty_help') }}</p>
                <button
                    v-if="canManage"
                    type="button"
                    class="mt-4 inline-flex items-center gap-1.5 rounded-control bg-accent px-4 py-2 text-sm font-medium text-on-accent hover:bg-accent-strong"
                    @click="addingBooks = true"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M10 4v12M4 10h12" stroke-linecap="round" />
                    </svg>
                    {{ t('ui.universe_add_books') }}
                </button>
            </div>

            <ol v-else class="space-y-2.5">
                <li
                    v-for="book in books"
                    :key="book.id"
                    class="flex flex-wrap items-center gap-3 rounded-card border border-edge bg-surface p-3 shadow-soft sm:flex-nowrap"
                >
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-accent/30 bg-accent-soft font-serif text-sm font-bold text-accent-strong">
                        {{ book.reading_order }}
                    </span>

                    <span class="relative flex h-16 w-11 shrink-0 items-center justify-center overflow-hidden rounded bg-surface-sunken font-serif text-sm text-accent shadow-soft">
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
                        <strong class="block truncate font-serif text-base leading-snug text-heading">{{ book.title }}</strong>
                        <span class="block truncate text-sm text-ink-muted">{{ book.author || t('ui.unknown_author') }}</span>
                        <span v-if="book.publisher || book.published_year" class="mt-0.5 block truncate text-xs text-ink-soft">
                            {{ [book.publisher, book.published_year].filter(Boolean).join(' · ') }}
                        </span>

                        <div class="mt-1.5 flex items-center gap-2">
                            <span class="rounded px-1.5 py-0.5 text-[11px] font-medium" :class="statusClass(book.reading_status)">
                                {{ statusLabel(book.reading_status) }}
                            </span>
                            <span v-if="book.reading_progress > 0" class="h-1.5 w-16 overflow-hidden rounded-full bg-surface-sunken">
                                <span class="block h-full rounded-full bg-accent" :style="{ width: book.reading_progress + '%' }"></span>
                            </span>
                        </div>
                    </div>

                    <div v-if="canManage" class="flex shrink-0 items-center gap-1">
                        <button
                            type="button"
                            class="rounded-control border border-edge p-2 text-ink hover:bg-surface-raised disabled:cursor-not-allowed disabled:opacity-35"
                            :disabled="book.reading_order === 1"
                            :aria-label="t('ui.move_up')"
                            :title="t('ui.move_up')"
                            @click="moveBook(book, 'up')"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="M10 15V5m0 0-4 4m4-4 4 4" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="rounded-control border border-edge p-2 text-ink hover:bg-surface-raised disabled:cursor-not-allowed disabled:opacity-35"
                            :disabled="book.reading_order === books.length"
                            :aria-label="t('ui.move_down')"
                            :title="t('ui.move_down')"
                            @click="moveBook(book, 'down')"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="M10 5v10m0 0 4-4m-4 4-4-4" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="rounded-control border border-danger p-2 text-danger hover:bg-danger/10"
                            :aria-label="t('ui.universe_remove_book')"
                            :title="t('ui.universe_remove_book')"
                            @click="confirmRemove(book)"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="M4 10h12" stroke-linecap="round" />
                            </svg>
                        </button>
                    </div>
                </li>
            </ol>
        </div>

        <AddBooksModal
            v-if="canManage && addingBooks"
            :show="true"
            :universe="universe"
            @close="addingBooks = false"
        />

        <Modal v-if="removing" :show="true" max-width="md" @close="removing = null">
            <div class="p-6">
                <h2 class="font-serif text-lg text-heading">{{ t('ui.universe_remove_book') }}</h2>
                <p class="mt-2 text-sm text-ink-muted">{{ t('ui.universe_remove_book_confirm') }}</p>

                <div class="mt-5 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-control border border-edge px-4 py-2 text-ink hover:bg-surface-raised"
                        @click="removing = null"
                    >
                        {{ t('ui.cancel') }}
                    </button>
                    <button
                        type="button"
                        class="rounded-control bg-danger px-4 py-2 text-on-accent hover:bg-danger-strong"
                        @click="removeBook"
                    >
                        {{ t('ui.remove') }}
                    </button>
                </div>
            </div>
        </Modal>

        <Modal v-if="deletingUniverse" :show="true" max-width="md" @close="deletingUniverse = false">
            <div class="p-6">
                <h2 class="font-serif text-lg text-heading">{{ t('ui.universe_delete_title') }}</h2>
                <p class="mt-2 text-sm text-ink-muted">{{ t('ui.universe_delete_confirm') }}</p>

                <div class="mt-5 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-control border border-edge px-4 py-2 text-ink hover:bg-surface-raised"
                        @click="deletingUniverse = false"
                    >
                        {{ t('ui.cancel') }}
                    </button>
                    <button
                        type="button"
                        class="rounded-control bg-danger px-4 py-2 text-on-accent hover:bg-danger-strong"
                        @click="destroyUniverse"
                    >
                        {{ t('ui.delete') }}
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
