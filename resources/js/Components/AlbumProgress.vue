<script setup>
import { useI18n } from '@/i18n';

defineProps({
    progress: { type: Object, required: true },
});

const { t } = useI18n();
</script>

<template>
    <div class="mb-4 flex flex-col gap-1">
        <div class="flex justify-between items-end mb-1">
            <span class="text-xs uppercase tracking-wider font-bold text-ink-muted">{{ t('ui.album_progress') }}</span>
            <span class="font-serif font-bold text-xl text-accent">
                {{ progress.unlocked }} / {{ progress.total }}
            </span>
        </div>
        <div class="w-full h-3 rounded-full bg-edge overflow-hidden border border-edge/40">
            <div
                class="h-full bg-gradient-to-r from-accent via-tertiary to-danger rounded-full transition-all duration-700"
                :style="{ width: `${progress.total ? (progress.unlocked / progress.total) * 100 : 0}%` }"
            ></div>
        </div>
        <div class="flex justify-between text-[10px] font-bold text-ink-muted mt-1">
            <span>{{ t('ui.album_rarity_comun') }}: {{ progress.byRarity.comun }}</span>
            <span>{{ t('ui.album_rarity_rara') }}: {{ progress.byRarity.rara }}</span>
            <span>{{ t('ui.album_rarity_epica') }}: {{ progress.byRarity.epica }}</span>
        </div>
    </div>
</template>
