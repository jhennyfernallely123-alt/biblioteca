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
});

const { t } = useI18n();
const cards = ref(props.cards);
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

        const card = cards.value.find((item) => item.key === data.card.key);

        if (card) {
            card.unlocked = true;
            card.copies = data.copies;
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
</script>

<template>
    <Head :title="t('ui.cards_title')" />

    <AuthenticatedLayout>
        <header class="mb-6">
            <h1 class="font-serif text-3xl text-accent">{{ t('ui.cards_title') }}</h1>
        </header>

        <p
            v-if="error"
            class="mb-4 rounded-control border border-danger/40 bg-danger/10 px-3 py-2 text-danger"
            role="alert"
        >
            {{ error }}
        </p>

        <div v-if="progress.total === 0" class="rounded-card border border-edge bg-surface p-6 text-center">
            <p class="text-ink">{{ t('ui.cards_empty') }}</p>
            <p class="mt-2 text-ink-muted">{{ t('ui.cards_empty_help') }}</p>
            <Link href="/books/create" class="mt-4 inline-block rounded-control bg-tertiary px-4 py-2 text-on-accent">
                {{ t('ui.add_book') }}
            </Link>
        </div>

        <div v-else class="mx-auto w-full max-w-md">
            <CardMachine
                :offers="offers"
                :drawing="drawing"
                :result="result"
                :reset-at="resetAt"
                @crank="crank"
                @open="openLetter(result.card.key)"
            />

            <Link
                href="/cards/album"
                class="mt-6 flex min-h-11 items-center justify-center rounded-control border border-dorado/60 px-4 py-2 text-center text-sm text-accent hover:bg-surface"
            >
                {{ t('ui.cards_open_album') }}
            </Link>
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
