<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';

defineOptions({
    layout: {
        title: 'Konfirmasi password',
        description:
            'Bagian ini membutuhkan konfirmasi. Masukkan password Anda untuk melanjutkan.',
    },
});
</script>

<template>
    <Head title="Konfirmasi password" />

    <Form
        v-bind="store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
    >
        <div class="space-y-6">
            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    :autofocus="true"
                />
                <InputError :message="errors.password" />
            </div>

            <Button
                class="w-full"
                :disabled="processing"
                data-test="confirm-password-button"
            >
                <Spinner v-if="processing" />
                Konfirmasi
            </Button>
        </div>
    </Form>
</template>
