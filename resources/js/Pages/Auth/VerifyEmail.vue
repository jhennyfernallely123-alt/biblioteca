<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post('/email/verification-notification');
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Verificar correo" />

        <div class="mb-6">
            <h1 class="font-serif text-2xl text-gold">Verifica tu correo</h1>
            <p class="mt-1 text-sm text-mist">
                Te enviamos un enlace de verificación. Si no lo recibiste, podemos enviarte otro.
            </p>
        </div>

        <div v-if="verificationLinkSent" class="mb-4 text-sm font-medium text-success">
            Enviamos un nuevo enlace al correo con el que te registraste.
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <PrimaryButton
                class="w-full justify-center"
                :class="{ 'opacity-50': form.processing }"
                :disabled="form.processing"
            >
                Reenviar enlace
            </PrimaryButton>

            <Link
                href="/logout"
                method="post"
                as="button"
                class="block w-full text-center text-sm text-magic underline hover:text-gold focus:outline-none focus:ring-2 focus:ring-magic"
            >
                Cerrar sesión
            </Link>
        </form>
    </GuestLayout>
</template>
