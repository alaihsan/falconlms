<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import UserImportController from '@/actions/App/Http/Controllers/Admin/UserImportController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineProps<{ roles: { value: string; label: string }[] }>();

const form = useForm<{ file: File | null; default_role: string }>({
    file: null,
    default_role: 'student',
});

function submit(): void {
    form.post(UserImportController.store.url(), { forceFormData: true });
}
</script>

<template>
    <Head title="Impor pengguna" />

    <form
        class="mx-auto grid w-full max-w-2xl gap-6 p-6"
        @submit.prevent="submit"
    >
        <div>
            <h1 class="text-2xl font-semibold">Impor pengguna dari CSV</h1>
            <p class="text-sm text-muted-foreground">
                Setiap akun mendapat password sementara yang wajib diganti saat
                login pertama.
            </p>
        </div>

        <div class="grid gap-2 rounded-xl bg-muted p-4 text-sm">
            <p class="font-medium">Format file</p>
            <p>
                Dari Excel:
                <span class="font-medium">Simpan sebagai › CSV</span>. Baris
                pertama berisi nama kolom <code>username</code> (atau
                <code>nis</code>), <code>nama</code>, dan opsional
                <code>peran</code> (<code>siswa</code>, <code>guru</code>,
                <code>admin</code>). Pemisah koma maupun titik koma bisa
                dipakai.
            </p>
            <pre
                class="overflow-x-auto rounded-md bg-background p-3 font-mono text-xs"
            >
nis;nama;peran
0012345678;Budi Santoso;siswa
0012345679;Siti Aminah;siswa
guru.joko;Joko Widodo;guru</pre>
        </div>

        <div class="grid gap-2">
            <Label for="file">File CSV</Label>
            <input
                id="file"
                type="file"
                accept=".csv,text/csv"
                required
                class="text-sm file:mr-3 file:rounded-md file:border file:bg-background file:px-3 file:py-1.5 file:text-sm"
                @change="
                    form.file =
                        ($event.target as HTMLInputElement).files?.[0] ?? null
                "
            />
            <InputError :message="form.errors.file" />
        </div>

        <div class="grid gap-2">
            <Label for="default_role">Peran jika kolom peran kosong</Label>
            <select
                id="default_role"
                v-model="form.default_role"
                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
            >
                <option
                    v-for="role in roles"
                    :key="role.value"
                    :value="role.value"
                >
                    {{ role.label }}
                </option>
            </select>
        </div>

        <div class="flex justify-end">
            <Button type="submit" :disabled="form.processing || !form.file">
                <Spinner v-if="form.processing" /> Impor
            </Button>
        </div>
    </form>
</template>
