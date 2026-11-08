<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import RatingStars from '@/Components/RatingStars.vue';
import BookImages from '@/Components/BookImages.vue';
import ReadingLightbox from '@/Components/ReadingLightbox.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    book: { type: Object, required: true },
    images: { type: Array, default: () => [] },
});

const { t } = useI18n();

const coverUrl = computed(() => (props.book.cover_path ? `/covers/${props.book.id}` : null));
const coverInput = ref(null);
const readingOpen = ref(false);

function onCoverChange(event) {
    form.cover = event.target.files[0] ?? null;
}

function openCoverPicker() {
    coverInput.value?.click();
}

const form = useForm({
    title: props.book.title ?? '',
    author: props.book.author ?? '',
    isbn: props.book.isbn ?? '',
    publisher: props.book.publisher ?? '',
    published_year: props.book.published_year ?? null,
    shelf_id: props.book.shelf_id,
    rating: props.book.rating ?? 0,
    spice_rating: props.book.spice_rating ?? 0,
    heart_rating: props.book.heart_rating ?? 0,
    drop_rating: props.book.drop_rating ?? 0,
    crystal_rating: props.book.crystal_rating ?? 0,
    notes: props.book.notes ?? '',
    cover: null,
    cover_url: null,
});

function submit() {
    form.patch(`/books/${props.book.id}`, { forceFormData: true, preserveScroll: true });
}

function destroy() {
    if (window.confirm(t('ui.delete_book_confirm'))) {
        router.delete(`/books/${props.book.id}`);
    }
}
</script>

<template>
    <Head :title="t('ui.edit_page_title', { title: book.title })" />
    <AuthenticatedLayout>
        <div class="mx-auto w-full max-w-3xl">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl sm:text-3xl">{{ t('ui.edit_book_title') }}</h1>
                <Link href="/" class="rounded-control bg-accent px-3 py-2 text-sm sm:px-4 sm:py-2 text-on-accent sm:text-base hover:bg-accent-strong">
                    {{ t('ui.back') }}
                </Link>
            </div>

            <form class="w-full" @submit.prevent="submit">
            <div class="flex flex-row items-start gap-3 sm:gap-6">
                <div class="flex w-24 shrink-0 flex-col items-center sm:w-[180px]">
                    <div class="relative aspect-[2/3] w-full shrink-0 overflow-hidden rounded-card bg-surface shadow-card ring-1 ring-edge">
                        <img
                            v-if="coverUrl"
                            :src="coverUrl"
                            :alt="t('ui.cover_alt', { title: book.title })"
                            class="h-full w-full object-cover"
                        >
                        <div v-else class="flex h-full items-center justify-center p-2 text-center font-serif text-sm text-ink sm:p-4">
                            {{ book.title }}
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

                <BookImages :book-id="book.id" :images="images" />
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
                />

                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-control border border-accent/40 px-4 py-2 text-accent hover:bg-accent/10"
                        @click="readingOpen = true"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <path d="M5 3.2v9.6L13 8z" />
                        </svg>
                        {{ t('ui.reading_open') }}
                    </button>
                    <button type="submit" class="rounded-control bg-accent px-4 py-2 text-on-accent hover:bg-accent-strong" :disabled="form.processing">
                        {{ t('ui.save') }}
                    </button>
                    <button type="button" class="rounded-control border border-danger px-4 py-2 text-danger hover:bg-danger/10" @click="destroy">
                        {{ t('ui.delete') }}
                    </button>
                </div>
            </div>
        </form>
        </div>

        <ReadingLightbox
            v-if="readingOpen"
            :book="book"
            @close="readingOpen = false"
        />
    </AuthenticatedLayout>
</template>
