<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import type { SharedProps, UserRole } from '@/types';

type UserItem = {
    id: number;
    name: string;
    email: string;
    username: string | null;
    role: UserRole;
    role_label: string;
    must_change_password: boolean;
    created_at: string;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedUsers = {
    data: UserItem[];
    links: PaginationLink[];
    total: number;
    current_page: number;
    last_page: number;
};

const props = defineProps<{
    users: PaginatedUsers;
    filters: {
        search: string;
        role: string;
    };
    roles: Array<{ value: string; label: string }>;
}>();

const page = usePage<SharedProps>();
const currentUser = page.props.auth.user;

const searchQuery = ref(props.filters.search || '');
const selectedRole = ref(props.filters.role || '');

// Modals
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showImportModal = ref(false);
const editingUser = ref<UserItem | null>(null);

// Forms
const createForm = useForm({
    name: '',
    email: '',
    username: '',
    role: 'siswa',
    password: '',
    must_change_password: true,
});

const editForm = useForm({
    name: '',
    email: '',
    username: '',
    role: 'siswa',
    password: '',
    must_change_password: false,
});

const importForm = useForm<{
    file: File | null;
}>({
    file: null,
});

function applyFilter(): void {
    router.get(
        '/admin/users',
        {
            search: searchQuery.value || undefined,
            role: selectedRole.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}

function openCreateModal(): void {
    createForm.reset();
    createForm.clearErrors();
    createForm.role = 'siswa';
    createForm.must_change_password = true;
    showCreateModal.value = true;
}

function submitCreate(): void {
    createForm.post('/admin/users', {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
}

function openEditModal(user: UserItem): void {
    editingUser.value = user;
    editForm.reset();
    editForm.clearErrors();
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.username = user.username || '';
    editForm.role = user.role;
    editForm.password = '';
    editForm.must_change_password = user.must_change_password;
    showEditModal.value = true;
}

function submitEdit(): void {
    if (!editingUser.value) return;
    editForm.put(`/admin/users/${editingUser.value.id}`, {
        onSuccess: () => {
            showEditModal.value = false;
            editingUser.value = null;
        },
    });
}

function deleteUser(user: UserItem): void {
    if (confirm(`Apakah Anda yakin ingin menghapus akun "${user.name}"?`)) {
        router.delete(`/admin/users/${user.id}`);
    }
}

function onFileSelected(event: Event): void {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        importForm.file = target.files[0];
    }
}

function submitImport(): void {
    if (!importForm.file) return;
    importForm.post('/admin/users/import-csv', {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
}
</script>

<template>
    <div>
        <Head title="Kelola Pengguna - Falcon LMS" />

        <AuthenticatedLayout title="Kelola Pengguna">
            <template #header>
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h1
                            class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl"
                        >
                            Kelola Pengguna
                        </h1>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Manajemen akun administrator, guru, dan siswa
                            intranet sekolah.
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200"
                            @click="showImportModal = true"
                        >
                            <FalconIcon
                                name="upload"
                                className="w-4 h-4 text-slate-600"
                            />
                            <span>Impor CSV</span>
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700"
                            @click="openCreateModal"
                        >
                            <FalconIcon name="plus" className="w-4 h-4" />
                            <span>Tambah Pengguna</span>
                        </button>
                    </div>
                </div>
            </template>

            <!-- Filters Bar -->
            <div
                class="mb-5 flex flex-col items-stretch justify-between gap-3 sm:flex-row sm:items-center"
            >
                <div class="relative max-w-md flex-1">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama, email, atau username..."
                        class="w-full rounded-xl border border-slate-200 bg-white py-2 pr-3 pl-9 text-xs transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 focus:outline-hidden sm:text-sm"
                        @keydown.enter="applyFilter"
                    />
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"
                    >
                        <FalconIcon name="search" className="w-4 h-4" />
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <select
                        v-model="selectedRole"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 focus:outline-hidden sm:text-sm"
                        @change="applyFilter"
                    >
                        <option value="">Semua Peran</option>
                        <option
                            v-for="r in roles"
                            :key="r.value"
                            :value="r.value"
                        >
                            {{ r.label }}
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

            <!-- Users Table -->
            <div
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs"
            >
                <div class="overflow-x-auto">
                    <table
                        class="w-full text-left text-xs text-slate-600 sm:text-sm"
                    >
                        <thead
                            class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold tracking-wider text-slate-500 uppercase"
                        >
                            <tr>
                                <th class="px-5 py-3">Pengguna</th>
                                <th class="px-5 py-3">Username</th>
                                <th class="px-5 py-3">Peran</th>
                                <th class="px-5 py-3">Status Password</th>
                                <th class="px-5 py-3">Terdaftar</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="users.data.length === 0">
                                <td
                                    colspan="6"
                                    class="px-5 py-10 text-center text-slate-400"
                                >
                                    Tidak ada data pengguna yang ditemukan.
                                </td>
                            </tr>
                            <tr
                                v-for="u in users.data"
                                :key="u.id"
                                class="transition hover:bg-slate-50/80"
                            >
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700 uppercase"
                                        >
                                            {{ u.name.slice(0, 2) }}
                                        </div>
                                        <div>
                                            <p
                                                class="font-semibold text-slate-900"
                                            >
                                                {{ u.name }}
                                            </p>
                                            <p class="text-xs text-slate-400">
                                                {{ u.email }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td
                                    class="px-5 py-3.5 font-mono text-xs text-slate-600"
                                >
                                    {{ u.username || '-' }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span
                                        :class="[
                                            'rounded-full border px-2.5 py-0.5 text-[11px] font-semibold',
                                            u.role === 'admin'
                                                ? 'border-purple-200 bg-purple-50 text-purple-700'
                                                : u.role === 'guru'
                                                  ? 'border-blue-200 bg-blue-50 text-blue-700'
                                                  : 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                        ]"
                                    >
                                        {{ u.role_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span
                                        v-if="u.must_change_password"
                                        class="inline-flex items-center gap-1 rounded-md border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700"
                                    >
                                        <FalconIcon
                                            name="alert"
                                            className="w-3 h-3 text-amber-600"
                                        />
                                        Wajib Ganti
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-600"
                                    >
                                        <FalconIcon
                                            name="check"
                                            className="w-3 h-3"
                                        />
                                        Aktif
                                    </span>
                                </td>
                                <td
                                    class="px-5 py-3.5 text-xs whitespace-nowrap text-slate-400"
                                >
                                    {{ u.created_at }}
                                </td>
                                <td
                                    class="px-5 py-3.5 text-right whitespace-nowrap"
                                >
                                    <div class="inline-flex items-center gap-2">
                                        <button
                                            type="button"
                                            class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-indigo-600"
                                            title="Ubah Data"
                                            @click="openEditModal(u)"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            v-if="currentUser?.id !== u.id"
                                            type="button"
                                            class="rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                            title="Hapus Pengguna"
                                            @click="deleteUser(u)"
                                        >
                                            Hapus
                                        </button>
                                        <span
                                            v-else
                                            class="px-1 text-[10px] text-slate-300 italic"
                                        >
                                            (Akun Anda)
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="users.links.length > 3"
                    class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-5 py-3.5 text-xs text-slate-500"
                >
                    <div>
                        Menampilkan total
                        <span class="font-bold text-slate-800">{{
                            users.total
                        }}</span>
                        pengguna
                    </div>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, i) in users.links" :key="i">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'rounded-lg border px-2.5 py-1 text-xs font-semibold transition',
                                    link.active
                                        ? 'border-indigo-600 bg-indigo-600 text-white shadow-2xs'
                                        : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-100',
                                ]"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="rounded-lg border border-transparent px-2.5 py-1 text-xs text-slate-300 select-none"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>

            <!-- Create User Modal -->
            <div
                v-if="showCreateModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
                @click.self="showCreateModal = false"
            >
                <div
                    class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-slate-100 bg-white p-6 shadow-2xl"
                >
                    <div
                        class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3"
                    >
                        <h3 class="text-base font-bold text-slate-900">
                            Tambah Pengguna Baru
                        </h3>
                        <button
                            type="button"
                            class="p-1 text-slate-400 hover:text-slate-600"
                            @click="showCreateModal = false"
                        >
                            <FalconIcon name="x" className="w-5 h-5" />
                        </button>
                    </div>

                    <form class="space-y-4" @submit.prevent="submitCreate">
                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold text-slate-700"
                                >Nama Lengkap *</label
                            >
                            <input
                                v-model="createForm.name"
                                type="text"
                                required
                                placeholder="misal: Budi Santoso"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 focus:outline-hidden"
                            />
                            <p
                                v-if="createForm.errors.name"
                                class="mt-1 text-xs text-rose-600"
                            >
                                {{ createForm.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold text-slate-700"
                                >Email *</label
                            >
                            <input
                                v-model="createForm.email"
                                type="email"
                                required
                                placeholder="misal: budi@falcon.sch.id"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 focus:outline-hidden"
                            />
                            <p
                                v-if="createForm.errors.email"
                                class="mt-1 text-xs text-rose-600"
                            >
                                {{ createForm.errors.email }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold text-slate-700"
                                >Username (Opsional)</label
                            >
                            <input
                                v-model="createForm.username"
                                type="text"
                                placeholder="misal: budi01 (NISN / NIP)"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 focus:outline-hidden"
                            />
                            <p
                                v-if="createForm.errors.username"
                                class="mt-1 text-xs text-rose-600"
                            >
                                {{ createForm.errors.username }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold text-slate-700"
                                >Peran *</label
                            >
                            <select
                                v-model="createForm.role"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 focus:outline-hidden"
                            >
                                <option
                                    v-for="r in roles"
                                    :key="r.value"
                                    :value="r.value"
                                >
                                    {{ r.label }}
                                </option>
                            </select>
                            <p
                                v-if="createForm.errors.role"
                                class="mt-1 text-xs text-rose-600"
                            >
                                {{ createForm.errors.role }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold text-slate-700"
                                >Kata Sandi Awal *</label
                            >
                            <input
                                v-model="createForm.password"
                                type="password"
                                required
                                placeholder="Minimal 6 karakter"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 focus:outline-hidden"
                            />
                            <p
                                v-if="createForm.errors.password"
                                class="mt-1 text-xs text-rose-600"
                            >
                                {{ createForm.errors.password }}
                            </p>
                        </div>

                        <div class="pt-1">
                            <label
                                class="flex cursor-pointer items-center gap-2 select-none"
                            >
                                <input
                                    v-model="createForm.must_change_password"
                                    type="checkbox"
                                    class="h-4 w-4 rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="text-xs font-medium text-slate-700"
                                    >Wajibkan ganti password saat login
                                    pertama</span
                                >
                            </label>
                        </div>

                        <div
                            class="flex items-center justify-end gap-2 border-t border-slate-100 pt-3"
                        >
                            <button
                                type="button"
                                class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                                @click="showCreateModal = false"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="createForm.processing"
                                class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700 disabled:opacity-60"
                            >
                                {{
                                    createForm.processing
                                        ? 'Menyimpan...'
                                        : 'Simpan Pengguna'
                                }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit User Modal -->
            <div
                v-if="showEditModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
                @click.self="showEditModal = false"
            >
                <div
                    class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-slate-100 bg-white p-6 shadow-2xl"
                >
                    <div
                        class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3"
                    >
                        <h3 class="text-base font-bold text-slate-900">
                            Ubah Data Pengguna
                        </h3>
                        <button
                            type="button"
                            class="p-1 text-slate-400 hover:text-slate-600"
                            @click="showEditModal = false"
                        >
                            <FalconIcon name="x" className="w-5 h-5" />
                        </button>
                    </div>

                    <form class="space-y-4" @submit.prevent="submitEdit">
                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold text-slate-700"
                                >Nama Lengkap *</label
                            >
                            <input
                                v-model="editForm.name"
                                type="text"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 focus:outline-hidden"
                            />
                            <p
                                v-if="editForm.errors.name"
                                class="mt-1 text-xs text-rose-600"
                            >
                                {{ editForm.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold text-slate-700"
                                >Email *</label
                            >
                            <input
                                v-model="editForm.email"
                                type="email"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 focus:outline-hidden"
                            />
                            <p
                                v-if="editForm.errors.email"
                                class="mt-1 text-xs text-rose-600"
                            >
                                {{ editForm.errors.email }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold text-slate-700"
                                >Username (Opsional)</label
                            >
                            <input
                                v-model="editForm.username"
                                type="text"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 focus:outline-hidden"
                            />
                            <p
                                v-if="editForm.errors.username"
                                class="mt-1 text-xs text-rose-600"
                            >
                                {{ editForm.errors.username }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold text-slate-700"
                                >Peran *</label
                            >
                            <select
                                v-model="editForm.role"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 focus:outline-hidden"
                            >
                                <option
                                    v-for="r in roles"
                                    :key="r.value"
                                    :value="r.value"
                                >
                                    {{ r.label }}
                                </option>
                            </select>
                            <p
                                v-if="editForm.errors.role"
                                class="mt-1 text-xs text-rose-600"
                            >
                                {{ editForm.errors.role }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold text-slate-700"
                                >Kata Sandi Baru (Kosongkan bila tidak
                                diubah)</label
                            >
                            <input
                                v-model="editForm.password"
                                type="password"
                                placeholder="Biarkan kosong jika tidak ingin mengubah sandi"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 focus:outline-hidden"
                            />
                            <p
                                v-if="editForm.errors.password"
                                class="mt-1 text-xs text-rose-600"
                            >
                                {{ editForm.errors.password }}
                            </p>
                        </div>

                        <div class="pt-1">
                            <label
                                class="flex cursor-pointer items-center gap-2 select-none"
                            >
                                <input
                                    v-model="editForm.must_change_password"
                                    type="checkbox"
                                    class="h-4 w-4 rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="text-xs font-medium text-slate-700"
                                    >Wajibkan ganti password saat login
                                    berikutnya</span
                                >
                            </label>
                        </div>

                        <div
                            class="flex items-center justify-end gap-2 border-t border-slate-100 pt-3"
                        >
                            <button
                                type="button"
                                class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                                @click="showEditModal = false"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700 disabled:opacity-60"
                            >
                                {{
                                    editForm.processing
                                        ? 'Menyimpan...'
                                        : 'Perbarui Pengguna'
                                }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Import CSV Modal -->
            <div
                v-if="showImportModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
                @click.self="showImportModal = false"
            >
                <div
                    class="relative w-full max-w-lg rounded-2xl border border-slate-100 bg-white p-6 shadow-2xl"
                >
                    <div
                        class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3"
                    >
                        <div class="flex items-center gap-2">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
                            >
                                <FalconIcon name="upload" className="w-4 h-4" />
                            </div>
                            <h3 class="text-base font-bold text-slate-900">
                                Impor Siswa Massal via CSV
                            </h3>
                        </div>
                        <button
                            type="button"
                            class="p-1 text-slate-400 hover:text-slate-600"
                            @click="showImportModal = false"
                        >
                            <FalconIcon name="x" className="w-5 h-5" />
                        </button>
                    </div>

                    <div
                        class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-3.5 text-xs leading-relaxed text-slate-600"
                    >
                        <p class="mb-1 font-semibold text-slate-800">
                            Ketentuan Format CSV:
                        </p>
                        <ul
                            class="list-inside list-disc space-y-0.5 text-slate-500"
                        >
                            <li>
                                Kolom:
                                <code
                                    class="rounded bg-indigo-50 px-1 py-0.5 font-mono text-indigo-700"
                                    >nama, email, username, password</code
                                >
                            </li>
                            <li>
                                Siswa yang diimpor otomatis mendapat peran
                                <strong>Siswa</strong>.
                            </li>
                            <li>
                                Semua siswa yang diimpor
                                <strong>wajib mengganti kata sandi</strong> saat
                                login pertama.
                            </li>
                            <li>
                                Bila password dikosongkan, default kata sandi
                                adalah <code class="font-mono">siswa123</code>.
                            </li>
                        </ul>
                        <div class="mt-3">
                            <a
                                href="/admin/users/template-csv"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                            >
                                <FalconIcon
                                    name="download"
                                    className="w-3.5 h-3.5"
                                />
                                <span>Unduh Contoh Berkas Template CSV</span>
                            </a>
                        </div>
                    </div>

                    <form class="space-y-4" @submit.prevent="submitImport">
                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold text-slate-700"
                                >Pilih Berkas CSV *</label
                            >
                            <input
                                type="file"
                                accept=".csv,text/csv,text/plain"
                                required
                                class="w-full cursor-pointer rounded-xl border border-slate-300 text-xs text-slate-600 file:mr-3 file:rounded-xl file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                                @change="onFileSelected"
                            />
                            <p
                                v-if="importForm.errors.file"
                                class="mt-1 text-xs text-rose-600"
                            >
                                {{ importForm.errors.file }}
                            </p>
                        </div>

                        <div
                            class="flex items-center justify-end gap-2 border-t border-slate-100 pt-3"
                        >
                            <button
                                type="button"
                                class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                                @click="showImportModal = false"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="
                                    importForm.processing || !importForm.file
                                "
                                class="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700 disabled:opacity-60"
                            >
                                <span
                                    v-if="importForm.processing"
                                    class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"
                                ></span>
                                <span>{{
                                    importForm.processing
                                        ? 'Mengunggah & Memproses...'
                                        : 'Mulai Impor Siswa'
                                }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    </div>
</template>
