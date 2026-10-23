<script setup lang="ts">
import { FileText } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import DestinationPicker from '@/components/new/DestinationPicker.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { uploadFiles, usePendingUpload } from '@/composables/useNewActions';
import { formatBytes } from '@/lib/format';
import type { Destination } from '@/types';

const SHOWN_FILES = 5;

const pending = usePendingUpload();
const destination = ref<Destination | null>(null);

watch(pending, (upload) => {
    destination.value = upload?.destination ?? null;
});

const files = computed(() => pending.value?.files ?? []);
const totalSize = computed(() =>
    files.value.reduce((sum, file) => sum + file.size, 0),
);
const isFolder = computed(() => (pending.value?.paths.length ?? 0) > 0);

const description = computed(() => {
    if (destination.value?.space.kind === 'course') {
        return 'Setiap file menjadi draf materi, jadi Anda bisa menambahkan keterangan sebelum memposting.';
    }

    return isFolder.value
        ? 'Sub-folder di dalamnya ikut dibuat.'
        : 'File langsung tersimpan dan bisa disalin ke kelas kapan saja.';
});

function cancel(): void {
    pending.value = null;
}

function upload(): void {
    if (!pending.value || !destination.value) {
        return;
    }

    const { files: picked, paths } = pending.value;
    const target = destination.value;

    pending.value = null;
    uploadFiles(
        target.space,
        picked,
        { folderId: target.folder?.id, topicId: target.topic?.id },
        target.space.kind === 'course' ? [] : paths,
    );
}
</script>

<template>
    <Dialog
        :open="pending !== null"
        @update:open="(open: boolean) => !open && cancel()"
    >
        <DialogContent class="sm:max-w-md" data-test="upload-dialog">
            <DialogHeader>
                <DialogTitle>
                    Unggah {{ files.length }} file
                    <span class="font-normal text-muted-foreground"
                        >({{ formatBytes(totalSize) }})</span
                    >
                </DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>

            <ul class="grid gap-1 text-sm">
                <li
                    v-for="(file, index) in files.slice(0, SHOWN_FILES)"
                    :key="index"
                    class="flex items-center gap-2"
                >
                    <FileText class="size-4 shrink-0 text-muted-foreground" />
                    <span class="truncate">{{
                        pending?.paths[index] || file.name
                    }}</span>
                </li>
                <li
                    v-if="files.length > SHOWN_FILES"
                    class="pl-6 text-muted-foreground"
                >
                    dan {{ files.length - SHOWN_FILES }} file lainnya
                </li>
            </ul>

            <DestinationPicker v-if="destination" v-model="destination" />

            <DialogFooter>
                <Button type="button" variant="ghost" @click="cancel">
                    Batal
                </Button>
                <Button
                    type="button"
                    data-test="upload-confirm"
                    v-focus
                    @click="upload"
                >
                    Unggah ke sini
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
