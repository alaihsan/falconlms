<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    EllipsisVertical,
    Folder,
    FolderOpen,
    LayoutGrid,
    List,
    Pencil,
    Trash2,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import FolderController from '@/actions/App/Http/Controllers/Content/FolderController';
import ItemController from '@/actions/App/Http/Controllers/Content/ItemController';
import DropZone from '@/components/content/DropZone.vue';
import ItemIcon from '@/components/content/ItemIcon.vue';
import FormDialog from '@/components/new/FormDialog.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { confirmUpload } from '@/composables/useNewActions';
import { formatDate } from '@/lib/format';
import { show as itemShow } from '@/routes/items';
import { show as library } from '@/routes/library';
import type { CreationContext, FolderSummary, ItemSummary } from '@/types';

const props = defineProps<{
    context: CreationContext;
    folder: (FolderSummary & { parentId: number | null }) | null;
    folders: FolderSummary[];
    items: ItemSummary[];
}>();

// Grid or list, remembered on this device like in Google Drive.
const view = ref<'grid' | 'list'>('grid');

try {
    view.value =
        localStorage.getItem('falcon.libraryView') === 'list' ? 'list' : 'grid';
} catch {
    // Storage can be unavailable (private mode); the grid is fine.
}

watch(view, (value) => {
    try {
        localStorage.setItem('falcon.libraryView', value);
    } catch {
        // Ignore.
    }
});

const renaming = ref<FolderSummary | null>(null);

function onDrop(files: File[]): void {
    if (props.context.space) {
        confirmUpload(files, {
            space: props.context.space,
            folder: props.folder
                ? { id: props.folder.id, name: props.folder.name }
                : null,
            topic: null,
        });
    }
}

function deleteFolder(folder: FolderSummary): void {
    if (confirm(`Hapus folder "${folder.name}" beserta isinya?`)) {
        router.delete(FolderController.destroy.url(folder.id));
    }
}

function deleteItem(item: ItemSummary): void {
    if (confirm(`Hapus "${item.title}"?`)) {
        router.delete(ItemController.destroy.url(item.id), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head :title="folder?.name ?? 'Pustaka Saya'" />

    <DropZone class="flex flex-1 flex-col" @files="onDrop">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between gap-4">
                <h1 class="truncate text-2xl font-semibold">
                    {{ folder?.name ?? 'Pustaka Saya' }}
                </h1>
                <div class="flex rounded-full border p-0.5">
                    <button
                        type="button"
                        :class="[
                            'rounded-full p-1.5',
                            view === 'grid'
                                ? 'bg-accent text-accent-foreground'
                                : 'text-muted-foreground',
                        ]"
                        aria-label="Tampilan grid"
                        @click="view = 'grid'"
                    >
                        <LayoutGrid class="size-4" />
                    </button>
                    <button
                        type="button"
                        :class="[
                            'rounded-full p-1.5',
                            view === 'list'
                                ? 'bg-accent text-accent-foreground'
                                : 'text-muted-foreground',
                        ]"
                        aria-label="Tampilan daftar"
                        @click="view = 'list'"
                    >
                        <List class="size-4" />
                    </button>
                </div>
            </div>

            <div
                v-if="folders.length === 0 && items.length === 0"
                class="flex flex-1 flex-col items-center justify-center gap-3 rounded-2xl border border-dashed p-10 text-center"
            >
                <FolderOpen class="size-12 text-primary/70" />
                <h2 class="text-lg font-medium">Folder ini masih kosong</h2>
                <p class="max-w-md text-sm text-muted-foreground">
                    Klik
                    <span class="font-medium text-foreground">+ Baru</span>
                    atau seret file ke sini. Semua tersimpan di
                    <span class="font-medium text-foreground">{{
                        folder?.name ?? 'Pustaka Saya'
                    }}</span
                    >.
                </p>
            </div>

            <section v-if="folders.length > 0" class="grid gap-2">
                <h2 class="text-sm font-medium text-muted-foreground">
                    Folder
                </h2>
                <div
                    :class="
                        view === 'grid'
                            ? 'grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'
                            : 'grid divide-y rounded-xl border'
                    "
                >
                    <div
                        v-for="child in folders"
                        :key="child.id"
                        :class="[
                            'flex items-center gap-3 px-3 hover:bg-accent',
                            view === 'grid' ? 'rounded-xl border py-3' : 'py-2',
                        ]"
                        data-test="folder"
                    >
                        <Link
                            :href="library(child.id)"
                            class="flex min-w-0 flex-1 items-center gap-3"
                        >
                            <Folder
                                class="size-5 shrink-0 fill-primary/20 text-primary"
                            />
                            <span class="truncate text-sm font-medium">{{
                                child.name
                            }}</span>
                        </Link>
                        <DropdownMenu>
                            <DropdownMenuTrigger
                                class="rounded-full p-1 text-muted-foreground hover:bg-background"
                                :aria-label="`Menu ${child.name}`"
                            >
                                <EllipsisVertical class="size-4" />
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem @select="renaming = child">
                                    <Pencil /> Ganti nama
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    variant="destructive"
                                    @select="deleteFolder(child)"
                                >
                                    <Trash2 /> Hapus
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </section>

            <section v-if="items.length > 0" class="grid gap-2">
                <h2 class="text-sm font-medium text-muted-foreground">
                    File dan materi
                </h2>
                <div
                    :class="
                        view === 'grid'
                            ? 'grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'
                            : 'grid divide-y rounded-xl border'
                    "
                >
                    <div
                        v-for="item in items"
                        :key="item.id"
                        :class="
                            view === 'grid'
                                ? 'overflow-hidden rounded-xl border hover:shadow-md'
                                : 'flex items-center gap-3 px-3 py-2 hover:bg-accent'
                        "
                        data-test="item"
                    >
                        <Link
                            v-if="view === 'grid'"
                            :href="itemShow(item.id)"
                            class="flex h-32 items-center justify-center bg-muted"
                        >
                            <img
                                v-if="item.file?.preview === 'image'"
                                :src="item.file.url"
                                :alt="item.title"
                                class="h-full w-full object-cover"
                                loading="lazy"
                            />
                            <ItemIcon
                                v-else
                                :type="item.type"
                                :preview="item.file?.preview"
                            />
                        </Link>
                        <div
                            :class="[
                                'flex min-w-0 flex-1 items-center gap-3',
                                view === 'grid' ? 'px-3 py-2' : '',
                            ]"
                        >
                            <ItemIcon
                                v-if="view === 'list'"
                                :type="item.type"
                                :preview="item.file?.preview"
                            />
                            <Link
                                :href="itemShow(item.id)"
                                class="min-w-0 flex-1"
                            >
                                <p class="truncate text-sm font-medium">
                                    {{ item.title }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatDate(item.createdAt) }}
                                </p>
                            </Link>
                            <DropdownMenu>
                                <DropdownMenuTrigger
                                    class="rounded-full p-1 text-muted-foreground hover:bg-accent"
                                    :aria-label="`Menu ${item.title}`"
                                >
                                    <EllipsisVertical class="size-4" />
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem as-child>
                                        <Link
                                            :href="
                                                ItemController.edit.url(item.id)
                                            "
                                        >
                                            <Pencil /> Ubah
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        variant="destructive"
                                        @select="deleteItem(item)"
                                    >
                                        <Trash2 /> Hapus
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </DropZone>

    <FormDialog
        :open="renaming !== null"
        title="Ganti nama folder"
        :fields="[{ name: 'name', label: 'Nama folder', required: true }]"
        :initial="{ name: renaming?.name ?? '' }"
        :action="renaming ? FolderController.update.url(renaming.id) : ''"
        :extra="{ _method: 'patch' }"
        submit-label="Simpan"
        @update:open="(open) => !open && (renaming = null)"
    />
</template>
