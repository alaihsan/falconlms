<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import type { SharedProps } from '@/types';

type GradeItem = {
    id: number;
    item_title: string;
    item_type: string;
    class_name: string;
    siswa_name: string;
    siswa_identifier: string;
    score: number | null;
    max_score: number;
    feedback: string | null;
    submitted_at: string | null;
    graded_at: string | null;
};

type GradeSummary = {
    total_submissions: number;
    graded_count: number;
    pending_count: number;
    average_score: number | null;
};

defineProps<{
    grades: GradeItem[];
    summary: GradeSummary;
}>();

const page = usePage<SharedProps>();
const user = page.props.auth.user;
</script>

<template>
    <div>
        <Head title="Rekap Nilai Siswa - Falcon LMS" />

        <AuthenticatedLayout title="Buku Nilai">
            <template #header>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                        Rekap Nilai
                    </h1>
                    <p class="mt-0.5 text-xs text-slate-500">
                        {{
                            user?.role === 'siswa'
                                ? 'Riwayat perolehan nilai dan umpan balik tugas dari guru.'
                                : 'Rekapitulasi skor hasil pengerjaan tugas dan kuis siswa.'
                        }}
                    </p>
                </div>
            </template>

            <template #default>
                <!-- Summary KPI Cards -->
                <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs">
                        <span class="text-xs font-semibold text-slate-500">Total Pengumpulan</span>
                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ summary.total_submissions }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs">
                        <span class="text-xs font-semibold text-slate-500">Sudah Dinilai</span>
                        <p class="mt-1 text-2xl font-bold text-emerald-600">{{ summary.graded_count }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs">
                        <span class="text-xs font-semibold text-slate-500">Menunggu Penilaian</span>
                        <p class="mt-1 text-2xl font-bold text-amber-600">{{ summary.pending_count }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs">
                        <span class="text-xs font-semibold text-slate-500">Rata-Rata Nilai</span>
                        <p class="mt-1 text-2xl font-bold text-indigo-600">
                            {{ summary.average_score !== null ? summary.average_score : '-' }}
                        </p>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="grades.length === 0" class="rounded-2xl border border-dashed border-slate-200 p-10 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                        <FalconIcon name="grades" className="w-6 h-6" />
                    </div>
                    <p class="font-semibold text-slate-800">Belum ada data nilai tercatat.</p>
                    <p class="mt-1 text-xs text-slate-400">Nilai akan otomatis muncul setelah tugas atau kuis dikumpulkan dan dinilai.</p>
                </div>

                <!-- Grades Table -->
                <div v-else class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600 sm:text-sm">
                            <thead class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold tracking-wider text-slate-500 uppercase">
                                <tr>
                                    <th v-if="user?.role !== 'siswa'" class="px-5 py-3">Siswa</th>
                                    <th class="px-5 py-3">Tugas / Kuis</th>
                                    <th class="px-5 py-3">Kelas</th>
                                    <th class="px-5 py-3">Dikumpulkan</th>
                                    <th class="px-5 py-3">Nilai</th>
                                    <th class="px-5 py-3">Catatan Guru</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="g in grades" :key="g.id" class="transition hover:bg-slate-50/80">
                                    <td v-if="user?.role !== 'siswa'" class="px-5 py-3.5">
                                        <p class="font-bold text-slate-900">{{ g.siswa_name }}</p>
                                        <p class="text-[11px] text-slate-400">{{ g.siswa_identifier }}</p>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-1.5">
                                            <span class="rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-700">
                                                {{ g.item_type }}
                                            </span>
                                            <span class="font-semibold text-slate-800">{{ g.item_title }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-600">
                                        {{ g.class_name }}
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-500 whitespace-nowrap">
                                        {{ g.submitted_at || '-' }}
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <span
                                            v-if="g.score !== null"
                                            :class="[
                                                'inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-bold',
                                                g.score >= 75 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'
                                            ]"
                                        >
                                            {{ g.score }} / {{ g.max_score }}
                                        </span>
                                        <span v-else class="text-xs text-amber-600 italic">
                                            Menunggu
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-xs text-slate-600">
                                        {{ g.feedback || '-' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </AuthenticatedLayout>
    </div>
</template>

