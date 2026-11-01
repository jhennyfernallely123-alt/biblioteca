<script setup>
import { computed } from 'vue';

const props = defineProps({
    kind: { type: String, required: true },
    color: { type: String, default: 'cream' },
});

const TOKENS = {
    rose: '--color-orquidea',
    cream: '--color-crema',
    green: '--color-salvia',
    forest: '--color-bosque',
    gold: '--color-gold',
    clay: '--color-terracota',
};

const accent = computed(() => `var(${TOKENS[props.color] ?? TOKENS.cream})`);
const ink = 'var(--color-tinta)';
const paper = 'var(--color-pergamino)';
const wood = 'var(--color-madera)';
</script>

<template>
    <svg
        viewBox="0 0 64 80"
        fill="none"
        aria-hidden="true"
        :style="{ color: accent }"
        class="overflow-visible"
    >
        <g v-if="kind === 'cup'">
            <ellipse cx="30" cy="72" rx="18" ry="4" :fill="ink" opacity="0.25" />
            <path d="M16 40h28l-3 22a6 6 0 0 1-6 5H25a6 6 0 0 1-6-5z" fill="currentColor" />
            <path d="M16 40h28l-1 7H17z" :fill="paper" opacity="0.55" />
            <path d="M44 46a9 9 0 1 1 0 16" :stroke="ink" stroke-width="3" opacity="0.6" />
            <ellipse cx="30" cy="40" rx="14" ry="4" :fill="paper" opacity="0.8" />
        </g>

        <g v-else-if="kind === 'roses'">
            <ellipse cx="32" cy="74" rx="16" ry="4" :fill="ink" opacity="0.25" />
            <path d="M22 56h20l-3 16H25z" fill="currentColor" />
            <path d="M32 56V34M32 44l-8-6M32 48l8-7" :stroke="wood" stroke-width="2.5" />
            <circle cx="24" cy="26" r="8" fill="currentColor" />
            <circle cx="40" cy="24" r="9" fill="currentColor" />
            <circle cx="32" cy="16" r="7" fill="currentColor" />
            <circle cx="24" cy="26" r="3" :fill="ink" opacity="0.35" />
            <circle cx="40" cy="24" r="3.5" :fill="ink" opacity="0.35" />
            <circle cx="32" cy="16" r="2.5" :fill="ink" opacity="0.35" />
        </g>

        <g v-else-if="kind === 'plant'">
            <ellipse cx="32" cy="74" rx="15" ry="4" :fill="ink" opacity="0.25" />
            <path d="M21 54h22l-3 18H24z" fill="currentColor" />
            <path d="M21 54h22l-1 5H22z" :fill="paper" opacity="0.5" />
            <path d="M32 54V30" :stroke="wood" stroke-width="2.5" />
            <path d="M32 44c-10 0-14-8-14-14 9 0 14 5 14 14z" fill="currentColor" opacity="0.85" />
            <path d="M32 38c10 0 14-8 14-14-9 0-14 5-14 14z" fill="currentColor" />
            <path d="M32 30c-6-4-7-12-4-17 6 3 8 10 4 17z" fill="currentColor" opacity="0.7" />
        </g>

        <g v-else-if="kind === 'candle'">
            <ellipse cx="32" cy="74" rx="14" ry="4" :fill="ink" opacity="0.25" />
            <ellipse cx="32" cy="70" rx="13" ry="4" fill="currentColor" opacity="0.8" />
            <rect x="24" y="34" width="16" height="34" rx="3" fill="currentColor" />
            <rect x="24" y="34" width="6" height="34" rx="3" :fill="paper" opacity="0.35" />
            <path d="M32 34v-5" :stroke="ink" stroke-width="2" />
            <path d="M32 12c6 6 7 10 4 14-2 3-7 3-9 0-2-4 0-8 5-14z" fill="var(--color-gold)" />
            <path d="M32 18c3 3 3 6 1 8-1 1-3 1-4-1-1-2 0-5 3-7z" :fill="paper" opacity="0.7" />
        </g>

        <g v-else-if="kind === 'hourglass'">
            <ellipse cx="32" cy="74" rx="14" ry="4" :fill="ink" opacity="0.25" />
            <rect x="18" y="12" width="28" height="4" rx="2" fill="currentColor" />
            <rect x="18" y="66" width="28" height="4" rx="2" fill="currentColor" />
            <path d="M22 16h20l-10 22z" fill="currentColor" opacity="0.9" />
            <path d="M22 66h20L32 44z" fill="currentColor" opacity="0.55" />
            <path d="M22 16h20M22 66h20" :stroke="ink" stroke-width="1.5" opacity="0.5" />
            <path d="M32 44v16" stroke="var(--color-gold)" stroke-width="2" />
            <ellipse cx="32" cy="64" rx="6" ry="2" fill="var(--color-gold)" />
        </g>

        <g v-else-if="kind === 'crystal_ball'">
            <ellipse cx="32" cy="72" rx="18" ry="5" :fill="ink" opacity="0.25" />
            <path d="M20 64h24l4 8H16z" fill="currentColor" />
            <circle cx="32" cy="40" r="24" fill="currentColor" opacity="0.9" />
            <circle cx="32" cy="40" r="24" :stroke="ink" stroke-width="1.5" opacity="0.35" />
            <path d="M22 32c4-6 12-9 18-6" :stroke="paper" stroke-width="3" stroke-linecap="round" opacity="0.7" />
            <path d="M44 46l3 3-3 3-3-3z" fill="var(--color-gold)" />
        </g>

        <g v-else-if="kind === 'lamp'">
            <ellipse cx="32" cy="74" rx="16" ry="4" :fill="ink" opacity="0.25" />
            <path d="M18 30h28l-6 18H24z" fill="currentColor" />
            <path d="M18 30h28l-1 4H19z" :fill="paper" opacity="0.45" />
            <rect x="30" y="48" width="4" height="18" :fill="ink" opacity="0.55" />
            <ellipse cx="32" cy="68" rx="12" ry="4" fill="currentColor" />
            <path d="M22 52h20" stroke="var(--color-gold)" stroke-width="2" opacity="0.7" />
        </g>

        <g v-else-if="kind === 'cat'">
            <ellipse cx="32" cy="74" rx="16" ry="4" :fill="ink" opacity="0.25" />
            <path d="M20 68c0-12 5-20 12-20s12 8 12 20z" fill="currentColor" />
            <circle cx="32" cy="34" r="13" fill="currentColor" />
            <path d="M21 25l-2-12 11 6zM43 25l2-12-11 6z" fill="currentColor" />
            <circle cx="27" cy="33" r="2" :fill="ink" />
            <circle cx="37" cy="33" r="2" :fill="ink" />
            <path d="M32 38l-2 2h4z" :fill="ink" opacity="0.7" />
            <path d="M28 42c1 2 7 2 8 0" :stroke="ink" stroke-width="1.5" opacity="0.6" />
            <path d="M44 62c6-2 8-8 6-12" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
        </g>

        <g v-else-if="kind === 'skull'">
            <ellipse cx="32" cy="74" rx="16" ry="4" :fill="ink" opacity="0.25" />
            <path d="M32 10c-12 0-20 9-20 22 0 9 5 15 11 18v10h18V50c6-3 11-9 11-18 0-13-8-22-20-22z" fill="currentColor" />
            <circle cx="24" cy="34" r="5" :fill="ink" />
            <circle cx="40" cy="34" r="5" :fill="ink" />
            <path d="M32 42l-3 6h6z" :fill="ink" opacity="0.7" />
            <path d="M26 56v6M32 56v6M38 56v6" :stroke="ink" stroke-width="2" opacity="0.5" />
        </g>

        <g v-else-if="kind === 'cage'">
            <ellipse cx="32" cy="74" rx="16" ry="4" :fill="ink" opacity="0.25" />
            <path d="M14 64h36" stroke="currentColor" stroke-width="4" />
            <path d="M18 64V32a14 14 0 0 1 28 0v32" stroke="currentColor" stroke-width="3" />
            <path d="M24 64V32M32 64V22M40 64V32" stroke="currentColor" stroke-width="2" opacity="0.8" />
            <path d="M32 12v6" stroke="currentColor" stroke-width="3" />
            <circle cx="32" cy="10" r="3" fill="var(--color-gold)" />
        </g>

        <g v-else-if="kind === 'astrolabe'">
            <ellipse cx="32" cy="74" rx="16" ry="4" :fill="ink" opacity="0.25" />
            <circle cx="32" cy="42" r="28" stroke="currentColor" stroke-width="4" />
            <circle cx="32" cy="42" r="20" stroke="currentColor" stroke-width="2" opacity="0.8" />
            <circle cx="32" cy="42" r="11" stroke="var(--color-gold)" stroke-width="2" />
            <path d="M4 42h56M32 14v56M12 22l40 40M52 22L12 62" stroke="currentColor" stroke-width="1.5" opacity="0.55" />
            <circle cx="32" cy="42" r="3" fill="var(--color-gold)" />
            <path d="M32 70v6" stroke="currentColor" stroke-width="3" />
        </g>

        <g v-else-if="kind === 'tea_set'">
            <ellipse cx="32" cy="74" rx="20" ry="4" :fill="ink" opacity="0.25" />
            <path d="M12 52h30v8a10 10 0 0 1-10 10H22a10 10 0 0 1-10-10z" fill="currentColor" />
            <path d="M12 52h30v4H12z" :fill="paper" opacity="0.5" />
            <path d="M42 56h8a6 6 0 0 1 0 12h-8" stroke="currentColor" stroke-width="4" />
            <path d="M27 52c-2-6 2-10 0-16" stroke="currentColor" stroke-width="2" opacity="0.6" />
            <path d="M14 66h26l-4 6H18z" fill="currentColor" opacity="0.7" />
        </g>

        <g v-else-if="kind === 'music_box'">
            <ellipse cx="32" cy="74" rx="18" ry="4" :fill="ink" opacity="0.25" />
            <rect x="12" y="46" width="40" height="24" rx="3" fill="currentColor" />
            <rect x="12" y="46" width="40" height="6" rx="3" :fill="paper" opacity="0.5" />
            <path d="M14 42h36l-4-14H18z" fill="currentColor" opacity="0.85" />
            <circle cx="46" cy="58" r="3" fill="var(--color-gold)" />
            <path d="M50 58h6" stroke="var(--color-gold)" stroke-width="2" />
            <path d="M22 58h10M22 63h14" :stroke="ink" stroke-width="1.5" opacity="0.45" />
        </g>

        <g v-else-if="kind === 'quill'">
            <ellipse cx="32" cy="74" rx="16" ry="4" :fill="ink" opacity="0.25" />
            <path d="M20 70h24l-3 4H23z" fill="currentColor" />
            <path d="M32 70V54" stroke="currentColor" stroke-width="6" />
            <path d="M34 56c14-6 20-18 18-30-12 2-22 12-24 24z" fill="currentColor" opacity="0.85" />
            <path d="M34 56c6-8 12-16 16-26" :stroke="ink" stroke-width="1.5" opacity="0.5" />
            <path d="M26 52l-4-8 8 2z" fill="var(--color-gold)" />
        </g>

        <g v-else-if="kind === 'photo_frame'">
            <ellipse cx="32" cy="74" rx="16" ry="4" :fill="ink" opacity="0.25" />
            <rect x="14" y="14" width="36" height="46" rx="3" fill="currentColor" />
            <rect x="19" y="19" width="26" height="36" rx="2" :fill="paper" />
            <path d="M21 50l8-11 6 7 5-6 4 10z" :fill="wood" opacity="0.8" />
            <circle cx="38" cy="27" r="3" fill="var(--color-gold)" />
            <path d="M32 60v10" stroke="currentColor" stroke-width="3" />
            <path d="M24 70h16" stroke="currentColor" stroke-width="3" />
        </g>
    </svg>
</template>
