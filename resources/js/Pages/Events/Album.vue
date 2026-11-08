<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EventSticker from '@/Components/EventSticker.vue';
import Modal from '@/Components/Modal.vue';
import { useI18n } from '@/i18n';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    stickers: { type: Array, required: true },
    progress: { type: Object, required: true },
});

const { t } = useI18n();

const ALL = 'all';
const activeEvent = ref(ALL);
const selected = ref(null);

function selectAll() {
    activeEvent.value = ALL;
}

function selectEvent(key) {
    activeEvent.value = key;
}

const events = computed(() => {
    const seen = new Map();
    for (const sticker of props.stickers) {
        if (!seen.has(sticker.event_key)) {
            seen.set(sticker.event_key, sticker.event_name ?? sticker.event_key);
        }
    }
    return Array.from(seen.entries()).map(([key, name]) => ({ key, name }));
});

const filtered = computed(() =>
    activeEvent.value === ALL
        ? props.stickers
        : props.stickers.filter((sticker) => sticker.event_key === activeEvent.value)
);

const rarityLabel = (rarity) => t(`ui.sticker_rarity_${rarity}`);

const formatDate = (iso) => {
    if (!iso) {
        return '';
    }
    return new Intl.DateTimeFormat(document.documentElement?.lang || 'es', {
        dateStyle: 'medium',
    }).format(new Date(iso));
};

const stickerNumber = (sticker) =>
    String(props.stickers.indexOf(sticker) + 1).padStart(2, '0');

function open(sticker) {
    selected.value = sticker;
}

function close() {
    selected.value = null;
}
</script>

<template>
    <AuthenticatedLayout>
        <section class="sticker-album" :aria-label="t('ui.events_stickers_album')">
            <div class="sticker-album__rings" aria-hidden="true">
                <span class="sticker-album__ring" v-for="n in 8" :key="n" />
            </div>

            <header class="sticker-album__header mb-6 flex items-start justify-between gap-4">
                <div>
                    <h1 class="sticker-album__title">{{ t('ui.events_stickers_album') }}</h1>
                    <p class="sticker-album__hint">{{ t('ui.sticker_hint') }}</p>
                </div>
                <a href="/events" class="sticker-album__pill">
                    <span class="text-ink-muted">{{ t('ui.nav_events') }}</span>
                </a>
            </header>

            <div class="sticker-album__progress">
                <p class="sticker-album__label">{{ t('ui.sticker_progress_label') }}</p>
                <p class="sticker-album__title-big">{{ t('ui.sticker_progress_title') }}</p>
                <p class="sticker-album__completed">
                    {{ progress.owned }} / {{ progress.total }} — {{ t('ui.sticker_completed') }}
                </p>
                <div class="sticker-album__bar">
                    <div
                        class="sticker-album__bar-fill"
                        :style="{ width: `${progress.total ? (progress.owned / progress.total) * 100 : 0}%` }"
                        role="progressbar"
                        :aria-valuenow="progress.owned"
                        :aria-valuemin="0"
                        :aria-valuemax="progress.total"
                    />
                </div>
                <div class="sticker-album__counters">
                    <span class="sticker-album__counter">
                        <strong>{{ progress.by_rarity.comun }}</strong> {{ rarityLabel('comun') }}
                    </span>
                    <span class="sticker-album__counter">
                        <strong>{{ progress.by_rarity.rara }}</strong> {{ rarityLabel('rara') }}
                    </span>
                    <span class="sticker-album__counter">
                        <strong>{{ progress.by_rarity.epica }}</strong> {{ rarityLabel('epica') }}
                    </span>
                </div>
            </div>

            <div class="sticker-album__filters">
                <button
                    type="button"
                    class="sticker-album__pill"
                    :class="{ 'sticker-album__pill--active': activeEvent === ALL }"
                    @click="selectAll()"
                    :aria-pressed="activeEvent === ALL"
                >
                    {{ t('ui.sticker_all') }}
                </button>
                <button
                    v-for="event in events"
                    :key="event.key"
                    type="button"
                    class="sticker-album__pill"
                    :class="{ 'sticker-album__pill--active': activeEvent === event.key }"
                    @click="selectEvent(event.key)"
                    :aria-pressed="activeEvent === event.key"
                >
                    {{ event.name }}
                </button>
            </div>

            <div class="sticker-album__grid">
                <article
                    v-for="sticker in filtered"
                    :key="sticker.key"
                    class="sticker-album__card"
                    :class="{ 'sticker-album__card--locked': !sticker.owned }"
                    @click="sticker.owned && open(sticker)"
                >
                    <EventSticker
                        :item-key="sticker.key"
                        :size="112"
                        :locked="!sticker.owned"
                        class="sticker-album__sticker"
                    />
                    <p class="sticker-album__name">
                        {{ sticker.owned ? t(`ui.event_item_${sticker.key}`) : t('ui.sticker_locked') }}
                    </p>
                    <p class="sticker-album__event">
                        {{ sticker.event_name ?? sticker.event_key }}
                    </p>
                    <span
                        v-if="sticker.owned"
                        class="sticker-album__badge"
                        :class="`sticker-album__badge--${sticker.rarity}`"
                    >
                        {{ rarityLabel(sticker.rarity) }}
                    </span>
                    <span v-else class="sticker-album__locked-tag">
                        {{ t('ui.sticker_locked') }}
                    </span>
                </article>
            </div>
        </section>

        <Modal
            :show="selected !== null"
            max-width="md"
            @close="close"
        >
            <template v-if="selected">
                <EventSticker
                    :item-key="selected.key"
                    :size="160"
                    class="sticker-album__modal-sticker"
                />
                <span
                    class="sticker-album__badge"
                    :class="`sticker-album__badge--${selected.rarity}`"
                >
                    {{ rarityLabel(selected.rarity) }}
                </span>
                <h2 class="font-serif text-2xl text-ink mt-2 mb-1">
                    {{ t(`ui.event_item_${selected.key}`) }}
                </h2>
                <p class="text-ink-muted text-sm mb-4">
                    {{ selected.event_name ?? selected.event_key }}
                </p>
                <p class="text-ink-muted text-sm mb-4">
                    {{ t(`ui.sticker_desc_${selected.key}`) }}
                </p>
                <div class="border border-edge rounded p-3 bg-surface">
                    <p class="text-xs text-ink-muted mb-1">{{ t('ui.sticker_number', { n: stickerNumber(selected) }) }}</p>
                    <p class="text-xs text-ink-muted mb-1">
                        {{ t('ui.sticker_acquired') }}: {{ formatDate(selected.acquired_at) }}
                    </p>
                </div>
                <button
                    type="button"
                    class="mt-4 px-4 py-2 rounded bg-accent text-on-accent font-semibold"
                    @click="close"
                >
                    {{ t('ui.sticker_got_it') }}
                </button>
            </template>
        </Modal>
    </AuthenticatedLayout>
</template>
