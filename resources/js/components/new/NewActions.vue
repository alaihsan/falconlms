<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, useTemplateRef } from 'vue';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import FolderController from '@/actions/App/Http/Controllers/Content/FolderController';
import ItemController from '@/actions/App/Http/Controllers/Content/ItemController';
import CourseController from '@/actions/App/Http/Controllers/Courses/CourseController';
import JoinCourseController from '@/actions/App/Http/Controllers/Courses/JoinCourseController';
import MemberController from '@/actions/App/Http/Controllers/Courses/MemberController';
import PostController from '@/actions/App/Http/Controllers/Courses/PostController';
import TopicController from '@/actions/App/Http/Controllers/Courses/TopicController';
import FormDialog from '@/components/new/FormDialog.vue';
import LibraryImportDialog from '@/components/new/LibraryImportDialog.vue';
import TemporaryPasswordDialog from '@/components/new/TemporaryPasswordDialog.vue';
import { uploadFiles, useNewActions } from '@/composables/useNewActions';
import type { NewDialog } from '@/composables/useNewActions';
import type { CreationContext } from '@/types';

const page = usePage<{
    context?: CreationContext;
    roles?: { value: string; label: string }[];
}>();
const context = computed(() => page.props.context ?? null);
const space = computed(() => context.value?.space ?? null);

const { openDialog, close, registerInputs, run } = useNewActions();

const fileInput = useTemplateRef<HTMLInputElement>('fileInput');
const folderInput = useTemplateRef<HTMLInputElement>('folderInput');

onMounted(() => {
    registerInputs(fileInput.value, folderInput.value);

    // The users page opens the "new account" dialog when asked to (?baru=1).
    if (
        page.component === 'admin/users/Index' &&
        new URLSearchParams(window.location.search).has('baru')
    ) {
        run('user');
    }
});

function isOpen(name: NewDialog) {
    return openDialog.value === name;
}

function setOpen(open: boolean) {
    if (!open) {
        close();
    }
}

function onPicked(event: Event, withPaths: boolean): void {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files ?? []);

    if (space.value) {
        uploadFiles(
            space.value,
            files,
            {
                folderId: context.value?.folder?.id,
                topicId: context.value?.topic?.id,
            },
            withPaths ? files.map((file) => file.webkitRelativePath) : [],
        );
    }

    // Allow picking the same file again.
    input.value = '';
}
</script>

<template>
    <input
        ref="fileInput"
        type="file"
        multiple
        class="hidden"
        data-test="new-file-input"
        @change="onPicked($event, false)"
    />
    <input
        ref="folderInput"
        type="file"
        webkitdirectory
        class="hidden"
        data-test="new-folder-input"
        @change="onPicked($event, true)"
    />

    <template v-if="space">
        <FormDialog
            :open="isOpen('folder')"
            title="Folder baru"
            :fields="[{ name: 'name', label: 'Nama folder', required: true }]"
            :action="FolderController.store.url(space.id)"
            :extra="{ parent_id: context?.folder?.id ?? null }"
            submit-label="Buat"
            @update:open="setOpen"
        />
        <FormDialog
            :open="isOpen('link')"
            title="Tautan baru"
            description="Alamat web di jaringan sekolah, misalnya aplikasi atau situs belajar."
            :fields="[
                { name: 'title', label: 'Judul', required: true },
                {
                    name: 'url',
                    label: 'Alamat',
                    type: 'url',
                    placeholder: 'http://',
                    required: true,
                },
            ]"
            :action="ItemController.store.url(space.id)"
            :extra="{ type: 'link', folder_id: context?.folder?.id ?? null }"
            submit-label="Simpan"
            @update:open="setOpen"
        />
        <FormDialog
            :open="isOpen('topic')"
            title="Topik baru"
            :fields="[
                {
                    name: 'name',
                    label: 'Nama topik',
                    placeholder: 'Bab 1 - Aljabar',
                    required: true,
                },
            ]"
            :action="TopicController.store.url(space.id)"
            submit-label="Buat"
            @update:open="setOpen"
        />
        <FormDialog
            :open="isOpen('announcement')"
            title="Pengumuman"
            description="Tampil di Forum kelas dan dikirim sebagai notifikasi ke siswa."
            :fields="[
                {
                    name: 'body',
                    label: 'Isi pengumuman',
                    type: 'textarea',
                    required: true,
                },
            ]"
            :action="PostController.store.url(space.id)"
            submit-label="Umumkan"
            @update:open="setOpen"
        />
        <FormDialog
            :open="isOpen('add_members')"
            title="Tambah anggota"
            description="Akun siswa masuk sebagai siswa, akun guru sebagai guru pendamping."
            :fields="[
                {
                    name: 'usernames',
                    label: 'Username atau NIS',
                    type: 'textarea',
                    hint: 'Pisahkan dengan baris baru, spasi, atau koma.',
                    required: true,
                },
            ]"
            :action="MemberController.store.url(space.id)"
            submit-label="Tambahkan"
            @update:open="setOpen"
        />
        <LibraryImportDialog
            v-if="space.kind === 'course'"
            :open="isOpen('from_library')"
            :course="space"
            :topic-id="context?.topic?.id ?? null"
            @update:open="setOpen"
        />
    </template>

    <FormDialog
        :open="isOpen('course')"
        title="Kelas baru"
        :fields="[
            {
                name: 'name',
                label: 'Nama kelas',
                placeholder: 'Matematika X IPA 1',
                required: true,
            },
            {
                name: 'section',
                label: 'Bagian (opsional)',
                placeholder: 'Semester 1',
            },
            { name: 'subject', label: 'Mata pelajaran (opsional)' },
        ]"
        :action="CourseController.store.url()"
        submit-label="Buat kelas"
        @update:open="setOpen"
    />
    <FormDialog
        :open="isOpen('join_course')"
        title="Gabung kelas"
        description="Minta kode kelas kepada guru Anda."
        :fields="[
            {
                name: 'code',
                label: 'Kode kelas',
                placeholder: 'k7mq2xa',
                required: true,
            },
        ]"
        :action="JoinCourseController.url()"
        submit-label="Gabung"
        @update:open="setOpen"
    />
    <FormDialog
        :open="isOpen('user')"
        title="Pengguna baru"
        description="Akun dibuat dengan password sementara yang wajib diganti saat login."
        :fields="[
            {
                name: 'username',
                label: 'Username atau NIS',
                required: true,
            },
            { name: 'name', label: 'Nama lengkap', required: true },
            {
                name: 'role',
                label: 'Peran',
                type: 'select',
                required: true,
                options: page.props.roles ?? [
                    { value: 'student', label: 'Siswa' },
                    { value: 'teacher', label: 'Guru' },
                    { value: 'admin', label: 'Administrator' },
                ],
            },
        ]"
        :initial="{ role: 'student' }"
        :action="UserController.store.url()"
        submit-label="Buat akun"
        @update:open="setOpen"
    />

    <TemporaryPasswordDialog />
</template>
