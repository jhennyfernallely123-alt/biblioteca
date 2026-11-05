<script setup>
import CardEmblem from './CardEmblem.vue';
import { useI18n } from '@/i18n';

defineProps({
    cards: { type: Array, required: true },
    progress: { type: Object, required: true },
});

defineEmits(['open']);

const { t } = useI18n();
</script>

<template>
    <section class="card-album" :aria-label="t('ui.cards_album')">
        <header class="mb-4">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="font-serif text-xl text-gold">{{ t('ui.cards_album') }}</h2>
                <span class="text-sm text-mist">
                    {{ t('ui.cards_album_progress', { unlocked: progress.unlocked, total: progress.total }) }}
                </span>
            </div>
            <div
                class="mt-2 h-2 rounded-full bg-night"
                role="progressbar"
                :aria-valuenow="progress.unlocked"
                :aria-valuemin="0"
                :aria-valuemax="progress.total"
                :aria-label="t('ui.cards_album')"
            >
                <div
                    class="h-full rounded-full bg-gold transition-all"
                    :style="{ width: `${progress.total ? (progress.unlocked / progress.total) * 100 : 0}%` }"
                ></div>
            </div>
        </header>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            <template v-for="card in cards" :key="card.key">
                <button
                    v-if="card.unlocked"
                    type="button"
                    class="card-slot flex min-h-40 flex-col items-center justify-center gap-2 p-3 text-center text-moon transition hover:ring-2 hover:ring-gold/60"
                    @click="$emit('open', card.key)"
                >
                    <CardEmblem :name="card.emblem" :size="26" class="text-gold" />
                    <span class="card-monogram h-10 w-10 text-sm">{{ card.monogram }}</span>
                    <span class="text-sm leading-tight">{{ card.character }}</span>
                    <span class="text-xs text-mist">{{ card.familyLabel }}</span>
                    <span v-if="card.copies > 1" class="font-serif text-sm text-gold">
                        {{ t('ui.cards_copies', { count: card.copies }) }}
                    </span>
                </button>

                <div
                    v-else
                    class="card-slot card-back min-h-40 flex-col gap-2 p-3 text-center"
                    aria-disabled="true"
                    :title="t('ui.cards_locked_hint')"
                >
                    <CardEmblem :name="card.familyEmblem" :size="26" />
                    <span class="card-monogram h-10 w-10 text-lg" aria-hidden="true">?</span>
                    <span class="text-sm text-mist">{{ t('ui.cards_locked') }}</span>
                    <span class="sr-only">{{ t('ui.cards_locked_hint') }}</span>
                </div>
            </template>
        </div>

        <footer class="mt-4 text-center text-xs text-mist">
            {{ t('ui.cards_disclaimer') }}
        </footer>
    </section>
</template>
