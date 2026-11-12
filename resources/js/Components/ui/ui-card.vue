<script setup>
defineProps({
    hoverable: { type: Boolean, default: true },
});
</script>

<template>
    <div
        class="rounded-lg border border-edge bg-surface p-4 transition-all duration-200"
        :class="{
            'shadow-soft hover:shadow-card hover:border-accent hover:scale-[1.02]': hoverable,
        }"
    >
        <slot />
    </div>
</template>
