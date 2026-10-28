<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    id: {
        type: String,
        default: undefined,
    },
    autocomplete: {
        type: String,
        default: 'current-password',
    },
    required: {
        type: Boolean,
        default: false,
    },
    autofocus: {
        type: Boolean,
        default: false,
    },
});

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);
const visible = ref(false);

onMounted(() => {
    if (props.autofocus) {
        input.value?.focus();
    }
});
</script>

<template>
    <div class="relative">
        <input
            :id="id"
            ref="input"
            :type="visible ? 'text' : 'password'"
            v-model="model"
            :autocomplete="autocomplete"
            :required="required"
            :autofocus="autofocus"
            class="w-full rounded-control border-line bg-panel px-3 py-2 pe-12 text-moon shadow-soft placeholder:text-mist focus:border-magic focus:ring-magic"
        />

        <button
            type="button"
            class="absolute inset-y-0 end-0 flex w-11 items-center justify-center rounded-control text-mist hover:text-moon focus:outline-none focus:ring-2 focus:ring-magic"
            :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
            @click="visible = !visible"
        >
            <svg v-if="!visible" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                <circle cx="12" cy="12" r="2.7" />
            </svg>
            <svg v-else class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                <path d="m3 3 18 18" />
                <path d="M10.6 6.2A10.9 10.9 0 0 1 12 6c6 0 9.5 6 9.5 6a17.7 17.7 0 0 1-3.1 3.8" />
                <path d="M6.6 6.6C4 8.4 2.5 12 2.5 12s3.5 6 9.5 6a10.7 10.7 0 0 0 4.5-1" />
            </svg>
        </button>
    </div>
</template>
