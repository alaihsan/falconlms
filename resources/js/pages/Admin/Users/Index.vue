<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import UserTable from '@/components/admin/UserTable.vue';
import UserCreateModal from '@/components/admin/UserCreateModal.vue';
import UserEditModal from '@/components/admin/UserEditModal.vue';
import UserImportCsvModal from '@/components/admin/UserImportCsvModal.vue';
import type { PaginatedUsers, RoleOption, SharedProps, UserItem } from '@/types';

const props = defineProps<{
    users: PaginatedUsers;
    filters: {
        search: string;
        role: string;
    };
    roles: RoleOption[];
}>();

const page = usePage<SharedProps>();
const currentUser = page.props.auth.user;

const searchQuery = ref(props.filters.search || '');
const selectedRole = ref(props.filters.role || '');

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showImportModal = ref(false);
const editingUser = ref<UserItem | null>(null);

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

function openEditModal(user: UserItem): void {
    editingUser.value = user;
    showEditModal.value = true;
}

function closeEditModal(): void {
    showEditModal.value = false;
    editingUser.value = null;
}

function deleteUser(user: UserItem): void {
    if (confirm(`Apakah Anda yakin ingin menghapus akun "${user.name}"?`)) {
        router.delete(`/admin/users/${user.id}`);
    }
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
                            @click="showCreateModal = true"
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

            <UserTable
                :users="users"
                :current-user-id="currentUser?.id"
                @edit="openEditModal"
                @delete="deleteUser"
            />

            <UserCreateModal
                :open="showCreateModal"
                :roles="roles"
                @close="showCreateModal = false"
            />

            <UserEditModal
                :open="showEditModal"
                :user="editingUser"
                :roles="roles"
                @close="closeEditModal"
            />

            <UserImportCsvModal
                :open="showImportModal"
                @close="showImportModal = false"
            />
        </AuthenticatedLayout>
    </div>
</template>
