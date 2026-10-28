<script setup>
import { computed } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SocialLoginButtons from '@/Components/SocialLoginButtons.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const socialProviders = computed(() => page.props.socialProviders ?? { google: false, facebook: false });

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar sesión" />

        <div class="mb-6">
            <h1 class="font-serif text-2xl text-gold">Iniciar sesión</h1>
            <p class="mt-1 text-sm text-mist">Entra para ver tus estanterías.</p>
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-success">
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

            <div>
                <InputLabel for="password" value="Contraseña" />

                <PasswordInput
                    id="password"
                    class="mt-1"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <label class="flex min-h-11 items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-mist">Recordarme</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    href="/forgot-password"
                    class="text-sm text-magic underline hover:text-gold focus:outline-none focus:ring-2 focus:ring-magic"
                >
                    ¿Olvidaste tu contraseña?
                </Link>
            </div>

            <PrimaryButton
                class="w-full justify-center"
                :class="{ 'opacity-50': form.processing }"
                :disabled="form.processing"
            >
                Entrar
            </PrimaryButton>
        </form>

        <div class="my-5 flex items-center gap-3 text-[11px] uppercase tracking-[0.16em] text-mist">
            <span class="h-px flex-1 bg-line" />
            o continúa con
            <span class="h-px flex-1 bg-line" />
        </div>

        <SocialLoginButtons :providers="socialProviders" />

        <div class="mt-6 border-t border-line pt-5 text-center text-sm text-mist">
            ¿Aún no tienes cuenta?
            <Link href="/register" class="font-medium text-magic underline hover:text-gold">
                Crear cuenta
            </Link>
        </div>
    </GuestLayout>
</template>
