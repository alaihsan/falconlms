<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { share } from '@/actions/App/Http/Controllers/LibraryController';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import type { LearningItemView, LibraryOption, TeacherOption } from '@/types';

const props = defineProps<{
    item: LearningItemView | null;
    classes: LibraryOption[];
    teachers: TeacherOption[];
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const form = useForm<{ class_ids: number[]; teacher_ids: number[] }>({
    class_ids: [],
    teacher_ids: [],
});

watch(
    () => props.item,
    (item) => {
        if (item) {
            form.clearErrors();
            form.class_ids = [...item.class_ids];
            form.teacher_ids = [...item.teacher_ids];
        }
    },
);

function submit(): void {
    if (!props.item) {
        return;
    }

    form.submit(share(props.item.id), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
}
</script>

<template>
    <div
        v-if="item"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
        @click.self="emit('close')"
    >
        <div
            class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl"
        >
            <div
                class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3"
            >
                <div class="min-w-0">
                    <h3 class="text-base font-bold text-slate-900">
                        Bagikan Konten
                    </h3>
                    <p class="mt-0.5 truncate text-xs text-slate-500">
                        {{ item.title }}
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

            <form class="space-y-5" @submit.prevent="submit">
                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-700"
                        >Kelas</label
                    >
                    <p class="mb-2 text-[11px] text-slate-400">
                        Siswa di kelas terpilih dapat melihat konten ini.
                    </p>
                    <p
                        v-if="classes.length === 0"
                        class="text-xs text-slate-400"
                    >
                        Anda belum memiliki kelas.
                    </p>
                    <div
                        v-else
                        class="max-h-40 space-y-1 overflow-y-auto rounded-xl border border-slate-200 p-2"
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
                    <p
                        v-if="form.errors.class_ids"
                        class="mt-1 text-xs text-rose-600"
                    >
                        {{ form.errors.class_ids }}
                    </p>
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-700"
                        >Guru Lain</label
                    >
                    <p class="mb-2 text-[11px] text-slate-400">
                        Guru terpilih dapat melihat, mengunduh, dan menyalin
                        konten ini ke Pustaka mereka.
                    </p>
                    <p
                        v-if="teachers.length === 0"
                        class="text-xs text-slate-400"
                    >
                        Belum ada guru lain yang terdaftar.
                    </p>
                    <div
                        v-else
                        class="max-h-40 space-y-1 overflow-y-auto rounded-xl border border-slate-200 p-2"
                    >
                        <label
                            v-for="teacher in teachers"
                            :key="teacher.id"
                            class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-slate-700 hover:bg-slate-50"
                        >
                            <input
                                v-model="form.teacher_ids"
                                type="checkbox"
                                :value="teacher.id"
                                class="rounded border-slate-300 text-indigo-600"
                            />
                            <span>{{ teacher.name }}</span>
                        </label>
                    </div>
                    <p
                        v-if="form.errors.teacher_ids"
                        class="mt-1 text-xs text-rose-600"
                    >
                        {{ form.errors.teacher_ids }}
                    </p>
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
                        {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
