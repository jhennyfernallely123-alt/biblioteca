<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardLetterModal from '@/Components/CardLetterModal.vue';
import CardMachine from '@/Components/CardMachine.vue';
import { useI18n } from '@/i18n';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

const props = defineProps({
    cards: { type: Array, required: true },
    offers: { type: Array, required: true },
    drawn: { type: Object, default: null },
    ink: { type: Number, default: 0 },
    progress: { type: Object, required: true },
    resetAt: { type: String, required: true },
    dailyBalls: { type: Number, default: 8 },
    stickers: { type: Array, required: true },
    stickerProgress: { type: Object, required: true },
    complete: { type: Boolean, default: false },
});

const { t } = useI18n();
const cards = ref(props.cards);
const stickers = ref(props.stickers);
const stickerProgress = ref({ ...props.stickerProgress });
const ink = ref(props.ink);
const drawing = ref(false);
const result = ref(props.drawn);
const letter = ref(null);
const letterOpen = ref(false);
const error = ref('');

async function crank(index) {
    if (drawing.value) {
        return;
    }

    drawing.value = true;
    error.value = '';

    try {
        const { data } = await axios.post('/cards/draw', { ball: index });
        result.value = data;
        ink.value = data.ink;

        if (data.kind === 'card' && data.card) {
            const card = cards.value.find((item) => item.key === data.card.key);

            if (card) {
                card.unlocked = true;
                card.copies = 1;
            }
        }

        if (data.kind === 'sticker' && data.sticker) {
            const sticker = stickers.value.find((item) => item.key === data.sticker.key);

            if (sticker) {
                sticker.unlocked = true;
                sticker.acquired_at = new Date().toISOString();
            }

            stickerProgress.value.unlocked += 1;
        }
    } catch (exception) {
        error.value = exception.response?.data?.message || t('ui.cards_draw_error');
    } finally {
        drawing.value = false;
    }
}

async function openLetter(key) {
    error.value = '';

    try {
        const { data } = await axios.get(`/cards/${key}`);
        letter.value = data;
        letterOpen.value = true;
    } catch {
        error.value = t('ui.cards_letter_error');
    }
}

function openResult() {
    if (result.value?.card) {
        openLetter(result.value.card.key);
    }
}
</script>

<template>
    <Head :title="t('ui.cards_title')" />

    <AuthenticatedLayout>
        <p
            v-if="error"
            class="mb-4 rounded-control border border-danger/40 bg-danger/10 px-3 py-2 text-danger"
            role="alert"
        >
            {{ error }}
        </p>

        <p
            v-if="progress.total === 0 && !complete"
            class="mb-4 rounded-control border border-edge bg-surface px-3 py-2 text-sm text-ink-muted"
        >
            {{ t('ui.cards_empty_help') }}
            <Link href="/books/create" class="text-accent underline">{{ t('ui.add_book') }}</Link>
        </p>

        <div class="mx-auto w-full max-w-md">
            <CardMachine
                :offers="offers"
                :drawing="drawing"
                :result="result"
                :reset-at="resetAt"
                :complete="complete"
                @crank="crank"
                @open="openResult"
            />

        </div>

        <CardLetterModal
            :show="letterOpen"
            :letter="letter"
            :copies="letter?.copies ?? 0"
            :duplicate="letter?.duplicate ?? false"
            @close="letterOpen = false"
        />
    </AuthenticatedLayout>
</template>
