<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post('/forgot-password');
};
</script>

<template>
    <GuestLayout>
        <Head title="Recuperar contraseña" />

        <div class="mb-6">
            <h1 class="font-serif text-2xl text-bosque">Recuperar contraseña</h1>
            <p class="mt-1 text-sm text-tinta/65">
                Escribe tu correo y te enviaremos un enlace para elegir una nueva contraseña.
            </p>
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-oliva">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="email" value="Correo electrónico" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <PrimaryButton
                class="w-full justify-center"
                :class="{ 'opacity-50': form.processing }"
                :disabled="form.processing"
            >
                Enviar enlace
            </PrimaryButton>
        </form>

        <div class="mt-6 border-t border-madera/20 pt-5 text-center text-sm text-tinta/70">
            <Link href="/login" class="font-medium text-oliva underline hover:text-bosque">
                Volver a iniciar sesión
            </Link>
        </div>
    </GuestLayout>
</template>
