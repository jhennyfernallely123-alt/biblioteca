<script setup>
import { computed } from 'vue';
import SeasonalGarland from './SeasonalGarland.vue';

const props = defineProps({
    season: { type: String, required: true },
});

const SHAPES = {
    winter: 'snow',
    christmas: 'bauble',
    spring: 'petal',
    summer: 'mote',
    autumn: 'leaf',
    halloween: 'leaf',
};

const shape = computed(() => SHAPES[props.season] ?? 'mote');

const vineItems = computed(() =>
    Array.from({ length: 10 }, (_, index) => ({
        key: `vine-${index}`,
        style: {
            top: `${6 + index * 9.5}%`,
            '--item-size': `${6 + ((index * 2) % 4)}px`,
            '--item-color': `var(--season-color-${(index % 4) + 1})`,
            '--vine-rotate': `${index % 2 === 0 ? -35 : 35}deg`,
        },
    }))
);
</script>

<template>
    <div
        class="seasonal-layer"
        :data-season="season"
        :data-season-decor="season"
        aria-hidden="true"
    >
        <SeasonalGarland :season="season" class="seasonal-trim" />

        <div class="seasonal-vine">
            <span class="seasonal-vine__line" />

            <span
                v-for="item in vineItems"
                :key="item.key"
                class="seasonal-vine__leaf"
                :class="`seasonal-particle--${shape}`"
                :style="item.style"
            />
        </div>

        <template v-if="season === 'halloween'">
            <svg class="seasonal-web seasonal-web--start" viewBox="0 0 120 120" fill="none">
                <path d="M0 0v120M0 0h120M0 0l120 120M0 0l96 120M0 0l120 96" stroke="currentColor" stroke-width="0.8" />
                <path d="M22 0c0 14 9 23 23 23M46 0c0 26 19 45 45 45M72 0c0 36 28 64 64 64" stroke="currentColor" stroke-width="0.8" />
            </svg>
            <svg class="seasonal-web seasonal-web--end" viewBox="0 0 120 120" fill="none">
                <path d="M0 0v120M0 0h120M0 0l120 120M0 0l96 120M0 0l120 96" stroke="currentColor" stroke-width="0.8" />
                <path d="M22 0c0 14 9 23 23 23M46 0c0 26 19 45 45 45M72 0c0 36 28 64 64 64" stroke="currentColor" stroke-width="0.8" />
            </svg>

            <span class="seasonal-spider seasonal-spider--one">
                <span class="seasonal-spider__thread" />
                <svg viewBox="0 0 16 16" fill="none">
                    <ellipse cx="8" cy="10.2" rx="2.4" ry="3" fill="currentColor" />
                    <circle cx="8" cy="5.8" r="1.8" fill="currentColor" />
                    <path d="M5.8 8.5 2.5 6.5M5.6 10 2 10.5M6 11.6 3.2 14M10.2 8.5l3.3-2M10.4 10l3.6.5M10 11.6l2.8 2.4" stroke="currentColor" stroke-width="1" stroke-linecap="round" />
                </svg>
            </span>
            <span class="seasonal-spider seasonal-spider--two">
                <span class="seasonal-spider__thread" />
                <svg viewBox="0 0 16 16" fill="none">
                    <ellipse cx="8" cy="10.2" rx="2.4" ry="3" fill="currentColor" />
                    <circle cx="8" cy="5.8" r="1.8" fill="currentColor" />
                    <path d="M5.8 8.5 2.5 6.5M5.6 10 2 10.5M6 11.6 3.2 14M10.2 8.5l3.3-2M10.4 10l3.6.5M10 11.6l2.8 2.4" stroke="currentColor" stroke-width="1" stroke-linecap="round" />
                </svg>
            </span>
        </template>
    </div>
</template>
