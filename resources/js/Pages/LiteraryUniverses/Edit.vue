<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AddBooksModal from '@/Pages/LiteraryUniverses/AddBooksModal.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    universe: { type: Object, required: true },
    books: { type: Array, required: true },
    types: { type: Array, required: true },
    openAdd: { type: Boolean, default: false },
});

const { t } = useI18n();

const form = useForm({
    name: props.universe.name,
    type: props.universe.type,
});

const addingBooks = ref(props.openAdd);
const removing = ref(null);

const orderedBooks = computed(
    () => [...props.books].sort((a, b) => a.reading_order - b.reading_order),
);

function handleAdded() {
    addingBooks.value = false;
    router.get(route('literary-universes.edit', props.universe.id), {}, {
        preserveScroll: true,
        preserveState: true,
    });
}

function move(book, direction) {
    const list = [...orderedBooks.value];
    const index = list.findIndex((item) => item.id === book.id);
    const target = index + direction;

    if (index < 0 || target < 0 || target >= list.length) {
        return;
    }

    [list[index], list[target]] = [list[target], list[index]];

    const orders = {};

    list.forEach((item, position) => {
        orders[item.id] = position + 1;
    });

    router.patch(route('literary-universes.reorder', props.universe.id), { orders }, {
        preserveScroll: true,
    });
}

function confirmRemove(book) {
    removing.value = book;
}

function removeBook() {
    if (!removing.value) {
        return;
    }

    const book = removing.value;
    removing.value = null;

    router.delete(route('literary-universes.books.destroy', [props.universe.id, book.id]), {
        preserveScroll: true,
    });
}

watch(() => form.type, () => {
    if (!props.types.some((type) => type.key === form.type)) {
        form.type = props.types[0]?.key ?? 'series';
    }
});
</script>

<template>
    <Head :title="t('ui.edit_universe')" />
    <AuthenticatedLayout>
        <div class="mx-auto w-full max-w-2xl">
            <div class="mb-6 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-soft">{{ t('ui.edit_universe') }}</p>
                    <h1 class="truncate font-serif text-2xl text-heading sm:text-3xl">{{ universe.name }}</h1>
                </div>
                <Link
                    :href="`/universes/${universe.id}`"
                    class="shrink-0 rounded-control border border-edge px-3 py-2 text-sm text-ink hover:bg-surface-raised sm:px-4"
                >
                    {{ t('ui.back') }}
                </Link>
            </div>

            <form
                class="space-y-6"
                @submit.prevent="form.patch(route('literary-universes.update', universe.id))"
            >
                <div class="space-y-5 rounded-card border border-edge bg-surface p-5 shadow-soft">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink">
                            {{ t('ui.universe_name') }}
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-control border border-edge bg-surface px-3 py-2 text-ink placeholder:text-ink-soft focus:border-accent focus:ring-accent"
                            :placeholder="t('ui.universe_name_placeholder')"
                            :class="{ 'border-danger': form.errors.name }"
                        >
                        <p v-if="form.errors.name" class="mt-1 text-xs text-danger">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <span class="mb-1 block text-sm font-medium text-ink">
                            {{ t('ui.universe_type') }}
                        </span>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="type in types"
                                :key="type.key"
                                type="button"
                                class="rounded-full border px-3 py-1.5 text-sm transition-colors"
                                :class="form.type === type.key
                                    ? 'border-accent bg-accent text-on-accent'
                                    : 'border-edge text-ink hover:border-accent hover:text-accent'"
                                @click="form.type = type.key"
                            >
                                {{ type.label }}
                            </button>
                        </div>
                        <p v-if="form.errors.type" class="mt-1 text-xs text-danger">{{ form.errors.type }}</p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex w-full items-center justify-center gap-1.5 rounded-control bg-accent px-4 py-2 text-sm font-medium text-on-accent hover:bg-accent-strong disabled:opacity-60 sm:w-auto"
                        :disabled="form.processing"
                        @click="addingBooks = true"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M10 4v12M4 10h12" stroke-linecap="round" />
                        </svg>
                        {{ t('ui.universe_add_books') }}
                    </button>
                </div>

                <section class="rounded-card border border-edge bg-surface p-5 shadow-soft">
                    <div class="mb-3 flex items-baseline justify-between gap-3">
                        <h2 class="font-serif text-lg text-heading">{{ t('ui.universe_books_section') }}</h2>
                        <span class="text-xs text-ink-muted">
                            {{ t('ui.universe_books_count', { count: orderedBooks.length }) }}
                        </span>
                    </div>

                    <p v-if="!orderedBooks.length" class="py-4 text-sm text-ink-muted">
                        {{ t('ui.universe_no_books') }}
                    </p>

                    <ul v-else class="space-y-2.5">
                        <li
                            v-for="(book, index) in orderedBooks"
                            :key="book.id"
                            class="flex items-center gap-3 rounded-full border border-edge bg-surface py-1.5 pl-2 pr-4 shadow-soft transition-colors hover:border-dorado/60 hover:bg-surface-raised"
                        >
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-dorado/40 bg-crema font-serif text-sm font-bold tabular-nums text-madera shadow-soft">
                                {{ index + 1 }}
                            </span>

                            <span class="relative flex h-14 w-10 shrink-0 items-center justify-center overflow-hidden rounded-control bg-surface-sunken font-serif text-sm text-madera ring-1 ring-edge/60">
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

                            <span class="min-w-0 flex-1">
                                <strong class="block truncate font-serif text-sm leading-snug text-heading">
                                    {{ book.title }}
                                </strong>
                                <span class="block truncate text-xs text-ink-muted">
                                    {{ book.author || t('ui.unknown_author') }}
                                </span>
                            </span>

                            <span class="flex shrink-0 items-center gap-1">
                                <button
                                    type="button"
                                    class="rounded-control border border-edge p-1.5 text-ink-muted hover:border-edge-strong hover:text-ink disabled:cursor-not-allowed disabled:opacity-40"
                                    :disabled="index === 0"
                                    :aria-label="t('ui.move_up')"
                                    @click="move(book, -1)"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M10 15V5m0 0-4 4m4-4 4 4" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    class="rounded-control border border-edge p-1.5 text-ink-muted hover:border-edge-strong hover:text-ink disabled:cursor-not-allowed disabled:opacity-40"
                                    :disabled="index === orderedBooks.length - 1"
                                    :aria-label="t('ui.move_down')"
                                    @click="move(book, 1)"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M10 5v10m0 0 4-4m-4 4-4-4" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    class="rounded-control border border-edge p-1.5 text-ink-muted hover:border-danger hover:text-danger"
                                    :aria-label="t('ui.universe_remove_book')"
                                    :title="t('ui.universe_remove_book')"
                                    @click="confirmRemove(book)"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                        <path d="M3.5 6h13" stroke-linecap="round" />
                                        <path d="M8 6V4.5h4V6" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M5.5 6v9.5A1.5 1.5 0 0 0 7 17h6a1.5 1.5 0 0 0 1.5-1.5V6" stroke-linejoin="round" />
                                        <path d="M8.5 9v5M11.5 9v5" stroke-linecap="round" />
                                    </svg>
                                </button>
                            </span>
                        </li>
                    </ul>
                </section>

                <div class="flex justify-end gap-2">
                    <Link
                        :href="`/universes/${universe.id}`"
                        class="rounded-control border border-edge px-4 py-2 text-ink hover:bg-surface-raised"
                    >
                        {{ t('ui.cancel') }}
                    </Link>
                    <button
                        type="submit"
                        class="rounded-control bg-accent px-4 py-2 text-on-accent hover:bg-accent-strong disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        {{ t('ui.save') }}
                    </button>
                </div>
            </form>

            <AddBooksModal
                v-if="addingBooks"
                :show="true"
                :universe="{ ...universe, books: orderedBooks }"
                @close="addingBooks = false"
                @added="handleAdded"
            />

            <Modal v-if="removing" :show="true" max-width="md" @close="removing = null">
                <div class="p-6">
                    <h2 class="font-serif text-lg text-heading">{{ t('ui.universe_remove_book') }}</h2>
                    <p class="mt-2 text-sm text-ink-muted">{{ t('ui.universe_remove_book_confirm') }}</p>
                    <div class="mt-5 flex justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-control border border-edge px-4 py-2 text-sm text-ink hover:bg-surface-raised"
                            @click="removing = null"
                        >
                            {{ t('ui.cancel') }}
                        </button>
                        <button
                            type="button"
                            class="rounded-control bg-danger px-4 py-2 text-sm font-medium text-white hover:opacity-90"
                            @click="removeBook"
                        >
                            {{ t('ui.remove') }}
                        </button>
                    </div>
                </div>
            </Modal>
        </div>
    </AuthenticatedLayout>
</template>
