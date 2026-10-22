<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import PasswordController from '@/actions/App/Http/Controllers/Auth/PasswordController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { home, logout } from '@/routes';

defineOptions({
    layout: {
        title: 'Ganti password',
        description: 'Minimal 8 karakter, berisi huruf dan angka',
    },
});

defineProps<{
    mustChangePassword: boolean;
    passwordRules: string;
}>();
</script>

<template>
    <Head title="Ganti password" />

    <p
        v-if="mustChangePassword"
        class="rounded-lg bg-accent px-4 py-3 text-sm text-accent-foreground"
    >
        Anda masuk dengan password sementara. Buat password baru sebelum
        melanjutkan.
    </p>

    <Form
        v-bind="PasswordController.update.form()"
        :reset-on-error="[
            'password',
            'password_confirmation',
            'current_password',
        ]"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div v-if="!mustChangePassword" class="grid gap-2">
            <Label for="current_password">Password saat ini</Label>
            <PasswordInput
                id="current_password"
                name="current_password"
                required
                autocomplete="current-password"
            />
            <InputError :message="errors.current_password" />
        </div>

        <div class="grid gap-2">
            <Label for="password">Password baru</Label>
            <PasswordInput
                id="password"
                name="password"
                required
                :autofocus="mustChangePassword"
                autocomplete="new-password"
                :passwordrules="passwordRules"
            />
            <InputError :message="errors.password" />
        </div>

        <div class="grid gap-2">
            <Label for="password_confirmation">Ulangi password baru</Label>
            <PasswordInput
                id="password_confirmation"
                name="password_confirmation"
                required
                autocomplete="new-password"
                :passwordrules="passwordRules"
            />
            <InputError :message="errors.password_confirmation" />
        </div>

        <Button
            type="submit"
            class="w-full"
            :disabled="processing"
            data-test="change-password-button"
        >
            <Spinner v-if="processing" />
            Simpan password
        </Button>

        <div class="text-center text-sm text-muted-foreground">
            <TextLink v-if="!mustChangePassword" :href="home()">
                Kembali ke Beranda
            </TextLink>
            <TextLink v-else :href="logout()" method="post" as="button">
                Keluar
            </TextLink>
        </div>
    </Form>
</template>
