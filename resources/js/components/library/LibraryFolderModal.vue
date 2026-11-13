<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { store, update } from '@/actions/App/Http/Controllers/FolderController';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import type { FolderView } from '@/types';

const props = defineProps<{
    open: boolean;
    parentId: number | null;
    folder: FolderView | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const form = useForm<{ name: string; parent_id: number | null }>({
    name: '',
    parent_id: null,
});

const isRenaming = computed(() => props.folder !== null);

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            form.clearErrors();
            form.name = props.folder?.name ?? '';
            form.parent_id = props.parentId;
        }
    },
);

function submit(): void {
    form.submit(props.folder ? update(props.folder.id) : store(), {
        preserveScroll: true,
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
            class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl"
        >
            <div
                class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3"
            >
                <h3 class="text-base font-bold text-slate-900">
                    {{ isRenaming ? 'Ganti Nama Folder' : 'Folder Baru' }}
                </h3>
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
                        >Nama Folder *</label
                    >
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        maxlength="120"
                        placeholder="misal: Semester Ganjil"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:outline-hidden"
                    />
                    <p
                        v-if="form.errors.name"
                        class="mt-1 text-xs text-rose-600"
                    >
                        {{ form.errors.name }}
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
