<script setup>
import { computed } from 'vue';

const props = defineProps({
    season: { type: String, required: true },
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
    christmas: 'snow',
};

const COUNTS = {
    winter: 16,
    valentine: 12,
    spring: 14,
    rain: 16,
    flowers: 14,
    summer: 10,
    sun: 12,
    sunflower: 12,
    harvest: 14,
    halloween: 0,
    autumn: 14,
    christmas: 20,
};

const shape = computed(() => SHAPES[props.season] ?? 'mote');

const particles = computed(() => {
    const count = COUNTS[props.season] ?? 0;

    return Array.from({ length: count }, (_, index) => {
        const left = (index * 37 + 11) % 100;
        const delay = -(((index * 7) % 100) / 10);
        const duration = 9 + ((index * 13) % 9);
        const size = 6 + ((index * 5) % 8);
        const drift = ((index * 11) % 20) - 10;

        return {
            key: `${props.season}-${index}`,
            style: {
                left: `${left}%`,
                animationDelay: `${delay}s`,
                animationDuration: `${duration}s`,
                width: `${size}px`,
                height: `${size}px`,
                '--season-drift': `${drift}px`,
                '--item-color': `var(--season-color-${(index % 4) + 1})`,
            },
        };
    });
});
</script>

<template>
    <div
        class="seasonal-particles"
        data-season-particles
        :data-season="season"
        aria-hidden="true"
    >
        <span
            v-for="particle in particles"
            :key="particle.key"
            class="seasonal-particle"
            :style="particle.style"
        >
            <span
                class="seasonal-particle__shape"
                :class="`seasonal-particle--${shape}`"
            />
        </span>
    </div>
</template>
