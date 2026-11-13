<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, watch } from 'vue';
import { useI18n } from '@/i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    universe: { type: Object, required: true },
    books: { type: Array, required: true },
});

const { t } = useI18n();

const searchQuery = ref('');
const searchResults = ref([]);
const searching = ref(false);
const searchTimer = ref(null);

function searchBooks() {
    const q = searchQuery.value.trim();
    if (q.length < 3) {
        searchResults.value = [];
        return;
    }

    clearTimeout(searchTimer.value);
    searching.value = true;

    searchTimer.value = setTimeout(async () => {
        try {
            const { data } = await axios.get('/books/lookup', { params: { q } });
            searchResults.value = data.results.filter((result) => {
                return !books.some((b) => b.id === result.id);
            });
        } catch (e) {
            searchResults.value = [];
        } finally {
            searching.value = false;
        }
    }, 400);
}

watch(searchQuery, () => {
    searchBooks();
});

function addBook(book) {
    const existingOrder = books.length > 0 ? Math.max(...books.map((b) => b.reading_order)) : 0;

    router.post(`/universes/${props.universe.id}/books`, {
        book_id: book.id,
        reading_order: existingOrder + 1,
    }, {
        onSuccess: () => {
            searchQuery.value = '';
            searchResults.value = [];
        },
    });
}

function moveBook(book, direction) {
    const index = books.findIndex((b) => b.id === book.id);
    if (direction === 'up' && index > 0) {
        swapOrders(index, index - 1);
    } else if (direction === 'down' && index < books.length - 1) {
        swapOrders(index, index + 1);
    }
}

function swapOrders(i, j) {
    const orders = books.map((b) => b.reading_order);
    const temp = orders[i];
    orders[i] = orders[j];
    orders[j] = temp;

    const payload = {};
    books.forEach((b, idx) => {
        payload[b.id] = orders[idx];
    });

    router.patch(`/universes/${props.universe.id}/reorder`, {
        orders: payload,
    });
}

function removeBook(bookId) {
    if (!confirm(t('ui.universe_remove_book_confirm'))) {
        return;
    }

    router.delete(`/universes/${props.universe.id}/books/${bookId}`, {
        onSuccess: () => {
            // La página se recarga vía Inertia
        },
    });
}
</script>

<template>
    <Head :title="t('ui.universe_detail', { name: universe.name })" />
    <AuthenticatedLayout>
        <div class="mx-auto w-full max-w-3xl">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-serif">{{ universe.name }}</h1>
                    <p v-if="universe.description" class="text-sm text-ink-muted">{{ universe.description }}</p>
                </div>
                <div class="flex gap-2">
                    <Link
                        :href="`/universes/${universe.id}/edit`"
                        class="rounded-control border border-edge px-3 py-2 text-sm text-ink hover:bg-surface-raised"
                    >
                        {{ t('ui.edit') }}
                    </Link>
                    <Link
                        href="/universes"
                        class="rounded-control bg-accent px-3 py-2 text-sm text-on-accent hover:bg-accent-strong"
                    >
                        {{ t('ui.back') }}
                    </Link>
                </div>
            </div>

            <div class="mb-6 flex items-center gap-3 text-sm text-ink-muted">
                <span class="rounded-full border border-edge px-2.5 py-0.5 text-xs font-medium">
                    {{ universe.type_label }}
                </span>
                <span>{{ t('ui.universe_books_count', { count: universe.total_books }) }}</span>
            </div>

            <div v-if="books.length === 0" class="rounded-card border border-edge bg-surface p-6 text-center text-ink-muted">
                {{ t('ui.universe_no_books') }}
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="book in books"
                    :key="book.id"
                    class="flex items-center gap-3 rounded-control border border-edge bg-surface p-3"
                >
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-accent text-on-accent font-serif font-bold text-sm shadow-soft">
                        {{ book.reading_order }}
                    </span>

                    <div class="flex h-16 w-11 shrink-0 items-center justify-center overflow-hidden rounded bg-surface-raised font-serif text-sm text-accent shadow-soft">
                        {{ book.title.charAt(0) }}
                        <img
                            v-if="book.cover_url"
                            :src="book.cover_url"
                            :alt="t('ui.cover_alt', { title: book.title })"
                            class="absolute inset-0 h-full w-full object-cover"
                            loading="lazy"
                            decoding="async"
                        >
                    </div>

                    <div class="min-w-0 flex-1">
                        <strong class="block font-serif text-base leading-snug">{{ book.title }}</strong>
                        <span class="block text-sm text-ink-muted">{{ book.author || t('ui.unknown_author') }}</span>
                        <span v-if="book.publisher || book.published_year" class="mt-0.5 block text-xs text-ink-muted/80">
                            {{ [book.publisher, book.published_year].filter(Boolean).join(' · ') }}
                        </span>
                        <span v-if="book.isbn" class="mt-0.5 block text-xs text-ink-muted/80">{{ book.isbn }}</span>
                    </div>

                    <div class="flex items-center gap-2 text-xs">
                        <span
                            :class="[
                                'text-xs font-medium rounded px-1.5 py-0.5',
                                book.reading_status === 'finished' ? 'bg-success/10 text-success' :
                                book.reading_status === 'reading' ? 'bg-accent-soft text-accent-strong' :
                                'bg-surface-raised text-ink-muted',
                            ]"
                        >
                            {{
                                book.reading_status === 'finished' ? t('ui.status_finished') :
                                book.reading_status === 'reading' ? t('ui.status_reading') :
                                t('ui.status_none')
                            }}
                        </span>
                        <div v-if="book.reading_progress > 0" class="w-16 bg-surface-raised rounded-full h-1.5 overflow-hidden">
                            <div
                                class="h-full bg-accent"
                                :style="{ width: book.reading_progress + '%' }"
                            ></div>
                        </div>
                    </div>

                    <div class="flex gap-1">
                        <button
                            type="button"
                            :disabled="book.reading_order === 1"
                            class="rounded-control border border-edge px-2 py-1 text-xs text-ink hover:bg-surface-raised"
                            @click="() => moveBook(book, 'up')"
                            :aria-label="t('ui.move_up')"
                        >
                            ↑
                        </button>
                        <button
                            type="button"
                            :disabled="book.reading_order === books.length"
                            class="rounded-control border border-edge px-2 py-1 text-xs text-ink hover:bg-surface-raised"
                            @click="() => moveBook(book, 'down')"
                            :aria-label="t('ui.move_down')"
                        >
                            ↓
                        </button>
                        <button
                            type="button"
                            class="rounded-control border border-danger px-2 py-1 text-xs text-danger hover:bg-danger/10"
                            @click="removeBook(book.id)"
                            :aria-label="t('ui.remove')"
                        >
                            ×
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="books.length > 0" class="mt-6 pt-6 border-t border-edge">
                <h3 class="text-sm font-medium text-ink mb-3">{{ t('ui.add_book_to_universe') }}</h3>

                <div class="flex flex-col gap-2 sm:flex-row items-end">
                    <input
                        v-model="searchQuery"
                        type="text"
                        class="w-full sm:w-64 rounded-control border border-edge bg-surface px-3 py-2 text-ink placeholder:text-ink-muted"
                        :placeholder="t('ui.search_placeholder')"
                    >
                    <div v-if="searching" class="flex-1">
                        <p class="text-xs text-ink-muted">{{ t('ui.searching') }}</p>
                    </div>
                    <button
                        v-if="!searching"
                        type="button"
                        class="rounded-control bg-accent px-4 py-2 text-on-accent hover:bg-accent-strong"
                        :disabled="searchQuery.value.trim().length < 3"
                    >
                        {{ t('ui.search') }}
                    </button>
                </div>

                <ul v-if="searchResults.length > 0" class="mt-2 space-y-1 max-h-48 overflow-y-auto border border-edge rounded-card bg-surface">
                    <li
                        v-for="result in searchResults"
                        :key="result.id"
                        class="flex items-start gap-2 rounded-control border-b border-edge/50 bg-surface p-2 last:border-b-0 hover:bg-surface-raised cursor-pointer"
                        @click="addBook(result)"
                    >
                        <span class="flex h-12 w-9 shrink-0 items-center justify-center overflow-hidden rounded bg-surface-raised font-serif text-xs text-accent">
                            {{ result.title.charAt(0) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <strong class="block text-sm font-serif leading-snug">{{ result.title }}</strong>
                            <span class="block text-xs text-ink-muted">{{ result.author || t('ui.unknown_author') }}</span>
                        </div>
                    </li>
                </ul>
                <p v-if="searchQuery.value.trim().length >= 3 && searchResults.length === 0 && !searching" class="mt-2 text-xs text-ink-muted">
                    {{ t('ui.no_results') }}
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
