<script setup>
import { BrowserMultiFormatReader } from '@zxing/browser';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from '@/i18n';

const emit = defineEmits(['detected', 'error']);
const { t } = useI18n();
const video = ref(null);
const message = ref('');
let controls = null;

onMounted(async () => {
    const reader = new BrowserMultiFormatReader();

    try {
        controls = await reader.decodeFromVideoDevice(
            undefined,
            video.value,
            (result) => {
                if (result) {
                    emit('detected', result.getText());
                    controls?.stop();
                }
            }
        );
    } catch {
        message.value = t('ui.scanner_error');
        emit('error');
    }
});

onBeforeUnmount(() => controls?.stop());
</script>

<template>
    <div class="space-y-2">
        <video ref="video" class="aspect-[4/3] w-full rounded-md bg-night" muted playsinline />
        <p v-if="message" class="text-sm text-danger">{{ message }}</p>
    </div>
</template>
