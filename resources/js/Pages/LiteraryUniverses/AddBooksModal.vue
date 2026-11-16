<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    show: { type: Boolean, default: false },
    universe: { type: Object, required: true },
});

const emit = defineEmits(['close', 'added']);

const { t } = useI18n();

const results = ref([]);
const loading = ref(false);
const query = ref('');
const selectedBooks = ref([]);
const submitting = ref(false);
const error = ref(null);

let debounce = null;
let requestToken = 0;

const selectedCount = computed(() => selectedBooks.value.length);

const universeIsbnSet = computed(() => new Set(
    (props.universe.books || []).map((book) => book.isbn).filter(Boolean),
));

const universeKeySet = computed(() => new Set(
    (props.universe.books || []).map((book) => `${book.title}|${book.author || ''}`),
));

const selectedKeySet = computed(() => new Set(selectedBooks.value.map(bookKey)));

function bookKey(book) {
    return book.isbn || `${book.title}|${book.author || ''}`;
}

function isLinked(book) {
    if (book.isbn && universeIsbnSet.value.has(book.isbn)) {
        return true;
    }

    return universeKeySet.value.has(bookKey(book));
}

function isSelected(book) {
    return selectedKeySet.value.has(bookKey(book));
}

function reset() {
    results.value = [];
    query.value = '';
    selectedBooks.value = [];
    error.value = null;
}

async function search() {
    const value = query.value.trim();

    if (value.length < 3) {
        results.value = [];
        return;
    }

    const token = ++requestToken;

    loading.value = true;
    error.value = null;

    try {
        const { data } = await window.axios.get('/books/lookup', {
            params: { q: value },
            timeout: 8000,
        });

        if (token !== requestToken) {
            return;
        }

        const seen = new Set();
        results.value = (Array.isArray(data.results) ? data.results : []).filter((book) => {
            const key = bookKey(book);

            if (seen.has(key)) {
                return false;
            }

            seen.add(key);

            return true;
        });
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
        }
    },
);

watch(query, () => {
    clearTimeout(debounce);
    debounce = setTimeout(search, 250);
});

onBeforeUnmount(() => clearTimeout(debounce));

function toggle(book) {
    if (!book || isLinked(book)) {
        return;
    }

    const key = bookKey(book);

    if (selectedKeySet.value.has(key)) {
        selectedBooks.value = selectedBooks.value.filter((item) => bookKey(item) !== key);
    } else {
        selectedBooks.value = [...selectedBooks.value, book];
    }
}

function removeSelected(book) {
    const key = bookKey(book);
    selectedBooks.value = selectedBooks.value.filter((item) => bookKey(item) !== key);
}

async function submit() {
    if (!selectedCount.value || submitting.value) {
        return;
    }

    submitting.value = true;
    error.value = null;

    let added = selectedBooks.value.length;

    try {
        const { data } = await window.axios.post(
            `/universes/${props.universe.id}/books/lookup`,
            { books: selectedBooks.value },
        );

        added = data.count ?? added;
    } catch (error_) {
        console.error('fromLookup error:', error_);
        const serverMessage = error_.response?.data?.message
            ?? error_.response?.data?.errors
            ?? null;
        error.value = serverMessage
            ? (typeof serverMessage === 'string' ? serverMessage : Object.values(serverMessage).flat().join(' '))
            : `Error ${error_.response?.status ?? 'desconocido'}: ${error_.response?.statusText ?? 'inténtalo de nuevo'}`;

        return;
    } finally {
        submitting.value = false;
    }

    try {
        emit('added', added);
    } catch (handlerError) {
        console.error('added handler error:', handlerError);
        emit('close');
    }
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

            <div class="border-b border-edge px-5 py-3">
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

                <div v-if="selectedCount" class="mt-3">
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-ink-muted">
                        {{ t('ui.universe_selection') }}
                    </p>
                    <ul class="flex flex-wrap gap-2">
                        <li
                            v-for="book in selectedBooks"
                            :key="bookKey(book)"
                            class="flex items-center gap-2 rounded-control border border-accent/40 bg-accent-soft px-2 py-1"
                        >
                            <span class="max-w-[14rem] truncate text-xs text-heading">{{ book.title }}</span>
                            <button
                                type="button"
                                class="text-ink-muted hover:text-danger"
                                :aria-label="t('ui.universe_remove_one')"
                                @click="removeSelected(book)"
                            >
                                <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="m4 4 8 8M12 4l-8 8" stroke-linecap="round" />
                                </svg>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-3">
                <p v-if="loading" class="py-6 text-center text-sm text-ink-muted">
                    {{ t('ui.universe_loading') }}
                </p>

                <p v-else-if="error" class="py-6 text-center text-sm text-danger">{{ error }}</p>

                <p v-else-if="query.trim().length > 0 && results.length === 0" class="py-6 text-center text-sm text-ink-muted">
                    {{ t('ui.universe_available_empty') }}
                </p>

                <ul v-else-if="results.length" class="space-y-2">
                    <li v-for="book in results" :key="bookKey(book)">
                        <label
                            class="flex items-center gap-3 rounded-control border p-2 transition-colors cursor-pointer"
                            :class="isLinked(book)
                                ? 'cursor-not-allowed border-edge/60 bg-surface-sunken/60 opacity-70'
                                : (isSelected(book)
                                    ? 'border-accent bg-accent-soft'
                                    : 'border-edge bg-surface hover:border-accent hover:bg-surface-raised')"
                        >
                            <input
                                type="checkbox"
                                class="h-4 w-4 shrink-0 rounded border-edge text-accent focus:ring-accent"
                                :checked="isSelected(book)"
                                :disabled="isLinked(book)"
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
                                <span v-if="book.isbn" class="block truncate text-[10px] text-ink-soft">
                                    {{ book.isbn }}
                                </span>
                            </span>

                            <span
                                v-if="isLinked(book)"
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
                        {{ submitting ? t('ui.universe_loading') : t('ui.universe_add_selected') }}
                    </button>
                </div>
            </div>
        </div>
    </Modal>
</template>
