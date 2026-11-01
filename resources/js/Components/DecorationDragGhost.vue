<script setup>
import { computed } from 'vue';
import DecorationArt from './DecorationArt.vue';

const props = defineProps({
    decoration: { type: Object, required: true },
    x: { type: Number, required: true },
    y: { type: Number, required: true },
});

const style = computed(() => ({
    transform: `translate3d(${props.x}px, ${props.y}px, 0) translate(-50%, -70%)`,
}));
</script>

<template>
    <Teleport to="body">
        <div class="book-drag-ghost" :style="style" aria-hidden="true">
            <div class="h-20 w-16 drop-shadow-lg">
                <DecorationArt :kind="decoration.kind" :color="decoration.color" class="h-full w-full" />
            </div>
        </div>
    </Teleport>
</template>
