<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EventSticker from '@/Components/EventSticker.vue';
import { useI18n } from '@/i18n';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

const props = defineProps({
    event: { type: Object, required: true },
    tasks: { type: Array, required: true },
    shop: { type: Array, required: true },
    collection: { type: Array, required: true },
    ink: { type: Number, default: 0 },
});

const { t } = useI18n();
const ink = ref(props.ink);
const collection = ref(props.collection);
const items = ref(props.shop);
const error = ref('');
const buying = ref('');
const notice = ref('');

async function buy(itemKey) {
    if (buying.value) {
        return;
    }

    buying.value = itemKey;
    error.value = '';
    notice.value = '';

    try {
        const { data } = await axios.post('/events/buy', { item: itemKey });
        ink.value = data.ink;

        const item = items.value.find((entry) => entry.key === itemKey);

        if (item) {
            item.owned = true;
        }

        if (! collection.value.some((entry) => entry.key === data.collectible.key)) {
            collection.value = [
                ...collection.value,
                {
                    key: data.collectible.key,
                    kind: data.collectible.kind,
                    eventKey: props.event.key,
                    name: item?.name ?? data.collectible.key,
                    emblem: item?.emblem ?? null,
                    initial: item?.initial ?? null,
                },
            ];
        }

        notice.value = t('ui.events_owned');
    } catch (exception) {
        error.value = exception.response?.data?.message || t('ui.events_buy_error');
    } finally {
        buying.value = '';
    }
}
</script>

<template>
    <Head :title="t('ui.nav_events')" />

    <AuthenticatedLayout>
        <header class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="font-serif text-3xl text-accent">{{ t(`ui.event_name_${event.key}`) }}</h1>
                    <span class="event-theme-chip" :data-event-theme="event.theme">
                        {{ t(`ui.events_theme_${event.theme}`) }}
                    </span>
                </div>
                <p class="mt-1 text-ink-muted">
                    {{ t('ui.events_day', { day: event.day, days: event.days }) }}
                </p>
            </div>
            <div class="card-ink">
                <span class="text-sm text-ink-muted">{{ t('ui.events_ink_label') }}</span>
                <strong class="text-2xl text-accent">{{ ink }}</strong>
            </div>
        </header>

        <p
            v-if="error"
            class="mb-4 rounded-control border border-danger/40 bg-danger/10 px-3 py-2 text-danger"
            role="alert"
        >
            {{ error }}
        </p>
        <p v-else-if="notice" class="sr-only" role="status">{{ notice }}</p>

        <div class="grid gap-6 lg:grid-cols-[1fr_1.2fr]">
            <section class="event-panel" :aria-label="t('ui.events_tasks')">
                <h2 class="font-serif text-xl text-accent">{{ t('ui.events_tasks') }}</h2>
                <ul class="mt-4 space-y-2">
                    <li
                        v-for="task in tasks"
                        :key="task.key"
                        class="event-task"
                        :class="task.done ? 'event-task--done' : ''"
                        :aria-current="task.done ? 'true' : undefined"
                    >
                        <span class="event-task__mark" aria-hidden="true">{{ task.done ? '✓' : '·' }}</span>
                        <span class="flex-1">{{ t(`ui.events_task_${task.key}`) }}</span>
                        <span class="text-sm text-accent">+{{ task.ink }}</span>
                        <span class="text-xs text-ink-muted">
                            {{ task.done ? t('ui.events_done') : t('ui.events_pending') }}
                        </span>
                    </li>
                </ul>
            </section>

            <section class="event-panel" :aria-label="t('ui.events_shop')">
                <h2 class="font-serif text-xl text-accent">{{ t('ui.events_shop') }}</h2>
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <article
                        v-for="item in items"
                        :key="item.key"
                        class="event-item"
                        :class="item.owned ? 'event-item--owned' : ''"
                    >
                        <EventSticker v-if="item.kind === 'sticker'" :item-key="item.key" :size="64" />
                        <span v-else class="card-monogram h-14 w-14 text-lg" aria-hidden="true">
                            {{ item.initial }}
                        </span>
                        <span class="mt-2 text-center text-sm leading-tight text-ink">
                            {{ t(`ui.event_item_${item.key}`) }}
                        </span>
                        <span class="text-xs text-ink-muted">{{ item.cost }} {{ t('ui.events_ink_label') }}</span>
                        <span v-if="item.owned" class="event-item__owned">{{ t('ui.events_owned') }}</span>
                        <button
                            v-else
                            type="button"
                            class="event-buy"
                            :disabled="ink < item.cost || buying === item.key"
                            :aria-label="`${t('ui.events_buy')} ${t(`ui.event_item_${item.key}`)}`"
                            @click="buy(item.key)"
                        >
                            {{ t('ui.events_buy') }}
                        </button>
                    </article>
                </div>
            </section>
        </div>

        <section class="event-panel mt-6" :aria-label="t('ui.events_collection')">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-serif text-xl text-accent">{{ t('ui.events_collection') }}</h2>
                <Link
                    href="/events/album"
                    class="rounded-control border border-dorado/60 px-3 py-2 text-sm text-accent hover:border-accent"
                >
                    {{ t('ui.events_open_album') }}
                </Link>
            </div>
            <div v-if="collection.length" class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                <article
                    v-for="item in collection"
                    :key="item.key"
                    class="event-item"
                >
                    <EventSticker v-if="item.kind === 'sticker'" :item-key="item.key" :size="64" />
                    <span v-else class="card-monogram h-14 w-14 text-lg" aria-hidden="true">
                        {{ item.initial }}
                    </span>
                    <span class="mt-2 text-center text-sm leading-tight text-ink">
                        {{ t(`ui.event_item_${item.key}`) }}
                    </span>
                </article>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
