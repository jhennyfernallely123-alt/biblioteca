<script setup>
import axios from 'axios';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    show: { type: Boolean, default: false },
    universe: { type: Object, required: true },
});

const emit = defineEmits(['close']);

const { t } = useI18n();

const books = ref([]);
const loading = ref(false);
const loaded = ref(false);
const query = ref('');
const selected = ref([]);
const error = ref(null);
const submitting = ref(false);

let debounce = null;
let requestToken = 0;

const selectedCount = computed(() => selected.value.length);

const hasSelectable = computed(() => books.value.some((book) => !book.in_collection));

const allLinked = computed(
    () => loaded.value && books.value.length > 0 && !hasSelectable.value && !query.value.trim(),
);

function reset() {
    books.value = [];
    query.value = '';
    selected.value = [];
    error.value = null;
    loaded.value = false;
}

async function load() {
    const token = ++requestToken;

    loading.value = true;
    error.value = null;

    try {
        const { data } = await axios.get(`/universes/${props.universe.id}/available-books`, {
            params: query.value.trim() ? { q: query.value.trim() } : {},
        });

        if (token !== requestToken) {
            return;
        }

        books.value = data.books;

        selected.value = selected.value.filter((id) =>
            data.books.some((book) => book.id === id && !book.in_collection),
        );

        loaded.value = true;
    } catch {
        if (token === requestToken) {
            error.value = t('ui.universe_load_error');
        }
    } finally {
        if (token === requestToken) {
            loading.value = false;
        }
    }
}

watch(
    () => props.show,
    (open) => {
        if (open) {
            reset();
            load();
        }
    },
);

watch(query, () => {
    clearTimeout(debounce);
    debounce = setTimeout(load, 250);
});

onBeforeUnmount(() => clearTimeout(debounce));

function toggle(book) {
    if (book.in_collection) {
        return;
    }

    selected.value = selected.value.includes(book.id)
        ? selected.value.filter((id) => id !== book.id)
        : [...selected.value, book.id];
}

function submit() {
    if (!selectedCount.value || submitting.value) {
        return;
    }

    submitting.value = true;

    router.post(
        `/universes/${props.universe.id}/books`,
        { book_ids: selected.value },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => emit('close'),
            onFinish: () => {
                submitting.value = false;
            },
        },
    );
}
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="emit('close')">
        <div class="flex max-h-[85vh] flex-col">
            <div class="border-b border-edge px-5 py-4">
                <h2 class="font-serif text-lg text-heading">
                    {{ t('ui.universe_add_books_title', { name: universe.name }) }}
                </h2>
                <p class="mt-1 text-xs text-ink-muted">{{ t('ui.universe_add_books_help') }}</p>
            </div>

            <div class="px-5 py-3">
                <div class="relative">
                    <svg
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-soft"
                        viewBox="0 0 20 20"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.6"
                        aria-hidden="true"
                    >
                        <circle cx="8.5" cy="8.5" r="5.5" />
                        <path d="m13 13 4 4" stroke-linecap="round" />
                    </svg>
                    <input
                        v-model="query"
                        type="search"
                        class="w-full rounded-control border border-edge bg-surface py-2 pl-9 pr-3 text-sm text-ink placeholder:text-ink-soft focus:border-accent focus:ring-accent"
                        :placeholder="t('ui.universe_add_books_search')"
                    >
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-5 pb-3">
                <p v-if="loading" class="py-6 text-center text-sm text-ink-muted">
                    {{ t('ui.universe_loading') }}
                </p>

                <p v-else-if="error" class="py-6 text-center text-sm text-danger">{{ error }}</p>

                <p v-else-if="allLinked" class="py-6 text-center text-sm text-ink-muted">
                    {{ t('ui.universe_available_none') }}
                </p>

                <p v-else-if="books.length === 0" class="py-6 text-center text-sm text-ink-muted">
                    {{ t('ui.universe_available_empty') }}
                </p>

                <ul v-else class="space-y-2">
                    <li v-for="book in books" :key="book.id">
                        <label
                            class="flex items-center gap-3 rounded-control border p-2 transition-colors"
                            :class="book.in_collection
                                ? 'cursor-not-allowed border-edge/60 bg-surface-sunken/60 opacity-70'
                                : 'cursor-pointer border-edge bg-surface hover:border-accent hover:bg-surface-raised'"
                        >
                            <input
                                type="checkbox"
                                class="h-4 w-4 shrink-0 rounded border-edge text-accent focus:ring-accent"
                                :checked="selected.includes(book.id)"
                                :disabled="book.in_collection"
                                @change="toggle(book)"
                            >

                            <span class="relative flex h-14 w-10 shrink-0 items-center justify-center overflow-hidden rounded bg-surface-sunken font-serif text-sm text-accent">
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

                            <span
                                v-if="book.in_collection"
                                class="shrink-0 rounded-control border border-edge bg-surface px-2 py-1 text-[11px] font-medium text-ink-muted"
                            >
                                {{ t('ui.universe_already_in_collection') }}
                            </span>
                        </label>
                    </li>
                </ul>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-edge bg-surface-raised px-5 py-3">
                <span class="text-sm text-ink-muted">
                    {{ t('ui.universe_selected', { count: selectedCount }) }}
                </span>

                <div class="flex gap-2">
                    <button
                        type="button"
                        class="rounded-control border border-edge bg-surface px-4 py-2 text-sm text-ink hover:border-edge-strong"
                        @click="emit('close')"
                    >
                        {{ t('ui.cancel') }}
                    </button>
                    <button
                        type="button"
                        class="rounded-control bg-accent px-4 py-2 text-sm font-medium text-on-accent hover:bg-accent-strong disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="!selectedCount || submitting"
                        @click="submit"
                    >
                        {{ t('ui.universe_add_selected') }}
                    </button>
                </div>
            </div>
        </div>
    </Modal>
</template>
