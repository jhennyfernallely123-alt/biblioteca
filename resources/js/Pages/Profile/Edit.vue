<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const { t } = useI18n();
</script>

<template>
    <Head :title="t('ui.profile_title')" />

    <AuthenticatedLayout>
        <h1 class="mb-6 text-3xl">{{ t('ui.profile_title') }}</h1>

        <div class="space-y-6">
            <div class="rounded-card border border-line bg-panel p-4">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </div>

            <div class="rounded-card border border-line bg-panel p-4">
                <UpdatePasswordForm class="max-w-xl" />
            </div>

            <div class="rounded-card border border-line bg-panel p-4">
                <DeleteUserForm class="max-w-xl" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
