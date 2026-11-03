<script setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    bookId: { type: Number, required: true },
    images: { type: Array, default: () => [] },
});

const { t } = useI18n();

const fileInput = ref(null);
const targetSlot = ref(0);

const SLOTS = [0, 1, 2];
const TILTS = ['-rotate-6', 'rotate-3', '-rotate-2'];

function imageFor(slot) {
    return props.images.find((image) => image.slot === slot) ?? null;
}

function choose(slot) {
    targetSlot.value = slot;
    fileInput.value?.click();
}

function upload(event) {
    const file = event.target.files[0];

    if (!file) {
        return;
    }

    router.post(
        `/books/${props.bookId}/images`,
        { image: file, slot: targetSlot.value },
        { forceFormData: true, preserveScroll: true }
    );

    event.target.value = '';
}

function remove(image) {
    router.delete(`/book-images/${image.id}`, { preserveScroll: true });
}
</script>

<template>
    <div class="flex min-w-0 flex-1 items-start gap-2 sm:flex-none">
        <div
            v-for="slot in SLOTS"
            :key="slot"
            class="min-w-0 flex-1 sm:w-24 sm:flex-none"
            :class="TILTS[slot]"
        >
            <div
                v-if="imageFor(slot)"
                class="relative h-28 w-full overflow-hidden rounded-card border border-line bg-night-raised shadow-card"
            >
                <img
                    :src="`/book-images/${imageFor(slot).id}`"
                    :alt="t('ui.book_image_alt', { slot: slot + 1 })"
                    class="h-full w-full object-cover"
                    loading="lazy"
                >
                <button
                    type="button"
                    class="absolute right-0.5 top-0.5 flex h-5 w-5 items-center justify-center rounded-full border border-line bg-night-raised text-moon hover:text-danger"
                    :aria-label="t('ui.remove_book_image')"
                    :title="t('ui.remove_book_image')"
                    @click.prevent="remove(imageFor(slot))"
                >
                    <svg class="h-3 w-3" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                        <path d="M3.5 3.5l7 7m0-7l-7 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                    </svg>
                </button>
            </div>

            <button
                v-else
                type="button"
                class="flex h-28 w-full flex-col items-center justify-center gap-1 rounded-card border border-dashed border-line bg-panel-raised/60 text-mist transition-colors hover:border-magic hover:text-gold"
                :aria-label="t('ui.add_book_image')"
                :title="t('ui.add_book_image')"
                @click="choose(slot)"
            >
                <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                    <path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                </svg>
            </button>
        </div>

        <input
            ref="fileInput"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            class="hidden"
            @change="upload"
        >
    </div>
</template>
