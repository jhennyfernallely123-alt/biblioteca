<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { onBeforeUnmount, ref, watch } from 'vue';
import BarcodeScanner from '@/Components/BarcodeScanner.vue';
import RatingStars from '@/Components/RatingStars.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { applyLookupResult } from '@/bookLookup';
import { useI18n } from '@/i18n';

const props = defineProps({ shelves: { type: Array, required: true } });
const { t } = useI18n();

const tab = ref('search');
const scannerOpen = ref(false);
const results = ref([]);
const searching = ref(false);
const error = ref('');
const coverInput = ref(null);
const coverPreview = ref(null);
let coverObjectUrl = null;
let searchTimer = null;
let searchController = null;
let searchRequest = 0;

const form = useForm({
    title: '',
    author: '',
    isbn: '',
    publisher: '',
    published_year: null,
    catalog_book_id: null,
    shelf_id: props.shelves[0]?.id ?? null,
    rating: null,
    spice_rating: null,
    heart_rating: null,
    drop_rating: null,
    crystal_rating: null,
    notes: '',
    cover: null,
    cover_url: null,
});

function releaseCoverObjectUrl() {
    if (coverObjectUrl) {
        URL.revokeObjectURL(coverObjectUrl);
        coverObjectUrl = null;
    }
}

function openCoverPicker() {
    coverInput.value?.click();
}

function onCoverChange(event) {
    const file = event.target.files[0] ?? null;
    form.cover = file;
    releaseCoverObjectUrl();

    if (file) {
        coverObjectUrl = URL.createObjectURL(file);
        coverPreview.value = coverObjectUrl;

        return;
    }

    coverPreview.value = form.cover_url ?? null;
}

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

        searchTimer = setTimeout(() => search(value), 600);
    },
);

onBeforeUnmount(() => {
    clearTimeout(searchTimer);
    searchController?.abort();
    releaseCoverObjectUrl();
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

    const safetyTimer = setTimeout(() => {
        searching.value = false;
        error.value = t('ui.catalog_error');
    }, 10000);

    try {
        const { data } = await axios.get('/books/lookup', { params: { isbn }, timeout: 8000 });
        results.value = data.results;
        if (data.results.length === 0) {
            error.value = t('ui.isbn_not_found');
        }
    } catch (e) {
        error.value = requestError(e, t('ui.catalog_error'));
    } finally {
        clearTimeout(safetyTimer);
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

    const safetyTimer = setTimeout(() => {
        if (request === searchRequest) {
            searching.value = false;
            error.value = t('ui.catalog_error');
        }
    }, 10000);

    try {
        const { data } = await axios.get('/books/lookup', {
            params: { q: value },
            signal: searchController.signal,
            timeout: 8000,
        });

        clearTimeout(safetyTimer);

        if (request !== searchRequest) {
            return;
        }

        results.value = data.results;

        if (data.results.length === 0) {
            error.value = t('ui.no_results');
        }
    } catch (e) {
        clearTimeout(safetyTimer);

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
    coverPreview.value = applyLookupResult(form, book);
    releaseCoverObjectUrl();
    if (coverInput.value) {
        coverInput.value.value = '';
    }
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
        <div class="mx-auto w-full max-w-3xl">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl sm:text-3xl">{{ t('ui.add_book') }}</h1>
                <Link href="/shelves" class="rounded-control bg-accent px-3 py-2 text-sm sm:px-4 sm:py-2 text-on-accent sm:text-base hover:bg-accent-strong">
                    {{ t('ui.back') }}
                </Link>
            </div>

            <div class="mb-4 flex gap-2">
                <button
                    type="button"
                    class="rounded-control border border-edge px-3 py-2"
                    :class="tab === 'search' ? 'bg-tertiary text-on-accent' : 'bg-surface text-ink-muted'"
                    @click="tab = 'search'"
                >
                    {{ t('ui.search_tab') }}
                </button>
                <button
                    type="button"
                    class="rounded-control border border-edge px-3 py-2"
                    :class="tab === 'manual' ? 'bg-tertiary text-on-accent' : 'bg-surface text-ink-muted'"
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
                        class="min-h-11 rounded-control border border-edge px-4 py-2 text-sm text-ink hover:bg-surface-raised"
                        @click="closeScanner"
                    >
                        {{ t('ui.close_camera') }}
                    </button>
                </div>
                <button
                    v-else
                    type="button"
                    class="min-h-11 rounded-control border border-edge px-4 py-2 text-sm text-ink hover:bg-surface-raised"
                    @click="scannerOpen = true"
                >
                    {{ t('ui.scan_camera') }}
                </button>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <input
                        v-model="form.isbn"
                        class="w-full rounded-control border border-edge bg-surface px-3 py-2 text-ink placeholder:text-ink-muted"
                        :placeholder="t('ui.isbn')"
                        @keyup.enter="lookupIsbn(form.isbn)"
                    >
                    <button
                        type="button"
                        class="rounded-control bg-accent px-4 py-2 text-on-accent hover:bg-accent-strong"
                        :disabled="searching"
                        @click="lookupIsbn(form.isbn)"
                    >
                        {{ t('ui.search_isbn') }}
                    </button>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <input
                        v-model="form.title"
                        class="w-full rounded-control border border-edge bg-surface px-3 py-2 text-ink placeholder:text-ink-muted"
                        :placeholder="t('ui.search_placeholder')"
                        @keyup.enter="search()"
                    >
                    <button
                        type="button"
                        class="rounded-control bg-accent px-4 py-2 text-on-accent hover:bg-accent-strong"
                        :disabled="searching"
                        @click="search()"
                    >
                        {{ t('ui.search') }}
                    </button>
                </div>

                <p v-if="searching" class="text-sm text-ink-muted">{{ t('ui.searching') }}</p>

                <p v-if="error" class="text-sm text-danger">{{ error }}</p>

                <ul class="space-y-2">
                    <li v-for="(book, index) in results" :key="index">
                        <button
                            type="button"
                            class="flex w-full items-start gap-3 rounded-control border border-edge bg-surface p-3 text-left text-ink hover:bg-surface-raised"
                            @click="select(book)"
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
                                    referrerpolicy="no-referrer"
                                    @error="coverError($event, book)"
                                >
                            </span>
                            <span class="min-w-0">
                                <strong class="block font-serif text-base leading-snug">{{ book.title }}</strong>
                                <span class="block text-sm text-ink-muted">{{ book.author || t('ui.unknown_author') }}</span>
                                <span
                                    v-if="book.publisher || book.published_year"
                                    class="mt-1 block text-xs text-ink-muted/80"
                                >
                                    {{ [book.publisher, book.published_year].filter(Boolean).join(' · ') }}
                                </span>
                            </span>
                        </button>
                    </li>
                </ul>
            </section>

            <form v-if="tab === 'manual'" class="w-full" @submit.prevent="submit">
                <div class="flex flex-row items-start gap-3 sm:gap-6">
                    <div class="flex w-24 shrink-0 flex-col items-center sm:w-[180px]">
                        <div class="relative aspect-[2/3] w-full shrink-0 overflow-hidden rounded-card bg-surface shadow-card ring-1 ring-edge">
                            <img
                                v-if="coverPreview"
                                :src="coverPreview"
                                :alt="t('ui.cover_alt', { title: form.title })"
                                class="h-full w-full object-cover"
                            >
                            <div v-else class="flex h-full items-center justify-center p-2 text-center font-serif text-sm text-ink sm:p-4">
                                {{ form.title || t('ui.show_as_cover') }}
                            </div>

                            <button
                                type="button"
                                class="absolute right-1 top-1 flex h-7 w-7 items-center justify-center rounded-full border border-edge bg-surface-raised/90 text-ink shadow-card transition-colors hover:bg-surface hover:text-accent"
                                :aria-label="t('ui.change_cover')"
                                :title="t('ui.change_cover')"
                                @click="openCoverPicker"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                    <path d="M2.5 5.5h2l1-1.5h5l1 1.5h2a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1h-11a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" />
                                    <circle cx="8" cy="9" r="2.2" stroke="currentColor" stroke-width="1.3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="min-w-0 flex-1 space-y-1">
                        <input v-model="form.title" class="w-full max-w-full rounded-control border border-edge bg-surface px-2 py-0.5 text-sm text-ink placeholder:text-ink-muted sm:px-3 sm:py-3 sm:text-base" :placeholder="t('ui.title_placeholder')">
                        <p v-if="form.errors.title" class="text-xs text-danger">{{ form.errors.title }}</p>

                        <input v-model="form.author" class="w-full max-w-full rounded-control border border-edge bg-surface px-2 py-0.5 text-sm text-ink placeholder:text-ink-muted sm:px-3 sm:py-3 sm:text-base" :placeholder="t('ui.author_placeholder')">
                        <input v-model="form.isbn" class="w-full max-w-full rounded-control border border-edge bg-surface px-2 py-0.5 text-sm text-ink placeholder:text-ink-muted sm:px-3 sm:py-3 sm:text-base" :placeholder="t('ui.isbn')">
                        <input v-model="form.publisher" class="w-full max-w-full rounded-control border border-edge bg-surface px-2 py-0.5 text-sm text-ink placeholder:text-ink-muted sm:px-3 sm:py-3 sm:text-base" :placeholder="t('ui.publisher_placeholder')">
                        <input v-model.number="form.published_year" type="number" class="w-full max-w-full rounded-control border border-edge bg-surface px-2 py-0.5 text-sm text-ink placeholder:text-ink-muted sm:px-3 sm:py-3 sm:text-base" :placeholder="t('ui.year_placeholder')">
                        <select v-model="form.shelf_id" class="w-full max-w-full rounded-control border border-edge bg-surface px-2 py-0.5 text-sm text-ink sm:px-3 sm:py-3 sm:text-base">
                            <option v-for="shelf in props.shelves" :key="shelf.id" :value="shelf.id">{{ shelf.display_name ?? shelf.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="mt-5 flex items-start gap-3 sm:gap-6">
                    <div
                        class="space-y-2"
                        role="group"
                        :aria-label="t('ui.rating_heading')"
                    >
                        <RatingStars
                            v-model="form.rating"
                            kind="star"
                            :label="t('ui.rating_stars')"
                            editable
                        />
                        <RatingStars
                            v-model="form.spice_rating"
                            kind="chili"
                            :label="t('ui.rating_spice')"
                            editable
                        />
                        <RatingStars
                            v-model="form.heart_rating"
                            kind="heart"
                            :label="t('ui.rating_hearts')"
                            editable
                        />
                        <RatingStars
                            v-model="form.drop_rating"
                            kind="drop"
                            :label="t('ui.rating_drops')"
                            editable
                        />
                        <RatingStars
                            v-model="form.crystal_rating"
                            kind="crystal"
                            :label="t('ui.rating_crystals')"
                            editable
                        />
                    </div>
                </div>

                <div class="mt-4 space-y-3">
                    <textarea
                        v-model="form.notes"
                        rows="4"
                        class="w-full max-w-full rounded-control border border-edge bg-surface px-3 py-2 text-ink placeholder:text-ink-muted"
                        :placeholder="t('ui.notes_placeholder')"
                    />

                    <input
                        ref="coverInput"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        class="hidden"
                        @change="onCoverChange"
                    >

                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="rounded-control bg-accent px-4 py-2 text-on-accent hover:bg-accent-strong" :disabled="form.processing">
                            {{ t('ui.save') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
