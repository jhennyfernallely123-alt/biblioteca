<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import RatingStars from '@/Components/RatingStars.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    book: { type: Object, required: true },
    shelves: { type: Array, required: true },
});

const { t } = useI18n();

const coverUrl = computed(() => (props.book.cover_path ? `/covers/${props.book.id}` : null));
const coverInput = ref(null);

function onCoverChange(event) {
    form.cover = event.target.files[0] ?? null;
}

const form = useForm({
    title: props.book.title ?? '',
    author: props.book.author ?? '',
    isbn: props.book.isbn ?? '',
    publisher: props.book.publisher ?? '',
    published_year: props.book.published_year ?? null,
    shelf_id: props.book.shelf_id,
    rating: props.book.rating ?? 0,
    notes: props.book.notes ?? '',
    cover: null,
    cover_url: null,
});

const moveForm = useForm({ shelf_id: props.book.shelf_id });
const viewForm = useForm({ view_mode: props.book.view_mode ?? 'covers' });

function submit() {
    form.patch(`/books/${props.book.id}`, { forceFormData: true, preserveScroll: true });
}

function move() {
    moveForm.patch(`/books/${props.book.id}/move`, { preserveScroll: true });
}

function saveViewMode() {
    viewForm.patch(`/books/${props.book.id}/view-mode`, { preserveScroll: true });
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
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl sm:text-3xl">{{ t('ui.edit_book_title') }}</h1>
            <Link href="/" class="rounded-control bg-arcane px-3 py-2 text-sm sm:px-4 sm:py-2 text-moon sm:text-base hover:bg-arcane-strong">
                {{ t('ui.back') }}
            </Link>
        </div>

        <div class="flex flex-col sm:flex-row gap-6 items-start max-w-full">
            <div class="flex flex-col items-center w-full sm:w-[180px] shrink-0">
                <div class="aspect-[2/3] overflow-hidden rounded-card bg-panel shadow-card ring-1 ring-line w-full max-w-[200px] sm:max-w-none shrink-0">
                    <img
                        v-if="coverUrl"
                        :src="coverUrl"
                        :alt="t('ui.cover_alt', { title: book.title })"
                        class="h-full w-full object-cover"
                    >
                    <div v-else class="flex h-full items-center justify-center p-4 text-center font-serif text-moon">
                        {{ book.title }}
                    </div>
                </div>
                <p class="mt-2 text-xs text-mist text-center">{{ book.isbn }}</p>
            </div>

            <form class="space-y-3 w-full min-w-0" @submit.prevent="submit">
                <input v-model="form.title" class="w-full max-w-full rounded-control border border-line bg-panel px-3 py-2 text-moon placeholder:text-mist" :placeholder="t('ui.title_placeholder')">
                <p v-if="form.errors.title" class="text-sm text-danger">{{ form.errors.title }}</p>

                <input v-model="form.author" class="w-full max-w-full rounded-control border border-line bg-panel px-3 py-2 text-moon placeholder:text-mist" :placeholder="t('ui.author_placeholder')">
                <input v-model="form.isbn" class="w-full max-w-full rounded-control border border-line bg-panel px-3 py-2 text-moon placeholder:text-mist" :placeholder="t('ui.isbn')">
                <input v-model="form.publisher" class="w-full max-w-full rounded-control border border-line bg-panel px-3 py-2 text-moon placeholder:text-mist" :placeholder="t('ui.publisher_placeholder')">
                <input v-model.number="form.published_year" type="number" class="w-full max-w-full rounded-control border border-line bg-panel px-3 py-2 text-moon placeholder:text-mist" :placeholder="t('ui.year_placeholder')">

                <div>
                    <span class="mb-1 block text-sm text-mist">{{ t('ui.rating_heading') }}</span>
                    <RatingStars v-model="form.rating" editable />
                </div>

                <textarea
                    v-model="form.notes"
                    rows="4"
                    class="w-full max-w-full rounded-control border border-line bg-panel px-3 py-2 text-moon placeholder:text-mist"
                    :placeholder="t('ui.notes_placeholder')"
                />

                <input
                    ref="coverInput"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="hidden"
                    @change="onCoverChange"
                />

                <button
                    type="button"
                    class="w-full sm:w-auto rounded-control border border-line bg-panel px-4 py-2 text-moon hover:bg-panel-raised"
                    @click="coverInput.value?.click()"
                >
                    {{ t('ui.change_cover') }}
                </button>

                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="rounded-control bg-arcane px-4 py-2 text-moon hover:bg-arcane-strong" :disabled="form.processing">
                        {{ t('ui.save') }}
                    </button>
                    <button type="button" class="rounded-control border border-danger px-4 py-2 text-danger hover:bg-danger/10" @click="destroy">
                        {{ t('ui.delete') }}
                    </button>
                </div>
            </form>
        </div>

        <section class="mt-8 rounded-card border border-line bg-panel p-4 w-full">
            <h2 class="mb-2 text-xl">{{ t('ui.move_heading') }}</h2>
            <div class="flex flex-wrap items-end gap-2 w-full">
                <select v-model="moveForm.shelf_id" class="w-full max-w-full rounded-control border border-line bg-panel px-3 py-2 text-moon">
                    <option v-for="shelf in shelves" :key="shelf.id" :value="shelf.id">{{ shelf.display_name ?? shelf.name }}</option>
                </select>
                <button type="button" class="rounded-control bg-arcane px-4 py-2 text-moon hover:bg-arcane-strong" :disabled="moveForm.processing" @click="move">
                    {{ t('ui.move') }}
                </button>
            </div>
        </section>

        <section class="mt-4 rounded-card border border-line bg-panel p-4 w-full">
            <h2 class="mb-2 text-xl">{{ t('ui.view_mode_heading') }}</h2>
            <div class="flex flex-wrap items-end gap-2 w-full">
                <select v-model="viewForm.view_mode" class="w-full max-w-full rounded-control border border-line bg-panel px-3 py-2 text-moon" @change="saveViewMode">
                    <option value="covers">{{ t('ui.show_as_cover') }}</option>
                    <option value="spines">{{ t('ui.show_as_spine') }}</option>
                </select>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
