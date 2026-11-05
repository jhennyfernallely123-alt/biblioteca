<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardAlbum from '@/Components/CardAlbum.vue';
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
        <header class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="font-serif text-3xl text-gold">{{ t('ui.cards_title') }}</h1>
                <p class="mt-1 max-w-2xl text-mist">{{ t('ui.cards_subtitle') }}</p>
            </div>
            <div class="card-ink" :title="t('ui.cards_ink_help')">
                <span class="text-sm text-mist">{{ t('ui.cards_ink') }}</span>
                <strong class="text-2xl text-gold">{{ ink }}</strong>
                <span class="sr-only">{{ t('ui.cards_ink_help') }}</span>
            </div>
        </header>

        <p
            v-if="error"
            class="mb-4 rounded-control border border-danger/40 bg-danger/10 px-3 py-2 text-danger"
            role="alert"
        >
            {{ error }}
        </p>

        <div v-if="progress.total === 0" class="rounded-card border border-line bg-panel p-6 text-center">
            <p class="text-moon">{{ t('ui.cards_empty') }}</p>
            <p class="mt-2 text-mist">{{ t('ui.cards_empty_help') }}</p>
            <Link href="/books/create" class="mt-4 inline-block rounded-control bg-arcane px-4 py-2 text-moon">
                {{ t('ui.add_book') }}
            </Link>
        </div>

        <div v-else class="grid gap-6 lg:grid-cols-2">
            <CardMachine
                :offers="offers"
                :drawing="drawing"
                :result="result"
                :reset-at="resetAt"
                @crank="crank"
                @open="openLetter(result.card.key)"
            />
            <CardAlbum :cards="cards" :progress="progress" @open="openLetter" />
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
