<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import FalconIcon from '@/components/ui/FalconIcon.vue';
</script>

<template>
    <div>
        <Head title="Selamat Datang - Falcon LMS" />

        <div
            class="flex min-h-screen flex-col items-center justify-center bg-slate-50 p-6 text-slate-800"
        >
            <div
                class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-lg"
            >
                <div
                    class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-600/30"
                >
                    <FalconIcon name="falcon" className="w-7 h-7" />
                </div>

                <h1 class="text-2xl font-extrabold text-slate-900">
                    Falcon LMS Intranet
                </h1>
                <p class="mt-2 text-sm leading-relaxed text-slate-500">
                    Platform pembelajaran manajemen kelas, materi, tugas, dan
                    penilaian terpadu untuk jaringan intranet sekolah.
                </p>

                <div class="mt-6 flex items-center justify-center gap-3">
                    <Link
                        href="/login"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                    >
                        <span>Masuk ke Aplikasi</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
