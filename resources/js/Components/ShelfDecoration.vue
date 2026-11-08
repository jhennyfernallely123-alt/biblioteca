<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import DecorationArt from './DecorationArt.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    decoration: { type: Object, required: true },
    dragging: { type: Boolean, default: false },
    dropPosition: { type: String, default: null },
});

const { t } = useI18n();

const name = computed(() => t(`ui.decoration_${props.decoration.kind}`));

function remove() {
    router.delete(`/decorations/${props.decoration.id}`, {
        preserveScroll: true,
        preserveState: true,
    });
}
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

        <button
            type="button"
            class="absolute -right-1 -top-1 hidden h-7 w-7 items-center justify-center rounded-full border border-edge bg-surface-raised text-ink opacity-0 shadow-card transition-opacity focus-visible:opacity-100 group-hover:opacity-100 sm:flex"
            :aria-label="t('ui.decoration_remove')"
            :title="t('ui.decoration_remove')"
            @pointerdown.stop
            @touchstart.stop
            @click.prevent="remove"
        >
            <svg class="h-3.5 w-3.5" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                <path d="M3.5 3.5l7 7m0-7l-7 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            </svg>
        </button>
    </div>
</template>
