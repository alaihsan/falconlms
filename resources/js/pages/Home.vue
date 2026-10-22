<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { FolderOpen, GraduationCap, Users } from '@lucide/vue';
import { computed } from 'vue';
import { home } from '@/routes';
import type { CreationContext } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Beranda',
                href: home(),
            },
        ],
    },
});

defineProps<{
    context: CreationContext;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Beranda" />

    <div class="flex flex-1 flex-col gap-6 p-6">
        <div>
            <h1 class="text-2xl font-semibold">
                Selamat datang, {{ user?.name }}
            </h1>
            <p class="text-sm text-muted-foreground">{{ user?.role_label }}</p>
        </div>

        <div
            class="flex flex-1 flex-col items-center justify-center gap-3 rounded-2xl border border-dashed p-10 text-center"
        >
            <template v-if="user?.role === 'teacher'">
                <FolderOpen class="size-12 text-primary/70" />
                <h2 class="text-lg font-medium">Pustaka Anda masih kosong</h2>
                <p class="max-w-md text-sm text-muted-foreground">
                    Klik
                    <span class="font-medium text-foreground">+ Baru</span>
                    untuk menambah folder, file, atau materi. Semua yang dibuat
                    dari Beranda tersimpan di
                    <span class="font-medium text-foreground">{{
                        context.space.name
                    }}</span
                    >.
                </p>
            </template>

            <template v-else-if="user?.role === 'admin'">
                <Users class="size-12 text-primary/70" />
                <h2 class="text-lg font-medium">Pengelolaan sekolah</h2>
                <p class="max-w-md text-sm text-muted-foreground">
                    Halaman kelola pengguna dan kelas hadir di tahap berikutnya.
                    Untuk sementara, akun dibuat lewat perintah
                    <code class="rounded bg-muted px-1"
                        >falcon:create-user</code
                    >
                    di server.
                </p>
            </template>

            <template v-else>
                <GraduationCap class="size-12 text-primary/70" />
                <h2 class="text-lg font-medium">Belum ada kelas</h2>
                <p class="max-w-md text-sm text-muted-foreground">
                    Kelas yang Anda ikuti akan tampil di sini.
                </p>
            </template>
        </div>
    </div>
</template>
