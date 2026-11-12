<script setup>
const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    error: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div class="relative mb-4">
        <label
            v-if="label"
            class="block text-sm font-bold text-ink-muted transition-colors duration-200 peer-focus:text-accent"
            :for="props.label"
        >
            {{ props.label }}
        </label>
        <input
            type="text"
            :id="props.label"
            :class="[
                'block w-full rounded-control border border-edge bg-surface px-3 py-2 text-ink',
                'placeholder:text-ink-muted',
                'focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent',
                'peer-focus:border-accent peer-focus:ring-1 peer-focus:ring-accent',
                props.error ? 'border-danger focus:border-danger focus:ring-danger' : '',
            ]"
            :value="props.modelValue"
            :placeholder="props.placeholder"
            @input="emit('update:modelValue', $event.target.value)"
        />
        <p v-if="props.error" class="mt-1 text-xs text-danger">
            {{ props.error }}
        </p>
    </div>
</template>
