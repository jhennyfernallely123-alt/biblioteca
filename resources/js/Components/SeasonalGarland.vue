<script setup>
import { computed } from 'vue';

const props = defineProps({
    season: { type: String, required: true },
    count: { type: Number, default: 8 },
    compact: { type: Boolean, default: false },
});

const SHAPES = {
    winter: 'snow',
    valentine: 'heart',
    spring: 'petal',
    rain: 'drop',
    flowers: 'petal',
    summer: 'mote',
    sun: 'star',
    sunflower: 'mote',
    harvest: 'leaf',
    halloween: 'leaf',
    autumn: 'leaf',
    christmas: 'bauble',
};

const shape = computed(() => SHAPES[props.season] ?? 'mote');

const items = computed(() =>
    Array.from({ length: props.count }, (_, index) => ({
        key: `garland-${index}`,
        style: {
            left: `${8 + (index * 84) / Math.max(1, props.count - 1)}%`,
            '--hang-length': `${
                props.compact ? 6 + ((index * 3) % 8) : 12 + ((index * 5) % 16)
            }px`,
        },
        itemStyle: {
            '--item-size': `${
                props.compact ? 6 + ((index * 2) % 4) : 9 + ((index * 3) % 5)
            }px`,
            '--item-color': `var(--season-color-${(index % 4) + 1})`,
        },
    }))
);
</script>

<template>
    <div
        class="seasonal-garland"
        :class="{ 'seasonal-garland--compact': compact }"
        :data-season-garland="season"
        aria-hidden="true"
    >
        <span class="seasonal-trim__string" />

        <span
            v-for="item in items"
            :key="item.key"
            class="seasonal-trim__hang"
            :style="item.style"
        >
            <span class="seasonal-trim__thread" />
            <svg
                v-if="season === 'halloween'"
                class="seasonal-trim__bat"
                viewBox="0 0 20 10"
                fill="none"
            >
                <path d="M10 3C8 0 4 0 1 3c1 3 4 4 6 3-1 2 1 4 3 4s4-2 3-4c2 1 5 0 6-3-3-3-7-3-9 0z" fill="currentColor" />
            </svg>
            <span
                v-else
                class="seasonal-trim__item"
                :class="`seasonal-particle--${shape}`"
                :style="item.itemStyle"
            />
        </span>
    </div>
</template>
