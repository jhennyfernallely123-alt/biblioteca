<script setup>
import { computed, ref } from 'vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    book: { type: Object, required: true },
});

const { t } = useI18n();
const failed = ref(false);

const coverUrl = computed(() =>
    props.book.cover_path && !failed.value ? `/covers/${props.book.id}` : null
);
</script>

<template>
    <a :href="`/books/${book.id}/edit`" class="group block w-full">
        <div
            class="aspect-[2/3] overflow-hidden rounded-card bg-crema shadow-sm ring-1 ring-madera/35 transition group-hover:-translate-y-0.5 group-hover:shadow-cover"
        >
            <img
                v-if="coverUrl"
                :src="coverUrl"
                :alt="t('ui.cover_alt', { title: book.title })"
                class="h-full w-full object-cover"
                loading="lazy"
                @error="failed = true"
            >
            <div v-else class="flex h-full flex-col justify-center p-2 text-center">
                <span class="font-serif text-base font-semibold text-tinta">{{ book.title }}</span>
                <span class="mt-1 text-xs text-tinta/65">{{ book.author }}</span>
            </div>
        </div>
        <h3 class="mt-2 truncate font-serif text-[18px] leading-6 text-tinta">{{ book.title }}</h3>
        <p class="truncate text-sm text-tinta/65">{{ book.author }}</p>
    </a>
</template>
