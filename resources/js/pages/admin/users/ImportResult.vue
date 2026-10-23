<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Printer } from '@lucide/vue';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import { Button } from '@/components/ui/button';

function print(): void {
    window.print();
}

defineProps<{
    created: {
        username: string;
        name: string;
        role: string;
        password: string;
    }[];
    errors: { line: number; message: string }[];
}>();
</script>

<template>
    <Head title="Hasil impor" />

    <div
        class="mx-auto grid w-full max-w-5xl gap-6 p-6 print:max-w-none print:p-0"
    >
        <div
            class="flex flex-wrap items-center justify-between gap-3 print:hidden"
        >
            <div>
                <h1 class="text-2xl font-semibold">Hasil impor</h1>
                <p class="text-sm text-muted-foreground">
                    {{ created.length }} akun dibuat<template
                        v-if="errors.length"
                        >, {{ errors.length }} baris dilewati</template
                    >. Password sementara hanya tampil di halaman ini. Cetak
                    sekarang sebelum meninggalkan halaman.
                </p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" as-child
                    ><Link :href="UserController.index.url()"
                        >Selesai</Link
                    ></Button
                >
                <Button v-if="created.length > 0" @click="print"
                    ><Printer /> Cetak slip</Button
                >
            </div>
        </div>

        <section
            v-if="errors.length > 0"
            class="grid gap-2 rounded-xl border border-destructive/40 p-4 print:hidden"
        >
            <h2 class="font-medium text-destructive">Baris yang dilewati</h2>
            <ul class="grid gap-1 text-sm">
                <li
                    v-for="error in errors"
                    :key="error.line"
                    data-test="import-error"
                >
                    <span class="font-mono text-muted-foreground"
                        >Baris {{ error.line }}:</span
                    >
                    {{ error.message }}
                </li>
            </ul>
        </section>

        <!-- One slip per account, to cut out and hand to each student. -->
        <section
            v-if="created.length > 0"
            class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 print:grid-cols-3 print:gap-0"
        >
            <div
                v-for="account in created"
                :key="account.username"
                class="break-inside-avoid rounded-xl border border-dashed p-4 print:rounded-none"
                data-test="slip"
            >
                <p class="text-xs text-muted-foreground">
                    Falcon LMS · {{ account.role }}
                </p>
                <p class="font-medium">{{ account.name }}</p>
                <p class="mt-2 text-xs text-muted-foreground">Username</p>
                <p class="font-mono">{{ account.username }}</p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Password sementara
                </p>
                <p class="font-mono text-lg tracking-wider">
                    {{ account.password }}
                </p>
            </div>
        </section>
    </div>
</template>
