<script setup>
import { computed } from 'vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    item: { type: Object, required: true },
});

const { t } = useI18n();

const isLocked = computed(() => !props.item.unlocked);
</script>

<template>
    <button
        type="button"
        class="card-envelope w-32 h-20 px-6 pt-16 pb-6 text-center cursor-pointer group"
        :class="{ 'opacity-50': isLocked }"
        :aria-label="isLocked ? t('ui.album_locked') : props.item.name"
        role="button"
        tabindex="0"
        @click="$emit('click')"
    >
        <span class="card-envelope__flap" aria-hidden="true"></span>
        <span class="card-envelope__seal">
            <svg
                viewBox="0 0 24 24"
                class="w-10 h-10"
                fill="none"
                stroke="var(--color-pergamino)"
                stroke-width="2.5"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <!-- Máscara de teatro blanca -->
                <path d="M10 12 Q10 8 12 8 Q14 8 14 12 Q14 16 12 16 Q10 16 10 12 Z" />
                <path d="M12 16 L18 16 L15 22 Z" />
            </svg>
        </span>
    </button>
</template>
