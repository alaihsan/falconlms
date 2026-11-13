<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import FalconIcon from '@/components/ui/FalconIcon.vue';

defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const importForm = useForm<{
    file: File | null;
}>({
    file: null,
});

function onFileSelected(event: Event): void {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        importForm.file = target.files[0];
    }
}

function submitImport(): void {
    if (!importForm.file) return;
    importForm.post('/admin/users/import-csv', {
        onSuccess: () => {
            emit('close');
            importForm.reset();
        },
    });
}
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
        @click.self="emit('close')"
    >
        <div
            class="relative w-full max-w-lg rounded-2xl border border-slate-100 bg-white p-6 shadow-2xl"
        >
            <div
                class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3"
            >
                <div class="flex items-center gap-2">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
                    >
                        <FalconIcon name="upload" className="w-4 h-4" />
                    </div>
                    <h3 class="text-base font-bold text-slate-900">
                        Impor Siswa Massal via CSV
                    </h3>
                </div>
                <button
                    type="button"
                    class="p-1 text-slate-400 hover:text-slate-600"
                    @click="emit('close')"
                >
                    <FalconIcon name="x" className="w-5 h-5" />
                </button>
            </div>

            <div
                class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-3.5 text-xs leading-relaxed text-slate-600"
            >
                <p class="mb-1 font-semibold text-slate-800">
                    Ketentuan Format CSV:
                </p>
                <ul class="list-inside list-disc space-y-0.5 text-slate-500">
                    <li>
                        Kolom:
                        <code
                            class="rounded bg-indigo-50 px-1 py-0.5 font-mono text-indigo-700"
                            >nama, email, username, password</code
                        >
                    </li>
                    <li>
                        Siswa yang diimpor otomatis mendapat peran
                        <strong>Siswa</strong>.
                    </li>
                    <li>
                        Semua siswa yang diimpor
                        <strong>wajib mengganti kata sandi</strong> saat login
                        pertama.
                    </li>
                    <li>
                        Bila password dikosongkan, default kata sandi adalah
                        <code class="font-mono">siswa123</code>.
                    </li>
                </ul>
                <div class="mt-3">
                    <a
                        href="/admin/users/template-csv"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                    >
                        <FalconIcon name="download" className="w-3.5 h-3.5" />
                        <span>Unduh Contoh Berkas Template CSV</span>
                    </a>
                </div>
            </div>

            <form class="space-y-4" @submit.prevent="submitImport">
                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-700"
                        >Pilih Berkas CSV *</label
                    >
                    <input
                        type="file"
                        accept=".csv,text/csv,text/plain"
                        required
                        class="w-full cursor-pointer rounded-xl border border-slate-300 text-xs text-slate-600 file:mr-3 file:rounded-xl file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                        @change="onFileSelected"
                    />
                    <p
                        v-if="importForm.errors.file"
                        class="mt-1 text-xs text-rose-600"
                    >
                        {{ importForm.errors.file }}
                    </p>
                </div>

                <div
                    class="flex items-center justify-end gap-2 border-t border-slate-100 pt-3"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                        @click="emit('close')"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="importForm.processing || !importForm.file"
                        class="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700 disabled:opacity-60"
                    >
                        <span
                            v-if="importForm.processing"
                            class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"
                        ></span>
                        <span>{{
                            importForm.processing
                                ? 'Mengunggah & Memproses...'
                                : 'Mulai Impor Siswa'
                        }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

