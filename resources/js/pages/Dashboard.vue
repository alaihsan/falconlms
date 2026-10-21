<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import type { SharedProps } from '@/types';

defineProps<{
    stats?: Record<string, number>;
}>();

const page = usePage<SharedProps>();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <div>
        <Head title="Beranda - Falcon LMS" />

        <AuthenticatedLayout title="Beranda">
            <template #header>
                <div class="flex items-center gap-3">
                    <h1
                        class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl"
                    >
                        Beranda
                    </h1>
                    <span
                        class="rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700"
                    >
                        {{ user?.role_label }}
                    </span>
                </div>
            </template>

            <template #default="{ viewMode }">
                <!-- Welcome Banner -->
                <div
                    class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-700 to-slate-900 p-6 text-white shadow-lg shadow-indigo-600/20 sm:p-8"
                >
                    <div
                        class="pointer-events-none absolute -right-10 -bottom-10 h-48 w-48 rounded-full bg-white/5"
                    ></div>
                    <div class="relative z-10 max-w-2xl">
                        <span
                            class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-white/10 px-2.5 py-1 text-xs font-medium text-white/90 backdrop-blur-xs"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-emerald-400"
                            ></span>
                            Sistem LMS Intranet Sekolah Siap Pakai
                        </span>
                        <h2
                            class="text-2xl font-extrabold tracking-tight sm:text-3xl"
                        >
                            Selamat Datang, {{ user?.name }}!
                        </h2>
                        <p class="mt-2 text-sm leading-relaxed text-indigo-100">
                            Aplikasi Falcon LMS berjalan sepenuhnya di jaringan
                            lokal sekolah tanpa memerlukan akses internet
                            publik. Semua materi, kelas, dan tugas tersimpan
                            aman di server lokal.
                        </p>
                    </div>
                </div>

                <!-- Admin Quick Stats -->
                <div v-if="user?.role === 'admin' && stats" class="mb-8">
                    <h3
                        class="mb-4 text-sm font-bold tracking-wider text-slate-500 uppercase"
                    >
                        Ringkasan Pengguna Sekolah
                    </h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div
                            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs transition hover:shadow-md"
                        >
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs font-semibold text-slate-500"
                                    >Total Pengguna</span
                                >
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
                                >
                                    <FalconIcon
                                        name="users"
                                        className="w-5 h-5"
                                    />
                                </div>
                            </div>
                            <p class="mt-3 text-3xl font-bold text-slate-900">
                                {{ stats.total_users ?? 0 }}
                            </p>
                            <Link
                                href="/admin/users"
                                class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                            >
                                <span>Kelola Pengguna</span>
                                <span>&rarr;</span>
                            </Link>
                        </div>

                        <div
                            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs transition hover:shadow-md"
                        >
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs font-semibold text-slate-500"
                                    >Total Guru</span
                                >
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
                                >
                                    <FalconIcon
                                        name="classes"
                                        className="w-5 h-5"
                                    />
                                </div>
                            </div>
                            <p class="mt-3 text-3xl font-bold text-slate-900">
                                {{ stats.total_guru ?? 0 }}
                            </p>
                            <p class="mt-3 text-xs text-slate-400">
                                Pendidik terdaftar
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs transition hover:shadow-md"
                        >
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs font-semibold text-slate-500"
                                    >Total Siswa</span
                                >
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                                >
                                    <FalconIcon
                                        name="user"
                                        className="w-5 h-5"
                                    />
                                </div>
                            </div>
                            <p class="mt-3 text-3xl font-bold text-slate-900">
                                {{ stats.total_siswa ?? 0 }}
                            </p>
                            <Link
                                href="/admin/users"
                                class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 hover:text-emerald-800"
                            >
                                <span>Impor Siswa CSV</span>
                                <span>&rarr;</span>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Teacher / Student Overview -->
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-2xs sm:p-8"
                >
                    <div
                        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600"
                    >
                        <FalconIcon name="classes" className="w-8 h-8" />
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">
                        {{
                            user?.role === 'siswa'
                                ? 'Belum Ada Kelas yang Diikuti'
                                : 'Mulai Pembelajaran Anda'
                        }}
                    </h3>
                    <p
                        class="mx-auto mt-1 max-w-md text-xs text-slate-500 sm:text-sm"
                    >
                        {{
                            user?.role === 'siswa'
                                ? 'Minta kode gabung kelas kepada guru Anda untuk mulai melihat materi dan mengerjakan tugas.'
                                : 'Gunakan tombol "+ Baru" atau menu Kelas untuk membuat kelas baru dan mengundang siswa.'
                        }}
                    </p>

                    <div
                        class="mt-6 flex flex-wrap items-center justify-center gap-3"
                    >
                        <Link
                            v-if="user?.role === 'guru'"
                            href="/kelas"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700 sm:text-sm"
                        >
                            <FalconIcon name="plus" className="w-4 h-4" />
                            <span>Buat Kelas Pertama</span>
                        </Link>

                        <Link
                            v-if="user?.role === 'admin'"
                            href="/admin/users"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700 sm:text-sm"
                        >
                            <FalconIcon name="users" className="w-4 h-4" />
                            <span>Kelola Data Siswa & Guru</span>
                        </Link>
                    </div>
                </div>
            </template>
        </AuthenticatedLayout>
    </div>
</template>
