<script setup lang="ts">
import FalconIcon from '@/components/ui/FalconIcon.vue';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-slate-50 px-4 py-8 sm:px-6 lg:px-8"
    >
        <!-- Brand Header -->
        <div class="mb-6 text-center sm:mx-auto sm:w-full sm:max-w-md">
            <div
                class="mb-3 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/30"
            >
                <FalconIcon name="falcon" className="w-8 h-8" />
            </div>
            <h1
                class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl"
            >
                Falcon LMS
            </h1>
            <p class="mt-1 text-xs font-medium text-slate-500 sm:text-sm">
                Sistem Manajemen Pembelajaran Intranet Sekolah
            </p>
            <div
                class="mt-2 inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700"
            >
                <span
                    class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"
                ></span>
                Jaringan Lokal Sekolah (Intranet)
            </div>
        </div>

        <!-- Content Card -->
        <div class="w-full sm:mx-auto sm:max-w-md">
            <div
                class="border border-slate-100 bg-white px-6 py-8 shadow-xl shadow-slate-200/50 sm:rounded-2xl sm:px-10"
            >
                <slot />
            </div>

            <p class="mt-6 text-center text-xs text-slate-400">
                &copy; {{ new Date().getFullYear() }} Falcon LMS. Tidak
                memerlukan koneksi internet.
            </p>
        </div>
    </div>
</template>
