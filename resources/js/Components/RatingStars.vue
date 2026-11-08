<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: Number, default: 0 },
    editable: { type: Boolean, default: false },
    kind: { type: String, default: 'star' },
    label: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const values = computed(() => [1, 2, 3, 4, 5]);

const ACTIVE = {
    star: 'text-accent',
    chili: 'text-danger',
    heart: 'text-tertiary',
    drop: 'text-ink-muted',
    crystal: 'text-secondary',
};

const INACTIVE = {
    star: 'text-accent/40',
    chili: 'text-danger/40',
    heart: 'text-tertiary/40',
    drop: 'text-ink-muted/40',
    crystal: 'text-secondary/40',
};

const activeClass = computed(() => ACTIVE[props.kind] ?? ACTIVE.star);
const inactiveClass = computed(() => INACTIVE[props.kind] ?? INACTIVE.star);

function select(value) {
    if (props.editable) {
        emit('update:modelValue', value);
    }
}
</script>

<template>
    <div class="flex gap-1" role="group" :aria-label="label">
        <button
            v-for="value in values"
            :key="value"
            type="button"
            class="leading-none transition-colors disabled:cursor-default"
            :class="value <= modelValue ? activeClass : inactiveClass"
            :disabled="!editable"
            :data-star="kind === 'star' ? '' : undefined"
            :data-rating="kind"
            :data-value="value"
            :aria-label="`${label} ${value}`"
            :title="`${label} ${value}`"
            @click="select(value)"
        >
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path v-if="kind === 'star'" d="M10 1.8l2.4 5 5.5.8-4 3.9.9 5.5L10 14.4 5.2 17l.9-5.5-4-3.9 5.5-.8z" />
                <template v-else-if="kind === 'chili'">
                    <path d="M13.1 3.3c1.5 0 2.6 1.2 2.6 2.8 0 1.3-.7 2.4-1.6 3.6-1.1 1.6-2.2 3.2-2.2 5.2 0 1.6 1 2.8 2.7 3.2-2.3.6-4.5-.9-4.5-3.8 0-2.4 1.4-4.4 2.5-6 .5-.7.8-1.3.8-1.9 0-.6-.4-1-1-1-.7 0-1.2.6-1.6 1.2-.3.4-.8.5-1.1.2-.3-.3-.3-.7-.1-1.1.8-1.4 2-2.4 3.5-2.4z" />
                    <path d="M13.1 3.3c.2-1 1-1.8 2.1-2" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
                </template>
                <path v-else-if="kind === 'heart'" d="M10 17.5 2.7 10.2a4.6 4.6 0 1 1 6.5-6.5l.8.8.8-.8a4.6 4.6 0 1 1 6.5 6.5z" />
                <path v-else-if="kind === 'drop'" d="M10 2c3 4 5 6.5 5 9.5a5 5 0 0 1-10 0C5 8.5 7 6 10 2z" />
                <template v-else>
                    <circle cx="10" cy="8" r="5.5" />
                    <path d="M5 14h10l-1.5 3h-7z" />
                </template>
            </svg>
        </button>
    </div>
</template>
