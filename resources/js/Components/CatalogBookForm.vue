<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    form: { type: Object, required: true },
    shelves: { type: Array, default: () => [] },
    showShelf: { type: Boolean, default: false },
    existingCoverUrl: { type: String, default: null },
});

const { t } = useI18n();
const coverInput = ref(null);
const localPreview = ref(null);
let objectUrl = null;

function releaseObjectUrl() {
    if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
        objectUrl = null;
    }
}

const preview = computed(
    () => localPreview.value || props.form.cover_url || props.existingCoverUrl || null
);

watch(
    () => props.form.cover_url,
    (value) => {
        if (!props.form.cover) {
            localPreview.value = value || props.existingCoverUrl || null;
        }
    }
);

function openCoverPicker() {
    coverInput.value?.click();
}

function onCoverChange(event) {
    const file = event.target.files[0] ?? null;
    props.form.cover = file;
    releaseObjectUrl();

    if (file) {
        objectUrl = URL.createObjectURL(file);
        localPreview.value = objectUrl;

        return;
    }

    localPreview.value = props.form.cover_url || props.existingCoverUrl || null;
}

onBeforeUnmount(releaseObjectUrl);
</script>

<template>
    <div class="flex flex-row items-start gap-3 sm:gap-6">
        <div class="flex w-24 shrink-0 flex-col items-center sm:w-[180px]">
            <div class="relative aspect-[2/3] w-full shrink-0 overflow-hidden rounded-card bg-surface shadow-card ring-1 ring-edge">
                <img
                    v-if="preview"
                    :src="preview"
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
            <p v-if="form.errors.author" class="text-xs text-danger">{{ form.errors.author }}</p>

            <input v-model="form.isbn" class="w-full max-w-full rounded-control border border-edge bg-surface px-2 py-0.5 text-sm text-ink placeholder:text-ink-muted sm:px-3 sm:py-3 sm:text-base" :placeholder="t('ui.isbn')">
            <p v-if="form.errors.isbn" class="text-xs text-danger">{{ form.errors.isbn }}</p>

            <input v-model="form.publisher" class="w-full max-w-full rounded-control border border-edge bg-surface px-2 py-0.5 text-sm text-ink placeholder:text-ink-muted sm:px-3 sm:py-3 sm:text-base" :placeholder="t('ui.publisher_placeholder')">
            <p v-if="form.errors.publisher" class="text-xs text-danger">{{ form.errors.publisher }}</p>

            <input v-model.number="form.published_year" type="number" class="w-full max-w-full rounded-control border border-edge bg-surface px-2 py-0.5 text-sm text-ink placeholder:text-ink-muted sm:px-3 sm:py-3 sm:text-base" :placeholder="t('ui.year_placeholder')">
            <p v-if="form.errors.published_year" class="text-xs text-danger">{{ form.errors.published_year }}</p>

            <template v-if="showShelf">
                <select v-model="form.shelf_id" class="w-full rounded-control border border-edge bg-surface px-2 py-0.5 text-sm text-ink sm:px-3 sm:py-3 sm:text-base">
                    <option v-for="shelf in shelves" :key="shelf.id" :value="shelf.id">
                        {{ shelf.display_name ?? shelf.name }}
                    </option>
                </select>
                <p class="text-xs text-ink-muted">{{ t('ui.catalog_shelf_help') }}</p>
                <p v-if="form.errors.shelf_id" class="text-xs text-danger">{{ form.errors.shelf_id }}</p>
            </template>
        </div>
    </div>

    <div class="mt-4 space-y-3">
        <label class="block text-sm text-ink-muted" for="catalog-cover-url">
            {{ t('ui.catalog_cover_url') }}
        </label>
        <input
            id="catalog-cover-url"
            v-model="form.cover_url"
            type="url"
            class="w-full rounded-control border border-edge bg-surface px-3 py-2 text-ink placeholder:text-ink-muted"
            :placeholder="t('ui.catalog_cover_url')"
        >
        <p v-if="form.errors.cover_url" class="text-xs text-danger">{{ form.errors.cover_url }}</p>

        <input
            ref="coverInput"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            class="hidden"
            @change="onCoverChange"
        >
        <p v-if="form.errors.cover" class="text-xs text-danger">{{ form.errors.cover }}</p>
    </div>
</template>
