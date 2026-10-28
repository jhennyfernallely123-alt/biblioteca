<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const form = useForm({
    nickname: '',
});

const submit = () => {
    form.post('/complete-profile');
};

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <GuestLayout>
        <Head title="Elige tu apodo" />

        <div class="mb-6">
            <h1 class="font-serif text-2xl text-gold">Elige tu apodo</h1>
            <p class="mt-1 text-sm text-mist">
                Así te saludaremos cuando entres a tu biblioteca.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="nickname" value="Apodo" />

                <TextInput
                    id="nickname"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.nickname"
                    required
                    autofocus
                    maxlength="50"
                    autocomplete="nickname"
                />

                <p class="mt-1 text-xs text-mist">
                    Puedes cambiarlo más tarde en Ajustes → Perfil.
                </p>

                <InputError class="mt-2" :message="form.errors.nickname" />
            </div>

            <PrimaryButton
                class="w-full justify-center"
                :class="{ 'opacity-50': form.processing }"
                :disabled="form.processing"
            >
                Continuar
            </PrimaryButton>

            <button
                type="button"
                class="block w-full text-center text-sm text-magic underline hover:text-gold focus:outline-none focus:ring-2 focus:ring-magic"
                @click="logout"
            >
                Salir
            </button>
        </form>
    </GuestLayout>
</template>
