<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { FileText, Folder } from '@lucide/vue';
import { computed, watch } from 'vue';
import LibraryImportController from '@/actions/App/Http/Controllers/Courses/LibraryImportController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { SpaceRef, Topic } from '@/types';

type LibraryTree = {
    folders: { id: number; name: string; parentId: number | null }[];
    items: {
        id: number;
        title: string;
        type: string;
        folderId: number | null;
    }[];
};

type Row =
    | { kind: 'folder'; id: number; name: string; depth: number }
    | { kind: 'item'; id: number; name: string; depth: number };

const props = defineProps<{
    open: boolean;
    course: SpaceRef;
    topicId: number | null;
}>();

const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const page = usePage<{ library?: LibraryTree; topics?: Topic[] }>();
const library = computed(() => page.props.library ?? null);
const topics = computed(() => page.props.topics ?? []);

const form = useForm<{
    folders: number[];
    items: number[];
    topic_id: number | null;
}>({ folders: [], items: [], topic_id: null });

watch(
    () => props.open,
    (open) => {
        if (open) {
            form.reset();
            form.topic_id = props.topicId;
            // The library is only loaded when the dialog opens.
            router.reload({ only: ['library'] });
        }
    },
);

// Flatten the folder tree into indented rows, folders before files.
const rows = computed<Row[]>(() => {
    const tree = library.value;

    if (!tree) {
        return [];
    }

    const result: Row[] = [];
    const walk = (parentId: number | null, depth: number) => {
        for (const folder of tree.folders.filter(
            (f) => f.parentId === parentId,
        )) {
            result.push({
                kind: 'folder',
                id: folder.id,
                name: folder.name,
                depth,
            });
            walk(folder.id, depth + 1);
        }

        for (const item of tree.items.filter((i) => i.folderId === parentId)) {
            result.push({ kind: 'item', id: item.id, name: item.title, depth });
        }
    };

    walk(null, 0);

    return result;
});

function toggle(row: Row, checked: boolean): void {
    const list = row.kind === 'folder' ? form.folders : form.items;
    const index = list.indexOf(row.id);

    if (checked && index === -1) {
        list.push(row.id);
    } else if (!checked && index !== -1) {
        list.splice(index, 1);
    }
}

function isChecked(row: Row): boolean {
    return (row.kind === 'folder' ? form.folders : form.items).includes(row.id);
}

function submit(): void {
    form.post(LibraryImportController.store.url(props.course.id), {
        onSuccess: () => emit('update:open', false),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <form class="grid gap-4" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>Dari Pustaka Saya</DialogTitle>
                    <DialogDescription>
                        Materi disalin ke
                        <span class="font-medium text-foreground">{{
                            course.name
                        }}</span>
                        sebagai draf. Folder menjadi topik dengan nama yang
                        sama.
                    </DialogDescription>
                </DialogHeader>

                <div class="max-h-80 overflow-y-auto rounded-lg border p-2">
                    <div
                        v-if="!library"
                        class="flex justify-center p-6 text-muted-foreground"
                    >
                        <Spinner />
                    </div>
                    <p
                        v-else-if="rows.length === 0"
                        class="p-6 text-center text-sm text-muted-foreground"
                    >
                        Pustaka Saya masih kosong.
                    </p>
                    <label
                        v-for="row in rows"
                        :key="`${row.kind}-${row.id}`"
                        class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-accent"
                        :style="{ paddingLeft: `${row.depth * 1.25 + 0.5}rem` }"
                    >
                        <input
                            type="checkbox"
                            class="size-4 accent-primary"
                            :checked="isChecked(row)"
                            @change="
                                toggle(
                                    row,
                                    ($event.target as HTMLInputElement).checked,
                                )
                            "
                        />
                        <Folder
                            v-if="row.kind === 'folder'"
                            class="size-4 text-primary"
                        />
                        <FileText v-else class="size-4 text-muted-foreground" />
                        <span class="truncate">{{ row.name }}</span>
                    </label>
                </div>

                <div class="grid gap-2">
                    <Label for="import-topic"
                        >Topik untuk file yang dipilih</Label
                    >
                    <select
                        id="import-topic"
                        v-model="form.topic_id"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                    >
                        <option :value="null">Tanpa topik</option>
                        <option
                            v-for="topic in topics"
                            :key="topic.id"
                            :value="topic.id"
                        >
                            {{ topic.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.topic_id" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="ghost"
                        @click="emit('update:open', false)"
                    >
                        Batal
                    </Button>
                    <Button
                        type="submit"
                        :disabled="
                            form.processing ||
                            (form.folders.length === 0 &&
                                form.items.length === 0)
                        "
                    >
                        <Spinner v-if="form.processing" />
                        Salin ke kelas
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
