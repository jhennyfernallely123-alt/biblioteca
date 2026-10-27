<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { onBeforeUnmount, ref, watch } from 'vue';
import BarcodeScanner from '@/Components/BarcodeScanner.vue';
import RatingStars from '@/Components/RatingStars.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useI18n } from '@/i18n';

const props = defineProps({ shelves: { type: Array, required: true } });
const { t } = useI18n();

const tab = ref('search');
const scannerOpen = ref(false);
const results = ref([]);
const searching = ref(false);
const error = ref('');
let searchTimer = null;
let searchController = null;
let searchRequest = 0;

const form = useForm({
    title: '',
    author: '',
    isbn: '',
    publisher: '',
    published_year: null,
    shelf_id: props.shelves[0]?.id ?? null,
    rating: null,
    notes: '',
    cover: null,
    cover_url: null,
});

watch(tab, () => {
    scannerOpen.value = false;
    clearTimeout(searchTimer);
    searchController?.abort();
    searching.value = false;
});

watch(
    () => form.title,
    (value) => {
        if (tab.value !== 'search') {
            return;
        }

        clearTimeout(searchTimer);
        searchController?.abort();
        searchRequest++;

        if (value.trim().length < 3) {
            results.value = [];
            error.value = '';
            searching.value = false;

            return;
        }

        searchTimer = setTimeout(() => search(value), 400);
    },
);

onBeforeUnmount(() => {
    clearTimeout(searchTimer);
    searchController?.abort();
});

function requestError(e, fallback) {
    if (e.response?.status === 422) {
        return e.response.data.errors?.isbn?.[0]
            ?? e.response.data.errors?.q?.[0]
            ?? t('ui.check_data');
    }

    return fallback;
}

async function lookupIsbn(isbn) {
    searching.value = true;
    error.value = '';

    try {
        const { data } = await axios.get('/books/lookup', { params: { isbn } });
        results.value = data.results;
        if (data.results.length === 0) {
            error.value = t('ui.isbn_not_found');
        }
    } catch (e) {
        error.value = requestError(e, t('ui.catalog_error'));
    } finally {
        searching.value = false;
    }
}

async function search(query = form.title) {
    const value = query.trim();

    if (value.length < 3) {
        results.value = [];

        return;
    }

    clearTimeout(searchTimer);
    searchController?.abort();
    searchController = new AbortController();
    const request = ++searchRequest;

    searching.value = true;
    error.value = '';

    try {
        const { data } = await axios.get('/books/lookup', {
            params: { q: value },
            signal: searchController.signal,
        });

        if (request !== searchRequest) {
            return;
        }

        results.value = data.results;

        if (data.results.length === 0) {
            error.value = t('ui.no_results');
        }
    } catch (e) {
        if (request !== searchRequest || e.code === 'ERR_CANCELED') {
            return;
        }

        error.value = requestError(e, t('ui.catalog_error'));
    } finally {
        if (request === searchRequest) {
            searching.value = false;
        }
    }
}

function select(book) {
    form.title = book.title;
    form.author = book.author ?? '';
    form.isbn = book.isbn ?? '';
    form.publisher = book.publisher ?? '';
    form.published_year = book.published_year;
    form.cover_url = book.cover_url;
    scannerOpen.value = false;
    tab.value = 'manual';
}

function closeScanner() {
    scannerOpen.value = false;
}

function coverError(event, book) {
    const image = event.target;

    if (book.isbn && image.dataset.fallbackTried !== '1') {
        image.dataset.fallbackTried = '1';
        image.src = `https://covers.openlibrary.org/b/isbn/${book.isbn}-M.jpg?default=false`;

        return;
    }

    image.remove();
}

function onDetected(isbn) {
    scannerOpen.value = false;
    lookupIsbn(isbn);
}

function submit() {
    form.post('/books', { forceFormData: true });
}
</script>

<template>
    <Head :title="t('ui.add_book')" />
    <AuthenticatedLayout>
        <h1 class="mb-4 text-3xl">{{ t('ui.add_book') }}</h1>

        <div class="mb-4 flex gap-2">
            <button
                type="button"
                class="rounded-control px-3 py-2"
                :class="tab === 'search' ? 'bg-oliva text-crema' : 'bg-crema text-tinta'"
                @click="tab = 'search'"
            >
                {{ t('ui.search_tab') }}
            </button>
            <button
                type="button"
                class="rounded-control px-3 py-2"
                :class="tab === 'manual' ? 'bg-oliva text-crema' : 'bg-crema text-tinta'"
                @click="tab = 'manual'"
            >
                {{ t('ui.manual_tab') }}
            </button>
        </div>

        <section v-if="tab === 'search'" class="space-y-4">
            <div v-if="scannerOpen" class="space-y-2">
                <BarcodeScanner @detected="onDetected" />
                <button
                    type="button"
                    class="min-h-11 rounded-control border border-madera/35 px-4 py-2 text-sm text-tinta hover:bg-pergamino"
                    @click="closeScanner"
                >
                    {{ t('ui.close_camera') }}
                </button>
            </div>
            <button
                v-else
                type="button"
                class="min-h-11 rounded-control border border-madera/35 px-4 py-2 text-sm text-tinta hover:bg-pergamino"
                @click="scannerOpen = true"
            >
                {{ t('ui.scan_camera') }}
            </button>

            <div class="flex flex-col gap-2 sm:flex-row">
                <input
                    v-model="form.isbn"
                    class="w-full rounded-control border border-madera/35 bg-crema px-3 py-2"
                    :placeholder="t('ui.isbn')"
                    @keyup.enter="lookupIsbn(form.isbn)"
                >
                <button
                    type="button"
                    class="rounded-control bg-oliva px-4 py-2 text-crema hover:bg-oliva-hover"
                    :disabled="searching"
                    @click="lookupIsbn(form.isbn)"
                >
                    {{ t('ui.search_isbn') }}
                </button>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <input
                    v-model="form.title"
                    class="w-full rounded-control border border-madera/35 bg-crema px-3 py-2"
                    :placeholder="t('ui.search_placeholder')"
                    @keyup.enter="search()"
                >
                <button
                    type="button"
                    class="rounded-control bg-oliva px-4 py-2 text-crema hover:bg-oliva-hover"
                    :disabled="searching"
                    @click="search()"
                >
                    {{ t('ui.search') }}
                </button>
            </div>

            <p v-if="searching" class="text-sm text-tinta/60">{{ t('ui.searching') }}</p>
            <p v-else class="text-sm text-tinta/60">{{ t('ui.search_hint') }}</p>

            <p v-if="error" class="text-sm text-terracota">{{ error }}</p>

            <ul class="space-y-2">
                <li v-for="(book, index) in results" :key="index">
                    <button
                        type="button"
                        class="flex w-full items-start gap-3 rounded-control border border-madera/35 bg-crema p-3 text-left hover:bg-pergamino"
                        @click="select(book)"
                    >
                        <span class="relative flex h-20 w-14 shrink-0 items-center justify-center overflow-hidden rounded bg-pergamino font-serif text-xl text-madera shadow-soft">
                            {{ book.title.charAt(0) }}
                            <img
                                v-if="book.cover_url"
                                :src="book.cover_url"
                                :alt="t('ui.cover_alt', { title: book.title })"
                                class="absolute inset-0 h-full w-full object-cover"
                                loading="lazy"
                                decoding="async"
                                referrerpolicy="no-referrer"
                                @error="coverError($event, book)"
                            >
                        </span>
                        <span class="min-w-0">
                            <strong class="block font-serif text-base leading-snug">{{ book.title }}</strong>
                            <span class="block text-sm text-tinta/65">{{ book.author || t('ui.unknown_author') }}</span>
                            <span
                                v-if="book.publisher || book.published_year"
                                class="mt-1 block text-xs text-tinta/50"
                            >
                                {{ [book.publisher, book.published_year].filter(Boolean).join(' · ') }}
                            </span>
                        </span>
                    </button>
                </li>
            </ul>
        </section>

        <form v-if="tab === 'manual'" class="space-y-4" @submit.prevent="submit">
            <input v-model="form.title" class="w-full rounded-control border border-madera/35 bg-crema px-3 py-2" :placeholder="t('ui.title_placeholder')">
            <input v-model="form.author" class="w-full rounded-control border border-madera/35 bg-crema px-3 py-2" :placeholder="t('ui.author_placeholder')">
            <input v-model="form.isbn" class="w-full rounded-control border border-madera/35 bg-crema px-3 py-2" :placeholder="t('ui.isbn')">
            <input v-model="form.publisher" class="w-full rounded-control border border-madera/35 bg-crema px-3 py-2" :placeholder="t('ui.publisher_placeholder')">
            <input v-model.number="form.published_year" type="number" class="w-full rounded-control border border-madera/35 bg-crema px-3 py-2" :placeholder="t('ui.year_placeholder')">
            <select v-model="form.shelf_id" class="w-full rounded-control border border-madera/35 bg-crema px-3 py-2">
                <option v-for="shelf in props.shelves" :key="shelf.id" :value="shelf.id">{{ shelf.display_name ?? shelf.name }}</option>
            </select>
            <div>
                <span class="mb-1 block text-sm text-tinta/65">{{ t('ui.rating_heading') }}</span>
                <RatingStars v-model="form.rating" editable />
            </div>
            <textarea
                v-model="form.notes"
                rows="3"
                class="w-full rounded-control border border-madera/35 bg-crema px-3 py-2"
                :placeholder="t('ui.notes_placeholder')"
            />
            <input type="file" accept="image/jpeg,image/png,image/webp" @change="form.cover = $event.target.files[0]">
            <p v-if="form.errors.title" class="text-sm text-terracota">{{ form.errors.title }}</p>
            <button type="submit" class="rounded-control bg-oliva px-4 py-2 text-crema hover:bg-oliva-hover" :disabled="form.processing">
                {{ t('ui.save') }}
            </button>
        </form>
    </AuthenticatedLayout>
</template>

