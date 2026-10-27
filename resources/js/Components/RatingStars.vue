<script setup>
import { computed } from 'vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    book: { type: Object, required: false },
    modelValue: { type: Number, default: 0 },
    editable: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);
const { t } = useI18n();

const stars = computed(() => Array.from({ length: 5 }, (_, index) => index + 1));

function select(value) {
    if (props.editable) {
        emit('update:modelValue', value);
    }
}
</script>

<template>
    <div class="flex gap-1" role="img" :aria-label="t('ui.rating_label', { rating: modelValue })">
        <button
            v-for="star in stars"
            :key="star"
            type="button"
            data-star
            class="leading-none disabled:cursor-default"
            :class="star <= modelValue ? 'text-gold' : 'text-gold/40'"
            :disabled="!editable"
            :aria-label="t('ui.rating_star', { star })"
            @click="select(star)"
        >
            ★
        </button>
    </div>
</template>
