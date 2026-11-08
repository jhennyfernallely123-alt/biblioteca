<script setup>
import { computed, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import DecorationArt from './DecorationArt.vue';
import CategoryIcon from './CategoryIcon.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    decorations: { type: Array, default: () => [] },
});

const { t } = useI18n();

const CATEGORIES = [
    {
        key: 'table',
        items: [
            { kind: 'cup', color: 'cream' },
            { kind: 'tea_set', color: 'cream' },
            { kind: 'music_box', color: 'forest' },
        ],
    },
    {
        key: 'plants',
        items: [
            { kind: 'roses', color: 'rose' },
            { kind: 'plant', color: 'green' },
        ],
    },
    {
        key: 'light',
        items: [
            { kind: 'candle', color: 'gold' },
            { kind: 'lamp', color: 'gold' },
        ],
    },
    {
        key: 'curios',
        items: [
            { kind: 'hourglass', color: 'gold' },
            { kind: 'crystal_ball', color: 'rose' },
            { kind: 'skull', color: 'cream' },
            { kind: 'cage', color: 'gold' },
            { kind: 'astrolabe', color: 'gold' },
            { kind: 'quill', color: 'gold' },
        ],
    },
    {
        key: 'keepsakes',
        items: [
            { kind: 'cat', color: 'clay' },
            { kind: 'photo_frame', color: 'clay' },
        ],
    },
];

const COLORS = ['rose', 'cream', 'green', 'forest', 'gold', 'clay'];

const FILTERS = [
    { key: 'all' },
    { key: 'table' },
    { key: 'plants' },
    { key: 'light' },
    { key: 'curios' },
    { key: 'keepsakes' },
];

const activeCategory = ref('all');

const visibleItems = computed(() => {
    if (activeCategory.value === 'all') {
        return CATEGORIES.flatMap((category) => category.items);
    }

    return CATEGORIES.find((category) => category.key === activeCategory.value)?.items ?? [];
});

const COLOR_CLASS = {
    rose: 'bg-malva',
    cream: 'bg-crema',
    green: 'bg-salvia',
    forest: 'bg-bosque',
    gold: 'bg-laton',
    clay: 'bg-terracota',
};

const chosen = reactive(
    Object.fromEntries(
        CATEGORIES.flatMap((category) => category.items).map((item) => [item.kind, item.color])
    )
);

const openPalette = ref(null);

const placedByKind = computed(() => {
    const groups = {};

    props.decorations.forEach((decoration) => {
        groups[decoration.kind] = groups[decoration.kind] ?? [];
        groups[decoration.kind].push(decoration);
    });

    Object.values(groups).forEach((items) => items.sort((a, b) => b.id - a.id));

    return groups;
});

function placedFor(kind) {
    return placedByKind.value[kind] ?? [];
}

function togglePalette(kind) {
    openPalette.value = openPalette.value === kind ? null : kind;
}

function chooseColor(kind, color) {
    chosen[kind] = color;
    openPalette.value = null;
}

function nameFor(kind) {
    return t(`ui.decoration_${kind}`);
}

function filterLabel(key) {
    return key === 'all' ? t('ui.decoration_filter_all') : t(`ui.decoration_category_${key}`);
}

function add(item) {
    router.post(
        '/decorations',
        { kind: item.kind, color: chosen[item.kind] },
        { preserveScroll: true }
    );
}

function removeLast(kind) {
    const [decoration] = placedFor(kind);

    if (decoration) {
        router.delete(`/decorations/${decoration.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <div class="space-y-8">
        <div
            class="scrollbar-hidden flex gap-3 overflow-x-auto pb-2 sm:flex-wrap sm:overflow-visible"
            role="group"
            :aria-label="t('ui.decoration_categories')"
        >
            <button
                v-for="option in FILTERS"
                :key="option.key"
                type="button"
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border transition-colors"
                :class="
                    activeCategory === option.key
                        ? 'border-secondary bg-surface text-accent'
                        : 'border-edge bg-surface-raised text-ink-muted hover:text-ink'
                "
                :data-category-filter="option.key"
                :aria-label="filterLabel(option.key)"
                :aria-pressed="activeCategory === option.key"
                :title="filterLabel(option.key)"
                @click="activeCategory = option.key"
            >
                <CategoryIcon :name="option.key" class="h-6 w-6" />
            </button>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="item in visibleItems"
                :key="item.kind"
                class="relative rounded-card border border-edge bg-surface p-3"
                :data-decoration-card="item.kind"
            >
                <div class="relative mb-3 flex h-32 items-end justify-center rounded-control bg-surface-raised p-2 pl-10">
                    <DecorationArt :kind="item.kind" :color="chosen[item.kind]" class="h-full w-full" />

                    <p class="pointer-events-none absolute inset-y-1 left-1 flex w-7 items-center justify-center">
                        <span class="max-h-full overflow-hidden rounded-control bg-scrim/60 px-1 py-2 text-center font-serif text-sm leading-tight text-dorado [writing-mode:vertical-rl]">
                            {{ nameFor(item.kind) }}
                        </span>
                    </p>

                    <button
                        type="button"
                        class="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full border border-edge transition-shadow hover:ring-2 hover:ring-secondary hover:ring-offset-2 hover:ring-offset-surface-raised"
                        :class="COLOR_CLASS[chosen[item.kind]]"
                        :data-color-toggle="item.kind"
                        :aria-label="t('ui.decoration_color')"
                        :aria-expanded="openPalette === item.kind"
                        aria-haspopup="true"
                        :title="t('ui.decoration_color')"
                        @click="togglePalette(item.kind)"
                    />
                </div>

                <div
                    v-if="openPalette === item.kind"
                    class="absolute right-4 top-12 z-10 grid grid-cols-3 gap-2 rounded-card border border-edge bg-surface-raised p-2 shadow-card"
                    role="group"
                    :data-color-palette="item.kind"
                    :aria-label="t('ui.decoration_color')"
                    @keydown.esc="openPalette = null"
                >
                    <button
                        v-for="color in COLORS"
                        :key="color"
                        type="button"
                        class="h-5 w-5 rounded-full border border-edge transition-shadow"
                        :class="[COLOR_CLASS[color], chosen[item.kind] === color ? 'ring-2 ring-secondary ring-offset-2 ring-offset-surface-raised' : '']"
                        :data-color="item.kind"
                        :data-color-key="color"
                        :aria-label="t(`ui.color_${color}`)"
                        :aria-pressed="chosen[item.kind] === color"
                        :title="t(`ui.color_${color}`)"
                        @click="chooseColor(item.kind, color)"
                    />
                </div>

                <button
                    v-if="placedFor(item.kind).length"
                    type="button"
                    class="w-full rounded-control border border-danger px-4 py-2 text-danger hover:bg-danger/10"
                    :data-decoration-remove="item.kind"
                    @click="removeLast(item.kind)"
                >
                    {{ t('ui.decoration_remove_button') }}
                </button>

                <button
                    v-else
                    type="button"
                    class="w-full rounded-control bg-accent px-4 py-2 text-on-accent hover:bg-accent-strong"
                    :data-decoration-add="item.kind"
                    @click="add(item)"
                >
                    {{ t('ui.decoration_add') }}
                </button>
            </div>
        </div>
    </div>
</template>
