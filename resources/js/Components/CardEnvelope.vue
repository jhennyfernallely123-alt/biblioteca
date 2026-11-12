<script setup>
import CardEmblem from './CardEmblem.vue';
import { useI18n } from '@/i18n';

defineProps({
    card: { type: Object, required: true },
});

defineEmits(['open']);

const { t } = useI18n();
</script>

<template>
    <div class="flex flex-col items-center gap-3">
        <button
            type="button"
            class="card-envelope w-full max-w-xs px-6 pt-16 pb-6 text-center"
            :aria-label="t('ui.cards_open_letter')"
            @click="$emit('open')"
        >
            <span class="card-envelope__flap" aria-hidden="true"></span>
            <span class="card-envelope__seal">
                <CardEmblem :name="card.familyEmblem" :size="26" />
            </span>
            <span class="card-envelope__label block text-sm">{{ t('ui.cards_open_letter') }}</span>
        </button>
    </div>
</template>
