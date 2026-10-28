<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    book: { type: Object, required: true },
    mode: { type: String, default: 'covers' },
    x: { type: Number, required: true },
    y: { type: Number, required: true },
});

const coverFailed = ref(false);
const coverUrl = computed(() => (props.book.cover_path ? `/covers/${props.book.id}` : null));
const style = computed(() => ({
    transform: `translate3d(${props.x}px, ${props.y}px, 0) translate(-50%, -50%) rotate(2deg)`,
}));
</script>

<template>
    <Teleport to="body">
        <div class="book-drag-ghost" :style="style" aria-hidden="true">
            <div v-if="mode === 'spines'" class="book-drag-ghost__spine">
                <span>{{ book.title }}</span>
            </div>

            <div v-else class="book-drag-ghost__cover">
                <img
                    v-if="coverUrl && !coverFailed"
                    :src="coverUrl"
                    alt=""
                    draggable="false"
                    @error="coverFailed = true"
                >
                <span v-else class="book-drag-ghost__fallback">{{ book.title }}</span>
            </div>
        </div>
    </Teleport>
</template>
