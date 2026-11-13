<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import type { SharedProps } from '@/types';

type ClassSummary = {
    id: number;
    name: string;
    subject: string;
    code: string;
    guru_name: string;
    students_count: number;
    items_count: number;
};

type ItemSummary = {
    id: number;
    title: string;
    type: string;
    type_label: string;
    class_name: string;
    author_name: string;
    due_at: string | null;
    created_at: string;
};

defineProps<{
    stats?: Record<string, number>;
    recentClasses?: ClassSummary[];
    recentItems?: ItemSummary[];
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

                <!-- Role Quick Stats -->
                <div v-if="stats" class="mb-8">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <template v-if="user?.role === 'admin'">
                            <div
                                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs"
                            >
                                <span class="text-xs font-semibold text-slate-500"
                                    >Total Pengguna</span
                                >
                                <p class="mt-2 text-3xl font-bold text-slate-900">
                                    {{ stats.total_users ?? 0 }}
                                </p>
                                <Link
                                    href="/admin/users"
                                    class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                                >
                                    Kelola Pengguna &rarr;
                                </Link>
                            </div>
                            <div
                                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs"
                            >
                                <span class="text-xs font-semibold text-slate-500"
                                    >Total Guru</span
                                >
                                <p class="mt-2 text-3xl font-bold text-slate-900">
                                    {{ stats.total_guru ?? 0 }}
                                </p>
                                <p class="mt-3 text-xs text-slate-400">
                                    Pendidik terdaftar
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs"
                            >
                                <span class="text-xs font-semibold text-slate-500"
                                    >Total Siswa</span
                                >
                                <p class="mt-2 text-3xl font-bold text-slate-900">
                                    {{ stats.total_siswa ?? 0 }}
                                </p>
                                <Link
                                    href="/admin/users"
                                    class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 hover:text-emerald-800"
                                >
                                    Impor Siswa CSV &rarr;
                                </Link>
                            </div>
                        </template>

                        <template v-else-if="user?.role === 'guru'">
                            <div
                                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs"
                            >
                                <span class="text-xs font-semibold text-slate-500"
                                    >Kelas yang Diampu</span
                                >
                                <p class="mt-2 text-3xl font-bold text-slate-900">
                                    {{ stats.total_classes ?? 0 }}
                                </p>
                                <Link
                                    href="/kelas"
                                    class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                                >
                                    Buka Daftar Kelas &rarr;
                                </Link>
                            </div>
                            <div
                                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs"
                            >
                                <span class="text-xs font-semibold text-slate-500"
                                    >Total Materi & Tugas</span
                                >
                                <p class="mt-2 text-3xl font-bold text-slate-900">
                                    {{ stats.total_items ?? 0 }}
                                </p>
                                <Link
                                    href="/pustaka"
                                    class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                                >
                                    Buka Pustaka &rarr;
                                </Link>
                            </div>
                            <div
                                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs"
                            >
                                <span class="text-xs font-semibold text-slate-500"
                                    >Menunggu Penilaian</span
                                >
                                <p class="mt-2 text-3xl font-bold text-amber-600">
                                    {{ stats.pending_grading ?? 0 }}
                                </p>
                                <Link
                                    href="/tugas-saya"
                                    class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-amber-700 hover:text-amber-800"
                                >
                                    Periksa Jawaban Siswa &rarr;
                                </Link>
                            </div>
                        </template>

                        <template v-else-if="user?.role === 'siswa'">
                            <div
                                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs"
                            >
                                <span class="text-xs font-semibold text-slate-500"
                                    >Kelas Diikuti</span
                                >
                                <p class="mt-2 text-3xl font-bold text-slate-900">
                                    {{ stats.enrolled_classes ?? 0 }}
                                </p>
                                <Link
                                    href="/kelas"
                                    class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                                >
                                    Lihat Kelas Saya &rarr;
                                </Link>
                            </div>
                            <div
                                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs"
                            >
                                <span class="text-xs font-semibold text-slate-500"
                                    >Tugas Belum Dikerjakan</span
                                >
                                <p class="mt-2 text-3xl font-bold text-amber-600">
                                    {{ stats.pending_tasks ?? 0 }}
                                </p>
                                <Link
                                    href="/tugas-saya"
                                    class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-amber-700 hover:text-amber-800"
                                >
                                    Kerjakan Tugas &rarr;
                                </Link>
                            </div>
                            <div
                                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs"
                            >
                                <span class="text-xs font-semibold text-slate-500"
                                    >Tugas Terkumpul</span
                                >
                                <p
                                    class="mt-2 text-3xl font-bold text-emerald-600"
                                >
                                    {{ stats.completed_tasks ?? 0 }}
                                </p>
                                <Link
                                    href="/nilai"
                                    class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 hover:text-emerald-800"
                                >
                                    Lihat Rekap Nilai &rarr;
                                </Link>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Recent Classes & Items -->
                <div
                    v-if="
                        (recentClasses && recentClasses.length > 0) ||
                        (recentItems && recentItems.length > 0)
                    "
                    class="grid grid-cols-1 gap-6 lg:grid-cols-2"
                >
                    <!-- Classes Column -->
                    <div
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs"
                    >
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-slate-800">
                                Kelas Aktif
                            </h3>
                            <Link
                                href="/kelas"
                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                            >
                                Lihat Semua
                            </Link>
                        </div>

                        <div
                            v-if="!recentClasses || recentClasses.length === 0"
                            class="py-6 text-center text-xs text-slate-400"
                        >
                            Belum ada kelas aktif.
                        </div>

                        <div
                            v-else
                            :class="
                                viewMode === 'grid'
                                    ? 'grid grid-cols-1 gap-3 sm:grid-cols-2'
                                    : 'space-y-2.5'
                            "
                        >
                            <Link
                                v-for="c in recentClasses"
                                :key="c.id"
                                :href="`/pustaka?class_id=${c.id}`"
                                class="block rounded-xl border border-slate-100 bg-slate-50/70 p-3.5 transition hover:border-indigo-200 hover:bg-indigo-50/40"
                            >
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <p class="font-bold text-slate-900">
                                        {{ c.name }} — {{ c.subject }}
                                    </p>
                                    <span
                                        class="rounded-md bg-indigo-100 px-2 py-0.5 font-mono text-[11px] font-bold text-indigo-700"
                                    >
                                        {{ c.code }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    Guru: {{ c.guru_name }} •
                                    {{ c.students_count }} Siswa •
                                    {{ c.items_count }} Materi/Tugas
                                </p>
                            </Link>
                        </div>
                    </div>

                    <!-- Recent Learning Items Column -->
                    <div
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs"
                    >
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-slate-800">
                                Aktivitas & Materi Terbaru
                            </h3>
                            <Link
                                href="/pustaka"
                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                            >
                                Buka Pustaka
                            </Link>
                        </div>

                        <div
                            v-if="!recentItems || recentItems.length === 0"
                            class="py-6 text-center text-xs text-slate-400"
                        >
                            Belum ada materi atau pengumuman.
                        </div>

                        <div v-else class="space-y-2.5">
                            <div
                                v-for="item in recentItems"
                                :key="item.id"
                                class="flex items-start justify-between gap-3 rounded-xl border border-slate-100 bg-slate-50/70 p-3.5"
                            >
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="rounded-md bg-white px-2 py-0.5 text-[10px] font-bold text-indigo-700 shadow-2xs"
                                        >
                                            {{ item.type_label }}
                                        </span>
                                        <span class="text-[11px] text-slate-400"
                                            >{{ item.class_name }}</span
                                        >
                                    </div>
                                    <p
                                        class="mt-1 text-xs font-semibold text-slate-900 sm:text-sm"
                                    >
                                        {{ item.title }}
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">
                                        Oleh {{ item.author_name }}
                                        <span v-if="item.due_at">
                                            • Tenggat: {{ item.due_at }}
                                        </span>
                                    </p>
                                </div>
                                <Link
                                    :href="
                                        item.type === 'assignment' ||
                                        item.type === 'quiz'
                                            ? '/tugas-saya'
                                            : '/pustaka'
                                    "
                                    class="shrink-0 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:border-indigo-300 hover:text-indigo-600"
                                >
                                    Buka
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fallback Empty Overview -->
                <div
                    v-else
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
                            href="/kelas"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700 sm:text-sm"
                        >
                            <FalconIcon name="plus" className="w-4 h-4" />
                            <span>{{
                                user?.role === 'siswa'
                                    ? 'Gabung Kelas dengan Kode'
                                    : 'Kelola Kelas'
                            }}</span>
                        </Link>
                    </div>
                </div>
            </template>
        </AuthenticatedLayout>
    </div>
</template>
