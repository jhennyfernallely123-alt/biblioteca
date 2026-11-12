<script setup>
defineProps({
    value: { type: Number, default: 0 },
    max: { type: Number, default: 100 },
    variant: { type: String, default: 'accent' },
    size: { type: String, default: 'md' },
});

const percent = computed(() => (props.max > 0 ? (props.value / props.max) * 100 : 0));

const colorVariant = computed(() => {
    switch (props.variant) {
        case 'accent': return 'bg-gradient-to-r from-accent via-tertiary to-danger';
        case 'tertiary': return 'bg-gradient-to-r from-tertiary to-accent';
        case 'success': return 'bg-success';
        default: return 'bg-accent';
    }
});
</script>

<template>
    <div class="w-full h-2 rounded-full bg-edge overflow-hidden border border-edge/40">
        <div
            class="h-full rounded-full transition-all duration-500 ease-out"
            :class="colorVariant"
            :style="{ width: `${percent.value}%` }"
        ></div>
    </div>
</template>
