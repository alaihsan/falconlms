<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import type { SharedProps } from '@/types';

type TaskSubmission = {
    id: number;
    siswa_name: string;
    siswa_username: string;
    answer_text: string | null;
    score: number | null;
    feedback: string | null;
    submitted_at: string | null;
    graded_at: string | null;
};

type MySubmission = {
    id: number;
    answer_text: string | null;
    score: number | null;
    feedback: string | null;
    submitted_at: string | null;
    graded_at: string | null;
};

type TaskItem = {
    id: number;
    title: string;
    type: string;
    type_label: string;
    topic: string | null;
    content: string | null;
    class_id: number | null;
    class_name: string;
    author_name: string;
    due_at: string | null;
    max_score: number;
    my_submission: MySubmission | null;
    submissions: TaskSubmission[];
};

defineProps<{
    tasks: TaskItem[];
    classes: Array<{ id: number; label: string }>;
}>();

const page = usePage<SharedProps>();
const user = page.props.auth.user;

const submittingTask = ref<TaskItem | null>(null);
const submitForm = useForm({
    answer_text: '',
});

const gradingSubmission = ref<TaskSubmission | null>(null);
const gradeForm = useForm({
    score: 100,
    feedback: '',
});

function openSubmitModal(task: TaskItem): void {
    submittingTask.value = task;
    submitForm.reset();
    submitForm.answer_text = task.my_submission?.answer_text || '';
}

function sendSubmission(): void {
    if (!submittingTask.value) return;
    submitForm.post(`/tugas-saya/${submittingTask.value.id}/submit`, {
        onSuccess: () => {
            submittingTask.value = null;
            submitForm.reset();
        },
    });
}

function openGradeModal(sub: TaskSubmission): void {
    gradingSubmission.value = sub;
    gradeForm.reset();
    gradeForm.score = sub.score !== null ? sub.score : 100;
    gradeForm.feedback = sub.feedback || '';
}

function sendGrade(): void {
    if (!gradingSubmission.value) return;
    gradeForm.post(`/tugas-saya/submissions/${gradingSubmission.value.id}/grade`, {
        onSuccess: () => {
            gradingSubmission.value = null;
            gradeForm.reset();
        },
    });
}
</script>

<template>
    <div>
        <Head title="Tugas Saya - Falcon LMS" />

        <AuthenticatedLayout title="Tugas & Penilaian">
            <template #header>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                        Tugas Saya
                    </h1>
                    <p class="mt-0.5 text-xs text-slate-500">
                        {{
                            user?.role === 'siswa'
                                ? 'Daftar tugas dan kuis yang harus dikerjakan beserta status nilai.'
                                : 'Daftar penugasan kelas dan riwayat jawaban siswa yang perlu diperiksa.'
                        }}
                    </p>
                </div>
            </template>

            <template #default>
                <!-- Empty State -->
                <div v-if="tasks.length === 0" class="rounded-2xl border border-dashed border-slate-200 p-10 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                        <FalconIcon name="tasks" className="w-6 h-6" />
                    </div>
                    <p class="font-semibold text-slate-800">Tidak ada tugas aktif.</p>
                    <p class="mt-1 text-xs text-slate-400">Semua tugas kelas akan tampil di sini saat guru menerbitkan penugasan.</p>
                </div>

                <!-- Task List -->
                <div v-else class="space-y-4">
                    <div
                        v-for="t in tasks"
                        :key="t.id"
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs"
                    >
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700">
                                        {{ t.type_label }}
                                    </span>
                                    <span class="text-xs font-semibold text-slate-600">
                                        {{ t.class_name }}
                                    </span>
                                    <span v-if="t.topic" class="text-xs text-slate-400">
                                        • {{ t.topic }}
                                    </span>
                                </div>
                                <h2 class="mt-1.5 text-base font-bold text-slate-900">
                                    {{ t.title }}
                                </h2>
                                <p class="mt-1 text-xs leading-relaxed text-slate-600 whitespace-pre-line">
                                    {{ t.content }}
                                </p>
                            </div>

                            <div class="flex shrink-0 flex-col items-end gap-1 text-xs text-slate-500">
                                <span v-if="t.due_at" class="font-semibold text-amber-600">
                                    Tenggat: {{ t.due_at }}
                                </span>
                                <span>Nilai Maks: {{ t.max_score }}</span>
                            </div>
                        </div>

                        <!-- Siswa View: Submission Status -->
                        <div v-if="user?.role === 'siswa'" class="mt-4 border-t border-slate-100 pt-3.5">
                            <div v-if="t.my_submission" class="flex flex-col gap-2 rounded-xl bg-slate-50 p-3.5 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div class="flex items-center gap-2 text-xs">
                                        <span class="inline-flex items-center gap-1 font-semibold text-emerald-600">
                                            <FalconIcon name="check" className="w-3.5 h-3.5" />
                                            Sudah Dikumpulkan
                                        </span>
                                        <span class="text-slate-400">
                                            ({{ t.my_submission.submitted_at }})
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-600">
                                        <strong>Jawaban:</strong> {{ t.my_submission.answer_text }}
                                    </p>
                                    <p v-if="t.my_submission.feedback" class="mt-1 text-xs text-indigo-600">
                                        <strong>Catatan Guru:</strong> {{ t.my_submission.feedback }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-3">
                                    <div v-if="t.my_submission.score !== null" class="text-right">
                                        <span class="text-[10px] text-slate-400 uppercase font-semibold">Nilai</span>
                                        <p class="text-lg font-extrabold text-indigo-600">
                                            {{ t.my_submission.score }} <span class="text-xs font-normal text-slate-400">/ {{ t.max_score }}</span>
                                        </p>
                                    </div>
                                    <span v-else class="rounded-lg bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                                        Menunggu Penilaian
                                    </span>

                                    <button
                                        v-if="t.my_submission.score === null"
                                        type="button"
                                        class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        @click="openSubmitModal(t)"
                                    >
                                        Ubah Jawaban
                                    </button>
                                </div>
                            </div>

                            <div v-else class="flex items-center justify-between">
                                <span class="text-xs font-medium text-rose-600">
                                    Belum dikerjakan
                                </span>
                                <button
                                    type="button"
                                    class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700"
                                    @click="openSubmitModal(t)"
                                >
                                    Kerjakan Sekarang
                                </button>
                            </div>
                        </div>

                        <!-- Guru/Admin View: Submissions to Grade -->
                        <div v-else class="mt-4 border-t border-slate-100 pt-3.5">
                            <h3 class="mb-2 text-xs font-bold tracking-wider text-slate-500 uppercase">
                                Jawaban Siswa ({{ t.submissions.length }})
                            </h3>

                            <div v-if="t.submissions.length === 0" class="py-2 text-xs text-slate-400">
                                Belum ada siswa yang mengumpulkan jawaban.
                            </div>

                            <div v-else class="divide-y divide-slate-100 rounded-xl border border-slate-100">
                                <div
                                    v-for="sub in t.submissions"
                                    :key="sub.id"
                                    class="flex flex-col gap-2 p-3 text-xs sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <div class="flex items-center gap-2 font-semibold text-slate-800">
                                            <span>{{ sub.siswa_name }}</span>
                                            <span class="text-[11px] font-normal text-slate-400">
                                                ({{ sub.siswa_username }})
                                            </span>
                                            <span class="text-[11px] font-normal text-slate-400">
                                                • {{ sub.submitted_at }}
                                            </span>
                                        </div>
                                        <p class="mt-1 text-slate-600">
                                            {{ sub.answer_text }}
                                        </p>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-3">
                                        <span v-if="sub.score !== null" class="font-bold text-indigo-600">
                                            Nilai: {{ sub.score }} / {{ t.max_score }}
                                        </span>
                                        <button
                                            type="button"
                                            class="rounded-lg bg-indigo-50 px-3 py-1.5 font-semibold text-indigo-700 hover:bg-indigo-100"
                                            @click="openGradeModal(sub)"
                                        >
                                            {{ sub.score !== null ? 'Ubah Nilai' : 'Beri Nilai' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Answer Modal (Siswa) -->
                <div
                    v-if="submittingTask"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
                    @click.self="submittingTask = null"
                >
                    <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                        <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                            <h3 class="text-base font-bold text-slate-900">
                                Kumpulkan Jawaban: {{ submittingTask.title }}
                            </h3>
                            <button
                                type="button"
                                class="p-1 text-slate-400 hover:text-slate-600"
                                @click="submittingTask = null"
                            >
                                <FalconIcon name="x" className="w-5 h-5" />
                            </button>
                        </div>

                        <form class="space-y-4" @submit.prevent="sendSubmission">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Tulis Jawaban Anda *</label>
                                <textarea
                                    v-model="submitForm.answer_text"
                                    rows="6"
                                    required
                                    placeholder="Ketik jawaban tugas atau uraian kuis Anda di sini..."
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:outline-hidden"
                                ></textarea>
                                <p v-if="submitForm.errors.answer_text" class="mt-1 text-xs text-rose-600">
                                    {{ submitForm.errors.answer_text }}
                                </p>
                            </div>

                            <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-3">
                                <button
                                    type="button"
                                    class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                                    @click="submittingTask = null"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    :disabled="submitForm.processing"
                                    class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                                >
                                    {{ submitForm.processing ? 'Mengirim...' : 'Kirim Jawaban' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Grading Modal (Guru/Admin) -->
                <div
                    v-if="gradingSubmission"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
                    @click.self="gradingSubmission = null"
                >
                    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                        <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                            <h3 class="text-base font-bold text-slate-900">
                                Beri Nilai Siswa
                            </h3>
                            <button
                                type="button"
                                class="p-1 text-slate-400 hover:text-slate-600"
                                @click="gradingSubmission = null"
                            >
                                <FalconIcon name="x" className="w-5 h-5" />
                            </button>
                        </div>

                        <form class="space-y-4" @submit.prevent="sendGrade">
                            <div class="rounded-xl bg-slate-50 p-3 text-xs text-slate-600">
                                <p class="font-bold text-slate-800">{{ gradingSubmission.siswa_name }}</p>
                                <p class="mt-1">{{ gradingSubmission.answer_text }}</p>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Skor / Nilai (0 - 100) *</label>
                                <input
                                    v-model.number="gradeForm.score"
                                    type="number"
                                    min="0"
                                    max="1000"
                                    required
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-indigo-700 focus:border-indigo-600 focus:outline-hidden"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Catatan / Umpan Balik Guru</label>
                                <textarea
                                    v-model="gradeForm.feedback"
                                    rows="3"
                                    placeholder="misal: Penjelasan sangat rapi, pemahaman konsep tepat."
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:outline-hidden"
                                ></textarea>
                            </div>

                            <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-3">
                                <button
                                    type="button"
                                    class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                                    @click="gradingSubmission = null"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    :disabled="gradeForm.processing"
                                    class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                                >
                                    {{ gradeForm.processing ? 'Menyimpan...' : 'Simpan Nilai' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </template>
        </AuthenticatedLayout>
    </div>
</template>

