<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { destroy as destroyFolder } from '@/actions/App/Http/Controllers/FolderController';
import {
    copy,
    destroy,
    index,
    toggleGraded,
} from '@/actions/App/Http/Controllers/LibraryController';
import LibraryFolderModal from '@/components/library/LibraryFolderModal.vue';
import LibraryH5pModal from '@/components/library/LibraryH5pModal.vue';
import LibraryItemActions from '@/components/library/LibraryItemActions.vue';
import LibraryItemFormModal from '@/components/library/LibraryItemFormModal.vue';
import LibraryMoveModal from '@/components/library/LibraryMoveModal.vue';
import LibraryPollModal from '@/components/library/LibraryPollModal.vue';
import LibraryPreviewModal from '@/components/library/LibraryPreviewModal.vue';
import LibraryShareModal from '@/components/library/LibraryShareModal.vue';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type {
    FolderView,
    LearningItemView,
    LibraryOption,
    LibraryView,
    SharedProps,
    TeacherOption,
    UploadRules,
} from '@/types';

const props = defineProps<{
    items: LearningItemView[];
    folders: FolderView[];
    currentFolder: FolderView | null;
    breadcrumbs: FolderView[];
    folderOptions: LibraryOption[];
    classes: LibraryOption[];
    teachers: TeacherOption[];
    view: LibraryView;
    filters: {
        search: string;
        type: string;
        class_id: number | null;
    };
    upload: UploadRules;
}>();

const role = usePage<SharedProps>().props.auth.user?.role;

const searchQuery = ref(props.filters.search || '');
const selectedType = ref(props.filters.type || '');
const selectedClass = ref(
    props.filters.class_id ? String(props.filters.class_id) : '',
);

const showCreateModal = ref(false);
const showFolderModal = ref(false);
const folderBeingRenamed = ref<FolderView | null>(null);
const itemBeingShared = ref<LearningItemView | null>(null);
const itemBeingMoved = ref<LearningItemView | null>(null);
const itemBeingPreviewed = ref<LearningItemView | null>(null);
const itemBeingPlayed = ref<LearningItemView | null>(null);
const pollItemId = ref<number | null>(null);
const itemBeingPolled = computed(
    () => props.items.find((item) => item.id === pollItemId.value) ?? null,
);

const isOwnLibrary = computed(() => props.view === 'mine');
const isFiltering = computed(
    () =>
        props.filters.search !== '' ||
        props.filters.type !== '' ||
        props.filters.class_id !== null,
);

const tabs = computed<{ view: LibraryView; label: string }[]>(() => {
    if (role === 'admin') {
        return [
            { view: 'mine', label: 'Pustaka Saya' },
            { view: 'all', label: 'Semua Konten' },
        ];
    }

    if (role === 'guru') {
        return [
            { view: 'mine', label: 'Pustaka Saya' },
            { view: 'shared', label: 'Dibagikan kepada Saya' },
        ];
    }

    return [];
});

const emptyMessage = computed(() => {
    if (isFiltering.value) {
        return 'Tidak ada konten yang cocok dengan pencarian atau filter.';
    }

    if (props.view === 'shared') {
        return 'Belum ada konten yang dibagikan guru lain kepada Anda.';
    }

    if (props.view === 'class') {
        return 'Belum ada materi yang dibagikan ke kelas Anda.';
    }

    return props.currentFolder
        ? 'Folder ini masih kosong.'
        : 'Pustaka Anda masih kosong.';
});

function folderUrl(folderId: number | null): string {
    return index.url({ query: { folder: folderId ?? undefined } });
}

function viewUrl(view: LibraryView): string {
    return index.url({ query: { view: view === 'mine' ? undefined : view } });
}

function applyFilter(): void {
    router.get(
        index.url(),
        {
            view:
                props.view === 'mine' || props.view === 'class'
                    ? undefined
                    : props.view,
            folder: props.currentFolder?.id,
            search: searchQuery.value || undefined,
            type: selectedType.value || undefined,
            class_id: selectedClass.value
                ? Number(selectedClass.value)
                : undefined,
        },
        { preserveState: true, replace: true },
    );
}

function openCreateFolder(): void {
    folderBeingRenamed.value = null;
    showFolderModal.value = true;
}

function openRenameFolder(folder: FolderView): void {
    folderBeingRenamed.value = folder;
    showFolderModal.value = true;
}

function deleteFolder(folder: FolderView): void {
    if (confirm(`Hapus folder "${folder.name}"? Folder harus kosong.`)) {
        router.delete(destroyFolder.url(folder.id), { preserveScroll: true });
    }
}

function copyItem(item: LearningItemView): void {
    router.post(copy.url(item.id), {}, { preserveScroll: true });
}

function toggleItemGraded(item: LearningItemView): void {
    router.patch(toggleGraded.url(item.id), {}, { preserveScroll: true });
}

function deleteItem(item: LearningItemView): void {
    if (confirm(`Pindahkan "${item.title}" ke Tempat Sampah?`)) {
        router.delete(destroy.url(item.id), { preserveScroll: true });
    }
}
</script>

<template>
    <div>
        <Head title="Pustaka Materi & Konten - Falcon LMS" />

        <AuthenticatedLayout title="Pustaka">
            <template #header>
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h1
                            class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl"
                        >
                            Pustaka
                        </h1>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Simpan materi dalam folder, lalu bagikan ke kelas
                            atau guru lain.
                        </p>
                    </div>

                    <div v-if="isOwnLibrary" class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                            @click="openCreateFolder"
                        >
                            <FalconIcon name="folder" className="w-4 h-4" />
                            <span>Folder Baru</span>
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700"
                            @click="showCreateModal = true"
                        >
                            <FalconIcon name="plus" className="w-4 h-4" />
                            <span>Unggah / Buat Konten</span>
                        </button>
                    </div>
                </div>
            </template>

            <template #default="{ viewMode }">
                <!-- View Tabs -->
                <div
                    v-if="tabs.length > 0"
                    class="mb-4 flex items-center gap-1 border-b border-slate-200"
                >
                    <Link
                        v-for="tab in tabs"
                        :key="tab.view"
                        :href="viewUrl(tab.view)"
                        class="-mb-px border-b-2 px-3 py-2 text-xs font-semibold transition sm:text-sm"
                        :class="
                            tab.view === view
                                ? 'border-indigo-600 text-indigo-700'
                                : 'border-transparent text-slate-500 hover:text-slate-800'
                        "
                    >
                        {{ tab.label }}
                    </Link>
                </div>

                <!-- Breadcrumbs -->
                <nav
                    v-if="isOwnLibrary && breadcrumbs.length > 0"
                    class="mb-4 flex flex-wrap items-center gap-1 text-sm"
                    aria-label="Lokasi folder"
                >
                    <Link
                        :href="folderUrl(null)"
                        class="rounded-lg px-2 py-1 font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-900"
                    >
                        Pustaka Saya
                    </Link>
                    <template
                        v-for="(crumb, crumbIndex) in breadcrumbs"
                        :key="crumb.id"
                    >
                        <span class="text-slate-300">/</span>
                        <span
                            v-if="crumbIndex === breadcrumbs.length - 1"
                            class="px-2 py-1 font-semibold text-slate-900"
                        >
                            {{ crumb.name }}
                        </span>
                        <Link
                            v-else
                            :href="folderUrl(crumb.id)"
                            class="rounded-lg px-2 py-1 font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-900"
                        >
                            {{ crumb.name }}
                        </Link>
                    </template>
                </nav>

                <!-- Filters Bar -->
                <div
                    class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="relative max-w-md flex-1">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari judul, topik, atau isi materi..."
                            class="w-full rounded-xl border border-slate-200 bg-white py-2 pr-3 pl-9 text-xs transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 focus:outline-hidden sm:text-sm"
                            @keydown.enter="applyFilter"
                        />
                        <div
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"
                        >
                            <FalconIcon name="search" className="w-4 h-4" />
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <select
                            v-model="selectedType"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 focus:outline-hidden sm:text-sm"
                            @change="applyFilter"
                        >
                            <option value="">Semua Jenis</option>
                            <option value="material">Materi</option>
                            <option value="announcement">Pengumuman</option>
                            <option value="assignment">Tugas</option>
                            <option value="quiz">Kuis</option>
                            <option value="link">Tautan</option>
                            <option value="poll">Polling</option>
                            <option value="h5p">H5P</option>
                        </select>

                        <select
                            v-if="classes.length > 0"
                            v-model="selectedClass"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 focus:outline-hidden sm:text-sm"
                            @change="applyFilter"
                        >
                            <option value="">Semua Kelas</option>
                            <option
                                v-for="c in classes"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.label }}
                            </option>
                        </select>

                        <button
                            type="button"
                            class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200"
                            @click="applyFilter"
                        >
                            Terapkan
                        </button>
                    </div>
                </div>

                <p
                    v-if="isOwnLibrary && isFiltering"
                    class="mb-3 text-xs text-slate-500"
                >
                    Menampilkan hasil dari semua folder.
                </p>

                <!-- Folders -->
                <div
                    v-if="folders.length > 0"
                    class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
                >
                    <div
                        v-for="folder in folders"
                        :key="folder.id"
                        class="group flex items-center gap-1 rounded-2xl border border-slate-200 bg-white pr-1 shadow-2xs transition hover:border-indigo-200 hover:shadow-md"
                    >
                        <Link
                            :href="folderUrl(folder.id)"
                            class="flex min-w-0 flex-1 items-center gap-2.5 px-4 py-3"
                        >
                            <FalconIcon
                                name="folder"
                                className="w-5 h-5 shrink-0 text-indigo-500"
                            />
                            <span
                                class="truncate text-sm font-semibold text-slate-800"
                                >{{ folder.name }}</span
                            >
                        </Link>
                        <button
                            type="button"
                            class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-indigo-600"
                            title="Ganti nama folder"
                            aria-label="Ganti nama folder"
                            @click="openRenameFolder(folder)"
                        >
                            <FalconIcon name="pencil" className="w-4 h-4" />
                        </button>
                        <button
                            type="button"
                            class="rounded-lg p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                            title="Hapus folder"
                            aria-label="Hapus folder"
                            @click="deleteFolder(folder)"
                        >
                            <FalconIcon name="trash" className="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="items.length === 0 && folders.length === 0"
                    class="rounded-2xl border border-dashed border-slate-200 p-10 text-center"
                >
                    <div
                        class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600"
                    >
                        <FalconIcon name="library" className="w-6 h-6" />
                    </div>
                    <p class="font-semibold text-slate-800">
                        {{ emptyMessage }}
                    </p>
                    <p
                        v-if="isOwnLibrary && !isFiltering"
                        class="mt-1 text-xs text-slate-400"
                    >
                        Unggah berkas atau buat konten baru untuk memulai.
                    </p>
                </div>

                <!-- Grid View -->
                <div
                    v-else-if="viewMode === 'grid'"
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="item in items"
                        :key="item.id"
                        class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs transition hover:shadow-md"
                    >
                        <div>
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <span
                                    class="rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700"
                                >
                                    {{ item.type_label }}
                                </span>
                                <span class="text-[11px] text-slate-400">{{
                                    item.created_at
                                }}</span>
                            </div>

                            <h3
                                class="mt-2.5 line-clamp-2 font-bold text-slate-900"
                            >
                                {{ item.title }}
                            </h3>

                            <p
                                v-if="item.topic"
                                class="mt-1 text-xs font-medium text-slate-500"
                            >
                                {{ item.topic }}
                            </p>

                            <p
                                v-if="item.content"
                                class="mt-2 line-clamp-3 text-xs leading-relaxed text-slate-600"
                            >
                                {{ item.content }}
                            </p>

                            <div
                                v-if="item.attachment_name"
                                class="mt-3 flex items-center gap-1.5 rounded-lg bg-slate-50 p-2 text-xs text-slate-600"
                            >
                                <FalconIcon
                                    name="download"
                                    className="w-3.5 h-3.5 shrink-0 text-slate-400"
                                />
                                <span class="truncate">{{
                                    item.attachment_name
                                }}</span>
                                <span
                                    v-if="item.attachment_size"
                                    class="shrink-0 text-slate-400"
                                    >{{ item.attachment_size }}</span
                                >
                            </div>

                            <p
                                v-if="!item.is_owner"
                                class="mt-2 text-[11px] text-slate-400"
                            >
                                Oleh {{ item.author_name }}
                            </p>
                            <p
                                v-if="item.shared_teacher_names.length > 0"
                                class="mt-2 text-[11px] text-slate-400"
                            >
                                Dibagikan ke
                                {{ item.shared_teacher_names.join(', ') }}
                            </p>
                        </div>

                        <div
                            class="mt-4 flex items-center justify-between gap-2 border-t border-slate-100 pt-3 text-xs"
                        >
                            <span
                                class="truncate font-medium text-slate-500"
                                :title="item.class_name"
                                >{{ item.class_name }}</span
                            >
                            <LibraryItemActions
                                :item="item"
                                @preview="itemBeingPreviewed = item"
                                @poll="pollItemId = item.id"
                                @play="itemBeingPlayed = item"
                                @share="itemBeingShared = item"
                                @move="itemBeingMoved = item"
                                @copy="copyItem(item)"
                                @toggle-graded="toggleItemGraded(item)"
                                @delete="deleteItem(item)"
                            />
                        </div>
                    </div>
                </div>

                <!-- List View (Default) -->
                <div
                    v-else-if="items.length > 0"
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs"
                >
                    <div class="divide-y divide-slate-100">
                        <div
                            v-for="item in items"
                            :key="item.id"
                            class="flex flex-col gap-2 p-4 transition hover:bg-slate-50/80 sm:flex-row sm:items-center sm:justify-between sm:p-5"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700"
                                    >
                                        {{ item.type_label }}
                                    </span>
                                    <span
                                        class="text-xs font-medium text-slate-500"
                                        >{{ item.class_name }}</span
                                    >
                                    <span
                                        v-if="item.topic"
                                        class="text-xs text-slate-400"
                                        >• {{ item.topic }}</span
                                    >
                                </div>

                                <h3
                                    class="mt-1 font-bold text-slate-900 sm:text-base"
                                >
                                    {{ item.title }}
                                </h3>

                                <p
                                    v-if="item.content"
                                    class="mt-1 line-clamp-2 text-xs text-slate-600"
                                >
                                    {{ item.content }}
                                </p>

                                <div
                                    class="mt-2 flex flex-wrap items-center gap-3 text-[11px] text-slate-400"
                                >
                                    <span>Oleh {{ item.author_name }}</span>
                                    <span>Dibuat {{ item.created_at }}</span>
                                    <span
                                        v-if="item.due_at"
                                        class="font-semibold text-amber-600"
                                        >Tenggat: {{ item.due_at }}</span
                                    >
                                    <span
                                        v-if="item.attachment_name"
                                        class="inline-flex items-center gap-1 text-indigo-600"
                                    >
                                        <FalconIcon
                                            name="download"
                                            className="w-3 h-3"
                                        />
                                        {{ item.attachment_name }}
                                        <span
                                            v-if="item.attachment_size"
                                            class="text-slate-400"
                                            >({{ item.attachment_size }})</span
                                        >
                                    </span>
                                    <span
                                        v-if="
                                            item.shared_teacher_names.length > 0
                                        "
                                    >
                                        Dibagikan ke
                                        {{
                                            item.shared_teacher_names.join(', ')
                                        }}
                                    </span>
                                </div>
                            </div>

                            <LibraryItemActions
                                :item="item"
                                class="sm:self-center"
                                @preview="itemBeingPreviewed = item"
                                @poll="pollItemId = item.id"
                                @play="itemBeingPlayed = item"
                                @share="itemBeingShared = item"
                                @move="itemBeingMoved = item"
                                @copy="copyItem(item)"
                                @toggle-graded="toggleItemGraded(item)"
                                @delete="deleteItem(item)"
                            />
                        </div>
                    </div>
                </div>

                <LibraryItemFormModal
                    :open="showCreateModal"
                    :folder-id="currentFolder?.id ?? null"
                    :folder-name="currentFolder?.name ?? null"
                    :classes="classes"
                    :upload="upload"
                    @close="showCreateModal = false"
                />
                <LibraryFolderModal
                    :open="showFolderModal"
                    :parent-id="currentFolder?.id ?? null"
                    :folder="folderBeingRenamed"
                    @close="showFolderModal = false"
                />
                <LibraryShareModal
                    :item="itemBeingShared"
                    :classes="classes"
                    :teachers="teachers"
                    @close="itemBeingShared = null"
                />
                <LibraryH5pModal
                    :item="itemBeingPlayed"
                    @close="itemBeingPlayed = null"
                />
                <LibraryPollModal
                    :item="itemBeingPolled"
                    @close="pollItemId = null"
                />
                <LibraryPreviewModal
                    :item="itemBeingPreviewed"
                    @close="itemBeingPreviewed = null"
                />
                <LibraryMoveModal
                    :item="itemBeingMoved"
                    :folder-options="folderOptions"
                    @close="itemBeingMoved = null"
                />
            </template>
        </AuthenticatedLayout>
    </div>
</template>
