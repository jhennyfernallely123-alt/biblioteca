<script setup>
import { useI18n } from '@/i18n';

defineProps({
    active: { type: String, required: true },
});

const emit = defineEmits(['update:active']);

const { t } = useI18n();
</script>

<template>
    <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-edge/40">
        <button
            v-for="category in ['all', 'card', 'sticker']"
            :key="category"
            :class="[
                'px-3 py-1.5 rounded-xl text-sm font-bold transition-all',
                active === category
                    ? 'bg-accent text-white shadow-sm'
                    : 'bg-surface text-ink-muted hover:bg-surface-raised',
            ]"
            @click="emit('update:active', category)"
        >
            {{ t(`ui.album_filter_${category}`) }}
        </button>
    </div>
</template>
