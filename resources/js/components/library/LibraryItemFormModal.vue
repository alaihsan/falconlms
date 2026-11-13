<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { store } from '@/actions/App/Http/Controllers/LibraryController';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import type { LibraryOption, UploadRules } from '@/types';

const props = defineProps<{
    open: boolean;
    folderId: number | null;
    folderName: string | null;
    classes: LibraryOption[];
    upload: UploadRules;
}>();

const fileInputKey = ref(0);

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const form = useForm<{
    folder_id: number | null;
    class_ids: number[];
    type: string;
    title: string;
    topic: string;
    content: string;
    url: string;
    poll_options: string[];
    attachment: File | null;
    due_at: string;
    max_score: number;
    is_graded: boolean;
}>({
    folder_id: null,
    class_ids: [],
    type: 'material',
    title: '',
    topic: '',
    content: '',
    url: '',
    poll_options: ['', ''],
    attachment: null,
    due_at: '',
    max_score: 100,
    is_graded: false,
});

const isH5p = computed(() => form.type === 'h5p');
const acceptedTypes = computed(() =>
    isH5p.value
        ? '.h5p'
        : props.upload.extensions.map((extension) => `.${extension}`).join(','),
);
const acceptedTypesLabel = computed(() =>
    isH5p.value
        ? 'Paket H5P (.h5p)'
        : props.upload.extensions.join(', ').toUpperCase(),
);

watch(isH5p, () => {
    form.attachment = null;
    fileInputKey.value++;
});
const isTask = computed(
    () => form.type === 'assignment' || form.type === 'quiz',
);

const usesFile = computed(() => form.type !== 'link' && form.type !== 'poll');

watch(isTask, (value) => {
    form.is_graded = value;
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            form.reset();
            form.clearErrors();
            form.folder_id = props.folderId;
        }
    },
);

function onFileChange(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.attachment = file;

    if (file && form.title === '') {
        form.title = file.name.replace(/\.[^.]+$/, '');
    }
}

const pollOptionsError = computed(() => {
    const errors = form.errors as Record<string, string>;
    const key = Object.keys(errors).find((name) =>
        name.startsWith('poll_options'),
    );

    return key ? errors[key] : null;
});

function submit(): void {
    form.transform((data) => ({
        ...data,
        url: data.type === 'link' ? data.url : '',
        poll_options:
            data.type === 'poll'
                ? data.poll_options
                      .map((option) => option.trim())
                      .filter(Boolean)
                : [],
        attachment: usesFile.value ? data.attachment : null,
    })).submit(store(), {
        forceFormData: true,
        onSuccess: () => emit('close'),
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
            class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl"
        >
            <div
                class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3"
            >
                <div>
                    <h3 class="text-base font-bold text-slate-900">
                        Unggah / Buat Konten
                    </h3>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Disimpan di: {{ folderName ?? 'Pustaka Saya' }}
                    </p>
                </div>
                <button
                    type="button"
                    class="p-1 text-slate-400 hover:text-slate-600"
                    @click="emit('close')"
                >
                    <FalconIcon name="x" className="w-5 h-5" />
                </button>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-700"
                        >Tipe Konten *</label
                    >
                    <select
                        v-model="form.type"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:outline-hidden"
                    >
                        <option value="material">
                            Materi (Bacaan / Modul)
                        </option>
                        <option value="announcement">Pengumuman Kelas</option>
                        <option value="assignment">Tugas Mandiri</option>
                        <option value="quiz">Kuis</option>
                        <option value="link">Tautan (Link)</option>
                        <option value="poll">Polling</option>
                        <option value="h5p">H5P (Konten Interaktif)</option>
                    </select>
                    <p
                        v-if="form.errors.type"
                        class="mt-1 text-xs text-rose-600"
                    >
                        {{ form.errors.type }}
                    </p>
                </div>

                <div v-if="usesFile">
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-700"
                        >{{
                            isH5p ? 'Paket H5P *' : 'Berkas (Opsional)'
                        }}</label
                    >
                    <input
                        :key="fileInputKey"
                        type="file"
                        :required="isH5p"
                        :accept="acceptedTypes"
                        class="w-full rounded-xl border border-slate-300 bg-white text-sm text-slate-600 file:mr-3 file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-indigo-700"
                        @change="onFileChange"
                    />
                    <p class="mt-1 text-[11px] text-slate-400">
                        {{ acceptedTypesLabel }} — maksimal
                        {{ upload.max_megabytes }} MB.
                    </p>
                    <p
                        v-if="form.errors.attachment"
                        class="mt-1 text-xs text-rose-600"
                    >
                        {{ form.errors.attachment }}
                    </p>
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-700"
                        >Judul *</label
                    >
                    <input
                        v-model="form.title"
                        type="text"
                        required
                        placeholder="misal: Modul 1: Pemrograman HTML & CSS"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:outline-hidden"
                    />
                    <p
                        v-if="form.errors.title"
                        class="mt-1 text-xs text-rose-600"
                    >
                        {{ form.errors.title }}
                    </p>
                </div>

                <div v-if="form.type === 'link'">
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-700"
                        for="library-item-url"
                        >Alamat Tautan *</label
                    >
                    <input
                        id="library-item-url"
                        v-model="form.url"
                        type="url"
                        required
                        placeholder="https://..."
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:outline-hidden"
                    />
                    <p
                        v-if="form.errors.url"
                        class="mt-1 text-xs text-rose-600"
                    >
                        {{ form.errors.url }}
                    </p>
                </div>

                <fieldset v-if="form.type === 'poll'">
                    <legend
                        class="mb-1 block text-xs font-semibold text-slate-700"
                    >
                        Pilihan Jawaban *
                    </legend>
                    <div class="space-y-2">
                        <div
                            v-for="(_, optionIndex) in form.poll_options"
                            :key="optionIndex"
                            class="flex items-center gap-2"
                        >
                            <input
                                v-model="form.poll_options[optionIndex]"
                                type="text"
                                maxlength="200"
                                :aria-label="`Pilihan ${optionIndex + 1}`"
                                :placeholder="`Pilihan ${optionIndex + 1}`"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:outline-hidden"
                            />
                            <button
                                v-if="form.poll_options.length > 2"
                                type="button"
                                class="rounded-lg p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                                :aria-label="`Hapus pilihan ${optionIndex + 1}`"
                                @click="
                                    form.poll_options.splice(optionIndex, 1)
                                "
                            >
                                <FalconIcon name="x" className="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                    <button
                        v-if="form.poll_options.length < 10"
                        type="button"
                        class="mt-2 text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                        @click="form.poll_options.push('')"
                    >
                        + Tambah pilihan
                    </button>
                    <p
                        v-if="pollOptionsError"
                        class="mt-1 text-xs text-rose-600"
                    >
                        {{ pollOptionsError }}
                    </p>
                </fieldset>

                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-700"
                        >Topik / Bab</label
                    >
                    <input
                        v-model="form.topic"
                        type="text"
                        placeholder="misal: Bab 1: Dasar Web"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:outline-hidden"
                    />
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-700"
                        >Konten / Penjelasan</label
                    >
                    <textarea
                        v-model="form.content"
                        rows="3"
                        placeholder="Tuliskan materi, instruksi, atau soal tugas..."
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:outline-hidden"
                    ></textarea>
                </div>

                <div
                    v-if="isTask || form.is_graded"
                    class="grid grid-cols-2 gap-3"
                >
                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold text-slate-700"
                            >Tenggat Waktu</label
                        >
                        <input
                            v-model="form.due_at"
                            type="datetime-local"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:outline-hidden"
                        />
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold text-slate-700"
                            >Nilai Maksimal</label
                        >
                        <input
                            v-model.number="form.max_score"
                            type="number"
                            min="1"
                            max="1000"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:outline-hidden"
                        />
                    </div>
                </div>

                <label
                    class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 p-3"
                >
                    <input
                        v-model="form.is_graded"
                        type="checkbox"
                        class="mt-0.5 rounded border-slate-300 text-indigo-600"
                    />
                    <span>
                        <span class="block text-xs font-semibold text-slate-700"
                            >Masukkan ke buku nilai</span
                        >
                        <span class="block text-[11px] text-slate-400">
                            Konten ini menjadi satu kolom di buku nilai setiap
                            kelas tujuan.
                        </span>
                    </span>
                </label>

                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-700"
                        >Bagikan ke Kelas</label
                    >
                    <p
                        v-if="classes.length === 0"
                        class="text-xs text-slate-400"
                    >
                        Anda belum memiliki kelas. Konten tetap tersimpan di
                        Pustaka dan bisa dibagikan nanti.
                    </p>
                    <div
                        v-else
                        class="max-h-36 space-y-1 overflow-y-auto rounded-xl border border-slate-200 p-2"
                    >
                        <label
                            v-for="c in classes"
                            :key="c.id"
                            class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-slate-700 hover:bg-slate-50"
                        >
                            <input
                                v-model="form.class_ids"
                                type="checkbox"
                                :value="c.id"
                                class="rounded border-slate-300 text-indigo-600"
                            />
                            <span>{{ c.label }}</span>
                        </label>
                    </div>
                    <p class="mt-1 text-[11px] text-slate-400">
                        Kosongkan untuk menyimpan sebagai draf pribadi.
                    </p>
                </div>

                <div
                    v-if="form.progress"
                    class="h-1.5 overflow-hidden rounded-full bg-slate-100"
                >
                    <div
                        class="h-full bg-indigo-600 transition-all"
                        :style="{ width: `${form.progress.percentage ?? 0}%` }"
                    ></div>
                </div>

                <div
                    class="flex items-center justify-end gap-2 border-t border-slate-100 pt-3"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                        @click="emit('close')"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Konten' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
