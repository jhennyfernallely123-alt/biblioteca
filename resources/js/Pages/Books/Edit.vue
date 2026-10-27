<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import RatingStars from '@/Components/RatingStars.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    book: { type: Object, required: true },
    shelves: { type: Array, required: true },
});

const { t } = useI18n();

const coverUrl = computed(() => (props.book.cover_path ? `/covers/${props.book.id}` : null));

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

function submit() {
    form.patch(`/books/${props.book.id}`, { forceFormData: true, preserveScroll: true });
}

function move() {
    moveForm.patch(`/books/${props.book.id}/move`, { preserveScroll: true });
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
        <h1 class="mb-4 text-3xl">{{ t('ui.edit_book_title') }}</h1>

        <div class="grid gap-6 md:grid-cols-[180px_1fr]">
            <div>
                <div class="aspect-[2/3] overflow-hidden rounded-card bg-crema shadow-card ring-1 ring-madera/35">
                    <img
                        v-if="coverUrl"
                        :src="coverUrl"
                        :alt="t('ui.cover_alt', { title: book.title })"
                        class="h-full w-full object-cover"
                    >
                    <div v-else class="flex h-full items-center justify-center p-4 text-center font-serif text-tinta">
                        {{ book.title }}
                    </div>
                </div>
                <p class="mt-2 text-xs text-tinta/60">{{ book.isbn }}</p>
            </div>

            <form class="space-y-3" @submit.prevent="submit">
                <input v-model="form.title" class="w-full rounded-control border border-madera/35 bg-crema px-3 py-2" :placeholder="t('ui.title_placeholder')">
                <p v-if="form.errors.title" class="text-sm text-terracota">{{ form.errors.title }}</p>

                <input v-model="form.author" class="w-full rounded-control border border-madera/35 bg-crema px-3 py-2" :placeholder="t('ui.author_placeholder')">
                <input v-model="form.isbn" class="w-full rounded-control border border-madera/35 bg-crema px-3 py-2" :placeholder="t('ui.isbn')">
                <input v-model="form.publisher" class="w-full rounded-control border border-madera/35 bg-crema px-3 py-2" :placeholder="t('ui.publisher_placeholder')">
                <input v-model.number="form.published_year" type="number" class="w-full rounded-control border border-madera/35 bg-crema px-3 py-2" :placeholder="t('ui.year_placeholder')">

                <div>
                    <span class="mb-1 block text-sm text-tinta/65">{{ t('ui.rating_heading') }}</span>
                    <RatingStars v-model="form.rating" editable />
                </div>

                <textarea
                    v-model="form.notes"
                    rows="4"
                    class="w-full rounded-control border border-madera/35 bg-crema px-3 py-2"
                    :placeholder="t('ui.notes_placeholder')"
                />

                <input type="file" accept="image/jpeg,image/png,image/webp" @change="form.cover = $event.target.files[0]">

                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="rounded-control bg-oliva px-4 py-2 text-crema hover:bg-oliva-hover" :disabled="form.processing">
                        {{ t('ui.save') }}
                    </button>
                    <button type="button" class="rounded-control border border-terracota px-4 py-2 text-terracota" @click="destroy">
                        {{ t('ui.delete') }}
                    </button>
                </div>
            </form>
        </div>

        <section class="mt-8 rounded-card border border-madera/25 bg-crema/70 p-4">
            <h2 class="mb-2 text-xl">{{ t('ui.move_heading') }}</h2>
            <div class="flex flex-wrap items-end gap-2">
                <select v-model="moveForm.shelf_id" class="rounded-control border border-madera/35 bg-crema px-3 py-2">
                    <option v-for="shelf in shelves" :key="shelf.id" :value="shelf.id">{{ shelf.display_name ?? shelf.name }}</option>
                </select>
                <button type="button" class="rounded-control bg-bosque px-4 py-2 text-crema" :disabled="moveForm.processing" @click="move">
                    {{ t('ui.move') }}
                </button>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
