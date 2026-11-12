<script setup>
import { useI18n } from '@/i18n';

defineProps({
    show: { type: Boolean, default: false },
    item: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const { t } = useI18n();
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 bg-ink/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click.self="emit('close')"
    >
        <div class="bg-surface rounded-modal border border-edge shadow-2xl max-w-md w-full p-6 transform animate-popIn">
            <button
                type="button"
                class="absolute top-4 right-4 text-ink-muted hover:text-ink w-10 h-10 rounded-control flex items-center justify-center"
                @click="emit('close')"
            >
                <span class="text-tinta">✕</span>
            </button>

            <div class="text-center pt-2">
                <div class="w-32 h-32 mx-auto mb-4 flex items-center justify-center rounded-xl border border-edge bg-surface/80 p-2">
                    <component
                        v-if="item"
                        :is="item.type === 'sticker' ? 'LibrarySticker' : 'CardEmblem'"
                        :key="item.key"
                        :sticker="item.key"
                        :name="item.image ?? 'book'"
                        :size="112"
                    />
                </div>

                <span
                    v-if="item"
                    class="inline-block px-3 py-1 text-[10px] font-bold rounded-full mb-2"
                    :class="{
                        'bg-amber-100 text-amber-700': item.rarity === 'epica',
                        'bg-blue-100 text-blue-700': item.rarity === 'rara',
                        'bg-surface text-ink-muted': item.rarity === 'comun',
                    }"
                >
                    {{ t(`ui.album_rarity_${item.rarity}`) }}
                </span>

                <h3 v-if="item" class="font-serif font-bold text-xl text-tinta mb-1">
                    {{ item.name }}
                </h3>
                <p v-if="item" class="text-sm text-ink-muted mb-4 px-2">
                    {{ t('ui.album_category', { category: item.category }) }}
                </p>

                <div v-if="item" class="bg-pergamino rounded-card p-3 flex justify-around text-xs font-bold text-tinta mb-4 border border-edge">
                    <div>
                        <span class="block text-ink-muted text-[10px] uppercase">{{ t('ui.album_number') }}</span>
                        <span class="text-tinta">#{{ item.key }}</span>
                    </div>
                    <div v-if="item.acquired_at" class="border-l border-edge/40 pl-3">
                        <span class="block text-ink-muted text-[10px] uppercase">{{ t('ui.album_acquired') }}</span>
                        <span class="text-tinta">{{ item.acquired_at }}</span>
                    </div>
                </div>

                <button type="button" v-if="item" class="w-full py-3 bg-accent text-white font-bold rounded-control hover:bg-accent/90 transition" @click="emit('close')">
                    {{ t('ui.album_understood') }}
                </button>
            </div>
        </div>
    </div>
</template>

<style>
.animate-popIn {
    animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}
@keyframes popIn {
    0% { transform: scale(0.5); opacity: 0; }
    80% { transform: scale(1.08); }
    100% { transform: scale(1); opacity: 1; }
}
</style>
