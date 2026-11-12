<script setup>
import { useId } from 'vue';

const model = defineModel({
    type: String,
    default: '',
});

defineProps({
    label: { type: String, required: true },
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: '' },
    error: { type: String, default: '' },
    autocomplete: { type: String, default: 'off' },
    maxlength: { type: Number, default: null },
    required: { type: Boolean, default: false },
});

const id = useId();
</script>

<template>
    <div class="relative">
        <input
            :id="id"
            v-model="model"
            :type="type"
            :placeholder="placeholder"
            :autocomplete="autocomplete"
            :maxlength="maxlength"
            :required="required"
            class="w-full rounded-card border bg-surface px-3 pb-2 pt-5 text-ink placeholder:text-ink-soft focus:outline-none"
            :class="error ? 'border-danger' : 'border-edge focus:border-2 focus:border-accent'"
        />
        <label
            :for="id"
            class="pointer-events-none absolute start-3 top-0 z-10 -translate-y-1/2 bg-surface px-1 text-xs font-medium transition-colors"
            :class="error ? 'text-danger' : 'text-accent'"
        >
            {{ label }}
        </label>
    </div>
</template>
