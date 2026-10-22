<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';

defineOptions({
    layout: {
        title: 'Masuk ke Falcon',
        description: 'Gunakan username atau NIS dan password Anda',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Masuk" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="username">Username atau NIS</Label>
                <Input
                    id="username"
                    type="text"
                    name="username"
                    required
                    v-focus
                    :tabindex="1"
                    autocomplete="username"
                    autocapitalize="none"
                    spellcheck="false"
                />
                <InputError :message="errors.username" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                />
                <InputError :message="errors.password" />
            </div>

            <Label for="remember" class="flex items-center space-x-3">
                <Checkbox id="remember" name="remember" :tabindex="3" />
                <span>Ingat saya di perangkat ini</span>
            </Label>

            <Button
                type="submit"
                class="mt-2 w-full"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                Masuk
            </Button>
        </div>

        <p class="text-center text-sm text-muted-foreground">
            Lupa password? Hubungi wali kelas atau admin sekolah.
        </p>
    </Form>
</template>
