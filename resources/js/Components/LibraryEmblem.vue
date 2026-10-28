<script setup>
import { useId } from 'vue';

defineProps({
    compact: {
        type: Boolean,
        default: false,
    },
});

const uid = useId();
const arcId = `biblioteca-arc-${uid}`;
const bookGradientId = `book-gold-${uid}`;
</script>

<template>
    <figure class="auth-emblem" :class="{ 'auth-emblem--compact': compact }" aria-hidden="true">
        <div class="auth-emblem__halo" />

        <div class="auth-emblem__card">
            <svg class="auth-emblem__art" viewBox="0 0 360 320" role="presentation" focusable="false">
                <defs>
                    <path :id="arcId" d="M 48 160 A 132 132 0 0 1 312 160" />
                    <linearGradient :id="bookGradientId" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stop-color="var(--color-gold)" />
                        <stop offset="1" stop-color="var(--color-magic)" />
                    </linearGradient>
                </defs>

                <text class="auth-emblem__word" text-anchor="middle">
                    <textPath :href="`#${arcId}`" startOffset="50%">Biblioteca</textPath>
                </text>

                <g class="auth-emblem__book" fill="none" :stroke="`url(#${bookGradientId})`" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M 180 214 C 146 190 112 190 76 202 L 76 248 C 112 236 146 236 180 260" />
                    <path d="M 180 214 C 214 190 248 190 284 202 L 284 248 C 248 236 214 236 180 260" />
                    <path d="M 180 214 L 180 260" />
                    <path d="M 96 214 C 122 206 144 206 168 220" opacity=".55" />
                    <path d="M 264 214 C 238 206 216 206 192 220" opacity=".55" />
                    <path d="M 98 228 C 124 220 146 220 168 234" opacity=".35" />
                    <path d="M 262 228 C 236 220 214 220 192 234" opacity=".35" />
                </g>

                <g class="auth-emblem__sparks">
                    <circle class="auth-emblem__spark auth-emblem__spark--1" cx="54" cy="92" r="2.2" />
                    <circle class="auth-emblem__spark auth-emblem__spark--2" cx="306" cy="104" r="1.8" />
                    <circle class="auth-emblem__spark auth-emblem__spark--3" cx="44" cy="238" r="1.7" />
                    <circle class="auth-emblem__spark auth-emblem__spark--4" cx="316" cy="232" r="2.1" />
                    <circle class="auth-emblem__spark auth-emblem__spark--5" cx="112" cy="76" r="1.5" />
                    <circle class="auth-emblem__spark auth-emblem__spark--6" cx="248" cy="70" r="1.7" />
                    <circle class="auth-emblem__spark auth-emblem__spark--7" cx="72" cy="176" r="1.5" />
                    <circle class="auth-emblem__spark auth-emblem__spark--8" cx="288" cy="176" r="1.6" />
                    <circle class="auth-emblem__spark auth-emblem__spark--9" cx="136" cy="282" r="1.8" />
                    <circle class="auth-emblem__spark auth-emblem__spark--10" cx="224" cy="282" r="1.6" />
                    <path class="auth-emblem__spark auth-emblem__spark--11" d="M 180 44 l 2.4 7.1 7.1 2.4 -7.1 2.4 -2.4 7.1 -2.4 -7.1 -7.1 -2.4 7.1 -2.4Z" />
                    <path class="auth-emblem__spark auth-emblem__spark--12" d="M 180 280 l 1.8 5.3 5.3 1.8 -5.3 1.8 -1.8 5.3 -1.8 -5.3 -5.3 -1.8 5.3 -1.8Z" />
                </g>
            </svg>
        </div>
    </figure>
</template>
