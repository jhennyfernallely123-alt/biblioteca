<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SocialLoginButtons from '@/Components/SocialLoginButtons.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    nickname: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const page = usePage();
const socialProviders = computed(() => page.props.socialProviders ?? { google: false, facebook: false });

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Crear cuenta" />

        <div class="mb-6">
            <h1 class="font-serif text-2xl text-accent">Crear cuenta</h1>
            <p class="mt-1 text-sm text-ink-muted">
                Empieza tu biblioteca personal con tres estanterías listas.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="name" value="Nombre" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="nickname" value="Apodo" />

                <TextInput
                    id="nickname"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.nickname"
                    required
                    maxlength="50"
                    autocomplete="nickname"
                />

                <p class="mt-1 text-xs text-ink-muted">
                    Así te saludaremos al entrar.
                </p>

                <InputError class="mt-2" :message="form.errors.nickname" />
            </div>

            <div>
                <InputLabel for="email" value="Correo electrónico" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Contraseña" />

                <PasswordInput
                    id="password"
                    class="mt-1"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel
                    for="password_confirmation"
                    value="Confirmar contraseña"
                />

                <PasswordInput
                    id="password_confirmation"
                    class="mt-1"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <PrimaryButton
                class="w-full justify-center"
                :class="{ 'opacity-50': form.processing }"
                :disabled="form.processing"
            >
                Crear cuenta
            </PrimaryButton>
        </form>

        <div class="my-5 flex items-center gap-3 text-[11px] uppercase tracking-[0.16em] text-ink-muted">
            <span class="h-px flex-1 bg-edge" />
            o continúa con
            <span class="h-px flex-1 bg-edge" />
        </div>

        <SocialLoginButtons :providers="socialProviders" />

        <div class="mt-6 border-t border-edge pt-5 text-center text-sm text-ink-muted">
            ¿Ya tienes cuenta?
            <Link href="/login" class="font-medium text-secondary underline hover:text-accent">
                Inicia sesión
            </Link>
        </div>
    </GuestLayout>
</template>
