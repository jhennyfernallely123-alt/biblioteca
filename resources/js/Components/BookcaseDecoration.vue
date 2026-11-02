<script setup>
import { computed } from 'vue';
import DecorationArt from './DecorationArt.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    decoration: { type: Object, required: true },
    dragging: { type: Boolean, default: false },
    dropPosition: { type: String, default: null },
});

const { t } = useI18n();

const name = computed(() => t(`ui.decoration_${props.decoration.kind}`));
</script>

<template>
    <div
        class="decoration-drag-source group relative shrink-0"
        :data-decoration-id="decoration.id"
        :class="{
            'opacity-30': dragging,
            'book-drop-before': dropPosition === 'before',
            'book-drop-after': dropPosition === 'after',
        }"
    >
        <div
            class="book-glow flex h-16 w-14 cursor-grab items-end justify-center sm:h-20 sm:w-16"
            role="img"
            :aria-label="name"
            :title="name"
        >
            <DecorationArt :kind="decoration.kind" :color="decoration.color" class="h-full w-full" />
        </div>
    </div>
</template>
