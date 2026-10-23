import { router, usePage } from '@inertiajs/vue3';
import { ref, shallowRef } from 'vue';
import { toast } from 'vue-sonner';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import UserImportController from '@/actions/App/Http/Controllers/Admin/UserImportController';
import ItemController from '@/actions/App/Http/Controllers/Content/ItemController';
import UploadController from '@/actions/App/Http/Controllers/Content/UploadController';
import type {
    CreationContext,
    Destination,
    NewMenuItem,
    SpaceRef,
} from '@/types';

export type NewDialog =
    | 'folder'
    | 'link'
    | 'course'
    | 'join_course'
    | 'topic'
    | 'announcement'
    | 'add_members'
    | 'from_library'
    | 'user';

// Shared by every "+ Baru" button and by NewActions, which renders the dialogs.
const openDialog = ref<NewDialog | null>(null);
const fileInput = shallowRef<HTMLInputElement | null>(null);
const folderInput = shallowRef<HTMLInputElement | null>(null);

// Files waiting for the user to confirm where they are saved.
export type PendingUpload = {
    files: File[];
    paths: string[];
    destination: Destination;
};
const pendingUpload = shallowRef<PendingUpload | null>(null);

/**
 * Ask where to save picked or dropped files before uploading them. The
 * place the user is at is chosen already; the dialog lets them change it.
 */
export function confirmUpload(
    files: File[],
    destination: Destination,
    paths: string[] = [],
): void {
    if (files.length > 0) {
        pendingUpload.value = { files, paths, destination };
    }
}

export function usePendingUpload() {
    return pendingUpload;
}

// PHP accepts at most 20 files per request (max_file_uploads), so bigger
// uploads are sent in batches that also stay under the size limit.
const BATCH_FILES = 20;

type UploadBatch = { files: File[]; paths: string[]; bytes: number };

function toBatches(
    files: File[],
    paths: string[],
    maxBytes: number,
): UploadBatch[] {
    const batches: UploadBatch[] = [];
    let current: UploadBatch = { files: [], paths: [], bytes: 0 };

    files.forEach((file, index) => {
        if (
            current.files.length > 0 &&
            (current.files.length >= BATCH_FILES ||
                current.bytes + file.size > maxBytes)
        ) {
            batches.push(current);
            current = { files: [], paths: [], bytes: 0 };
        }

        current.files.push(file);
        current.paths.push(paths[index] ?? '');
        current.bytes += file.size;
    });

    batches.push(current);

    return batches;
}

/**
 * Upload files into a space: into a folder of a library, or as draft
 * materials into a topic of a class. Used by "+ Baru" and by drag & drop.
 */
export function uploadFiles(
    space: SpaceRef,
    files: File[],
    target: { folderId?: number | null; topicId?: number | null },
    paths: string[] = [],
): void {
    if (files.length === 0) {
        return;
    }

    const { maxBytes, maxFiles } = usePage().props.uploads;

    if (files.length > maxFiles) {
        toast.error(`Maksimal ${maxFiles} file sekali unggah.`);

        return;
    }

    const tooBig = files.find((file) => file.size > maxBytes);

    if (tooBig) {
        toast.error(
            `${tooBig.name} lebih dari ${Math.floor(maxBytes / 1024 / 1024)} MB.`,
        );

        return;
    }

    const batches = toBatches(files, paths, maxBytes);
    const totalBytes = Math.max(
        batches.reduce((sum, batch) => sum + batch.bytes, 0),
        1,
    );
    const id = toast.loading(`Mengunggah ${files.length} file…`);
    let sentBytes = 0;

    const send = (index: number): void => {
        const batch = batches[index];
        let succeeded = false;

        router.post(
            UploadController.store.url(space.id),
            {
                files: batch.files,
                paths: paths.length > 0 ? batch.paths : [],
                ...(space.kind === 'course'
                    ? { topic_id: target.topicId ?? null }
                    : { folder_id: target.folderId ?? null }),
            },
            {
                forceFormData: true,
                preserveScroll: true,
                onProgress: (progress) => {
                    const done =
                        sentBytes +
                        (batch.bytes * (progress?.percentage ?? 0)) / 100;

                    toast.loading(
                        `Mengunggah… ${Math.round((done / totalBytes) * 100)}%`,
                        { id },
                    );
                },
                onSuccess: () => {
                    succeeded = true;
                },
                onError: (errors) => {
                    toast.error(
                        Object.values(errors)[0] ?? 'File tidak bisa diunggah.',
                    );
                },
                onFinish: () => {
                    sentBytes += batch.bytes;

                    if (succeeded && index + 1 < batches.length) {
                        send(index + 1);
                    } else {
                        toast.dismiss(id);
                    }
                },
            },
        );
    };

    send(0);
}

export function useNewActions() {
    const page = usePage<{ context?: CreationContext }>();

    function registerInputs(
        files: HTMLInputElement | null,
        folders: HTMLInputElement | null,
    ): void {
        fileInput.value = files;
        folderInput.value = folders;
    }

    function run(item: NewMenuItem): void {
        const context = page.props.context;

        switch (item) {
            case 'material':
            case 'assignment':
                if (context?.space) {
                    router.visit(
                        ItemController.create.url(context.space.id, {
                            query: {
                                type: item,
                                folder: context.folder?.id,
                                topic: context.topic?.id,
                            },
                        }),
                    );
                }

                return;
            case 'upload':
                fileInput.value?.click();

                return;
            case 'upload_folder':
                folderInput.value?.click();

                return;
            case 'import_users':
                router.visit(UserImportController.create.url());

                return;
            case 'user':
                // Creating accounts happens on the users page, behind password confirmation.
                if (page.component === 'admin/users/Index') {
                    openDialog.value = 'user';
                } else {
                    router.visit(
                        UserController.index.url({ query: { baru: 1 } }),
                    );
                }

                return;
            default:
                openDialog.value = item;
        }
    }

    function close(): void {
        openDialog.value = null;
    }

    return { openDialog, run, close, registerInputs };
}
