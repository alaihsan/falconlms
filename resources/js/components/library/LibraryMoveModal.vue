<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { move } from '@/actions/App/Http/Controllers/LibraryController';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import type { LearningItemView, LibraryOption } from '@/types';

const props = defineProps<{
    item: LearningItemView | null;
    folderOptions: LibraryOption[];
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const form = useForm<{ folder_id: number | null }>({
    folder_id: null,
});

watch(
    () => props.item,
    (item) => {
        if (item) {
            form.clearErrors();
            form.folder_id = item.folder_id;
        }
    },
);

function submit(): void {
    if (!props.item) {
        return;
    }

    form.submit(move(props.item.id), {
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
            class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"
        >
            <div
                class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3"
            >
                <div class="min-w-0">
                    <h3 class="text-base font-bold text-slate-900">
                        Pindahkan ke Folder
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

            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-700"
                        >Folder Tujuan</label
                    >
                    <select
                        v-model="form.folder_id"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:outline-hidden"
                    >
                        <option :value="null">
                            Pustaka Saya (tanpa folder)
                        </option>
                        <option
                            v-for="folder in folderOptions"
                            :key="folder.id"
                            :value="folder.id"
                        >
                            {{ folder.label }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors.folder_id"
                        class="mt-1 text-xs text-rose-600"
                    >
                        {{ form.errors.folder_id }}
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
                        {{ form.processing ? 'Memindahkan...' : 'Pindahkan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
