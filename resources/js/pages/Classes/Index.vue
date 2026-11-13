<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import type { SharedProps } from '@/types';

type SchoolClassItem = {
    id: number;
    name: string;
    subject: string;
    room: string | null;
    code: string;
    description: string | null;
    guru_name: string;
    students_count: number;
    items_count: number;
    created_at: string;
};

const props = defineProps<{
    classes: SchoolClassItem[];
    filters: {
        search: string;
    };
}>();

const page = usePage<SharedProps>();
const user = page.props.auth.user;

const searchQuery = ref(props.filters.search || '');
const showCreateModal = ref(false);
const showJoinModal = ref(false);

const createForm = useForm({
    name: '',
    subject: '',
    room: '',
    description: '',
});

const joinForm = useForm({
    code: '',
});

function applyFilter(): void {
    router.get(
        '/kelas',
        {
            search: searchQuery.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function submitCreate(): void {
    createForm.post('/kelas', {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
}

function submitJoin(): void {
    joinForm.post('/kelas/join', {
        onSuccess: () => {
            showJoinModal.value = false;
            joinForm.reset();
        },
    });
}

function deleteClass(schoolClass: SchoolClassItem): void {
    if (confirm(`Pindahkan kelas "${schoolClass.name}" ke Tempat Sampah?`)) {
        router.delete(`/kelas/${schoolClass.id}`);
    }
}
</script>

<template>
    <div>
        <Head title="Kelas - Falcon LMS" />

        <AuthenticatedLayout title="Daftar Kelas">
            <template #header>
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h1
                            class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl"
                        >
                            Kelas
                        </h1>
                        <p class="mt-0.5 text-xs text-slate-500">
                            {{
                                user?.role === 'siswa'
                                    ? 'Kelas yang Anda ikuti untuk pembelajaran dan pengumpulan tugas.'
                                    : 'Ruang kelas yang Anda ampu beserta kode gabung siswa.'
                            }}
                        </p>
                    </div>

                    <div>
                        <button
                            v-if="user?.role === 'siswa'"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700"
                            @click="showJoinModal = true"
                        >
                            <FalconIcon name="plus" className="w-4 h-4" />
                            <span>Gabung Kelas</span>
                        </button>

                        <button
                            v-else
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700"
                            @click="showCreateModal = true"
                        >
                            <FalconIcon name="plus" className="w-4 h-4" />
                            <span>Buat Kelas Baru</span>
                        </button>
                    </div>
                </div>
            </template>

            <template #default="{ viewMode }">
                <!-- Search bar -->
                <div class="mb-5 max-w-md">
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama kelas, mapel, atau kode..."
                            class="w-full rounded-xl border border-slate-200 bg-white py-2 pr-3 pl-9 text-xs transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 focus:outline-hidden sm:text-sm"
                            @keydown.enter="applyFilter"
                        />
                        <div
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"
                        >
                            <FalconIcon name="search" className="w-4 h-4" />
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div
                    v-if="classes.length === 0"
                    class="rounded-2xl border border-dashed border-slate-200 p-10 text-center"
                >
                    <div
                        class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600"
                    >
                        <FalconIcon name="classes" className="w-6 h-6" />
                    </div>
                    <p class="font-semibold text-slate-800">
                        Belum ada kelas ditemukan.
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        {{
                            user?.role === 'siswa'
                                ? 'Minta kode kelas kepada guru Anda untuk bergabung.'
                                : 'Klik "Buat Kelas Baru" untuk menambahkan kelas yang Anda ampu.'
                        }}
                    </p>
                </div>

                <!-- Grid View -->
                <div
                    v-else-if="viewMode === 'grid'"
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="c in classes"
                        :key="c.id"
                        class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs transition hover:shadow-md"
                    >
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3
                                        class="font-bold text-slate-900 sm:text-base"
                                    >
                                        {{ c.name }}
                                    </h3>
                                    <p
                                        class="text-xs font-semibold text-indigo-600"
                                    >
                                        {{ c.subject }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span
                                        class="rounded-md bg-indigo-50 px-2 py-0.5 font-mono text-xs font-bold text-indigo-700"
                                        title="Kode Kelas"
                                    >
                                        {{ c.code }}
                                    </span>
                                </div>
                            </div>

                            <p
                                v-if="c.description"
                                class="mt-2 line-clamp-2 text-xs text-slate-500"
                            >
                                {{ c.description }}
                            </p>

                            <div
                                class="mt-4 space-y-1 text-xs text-slate-400"
                            >
                                <p>Guru: {{ c.guru_name }}</p>
                                <p v-if="c.room">Ruang: {{ c.room }}</p>
                            </div>
                        </div>

                        <div
                            class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-xs"
                        >
                            <span class="text-slate-500 font-medium">
                                {{ c.students_count }} Siswa •
                                {{ c.items_count }} Materi
                            </span>
                            <div class="flex items-center gap-2">
                                <Link
                                    :href="`/pustaka?class_id=${c.id}`"
                                    class="font-semibold text-indigo-600 hover:text-indigo-800"
                                >
                                    Buka &rarr;
                                </Link>
                                <button
                                    v-if="user?.role !== 'siswa'"
                                    type="button"
                                    class="text-slate-400 hover:text-rose-600"
                                    title="Hapus Kelas"
                                    @click="deleteClass(c)"
                                >
                                    <FalconIcon
                                        name="trash"
                                        className="w-4 h-4"
                                    />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- List View (Default) -->
                <div
                    v-else
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs"
                >
                    <table
                        class="w-full text-left text-xs text-slate-600 sm:text-sm"
                    >
                        <thead
                            class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold tracking-wider text-slate-500 uppercase"
                        >
                            <tr>
                                <th class="px-5 py-3">Nama Kelas & Mapel</th>
                                <th class="px-5 py-3">Kode Gabung</th>
                                <th class="px-5 py-3">Guru Pengampu</th>
                                <th class="px-5 py-3">Jumlah Siswa</th>
                                <th class="px-5 py-3">Materi / Tugas</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="c in classes"
                                :key="c.id"
                                class="transition hover:bg-slate-50/80"
                            >
                                <td class="px-5 py-3.5">
                                    <p class="font-bold text-slate-900">
                                        {{ c.name }}
                                    </p>
                                    <p class="text-xs text-indigo-600">
                                        {{ c.subject }}
                                    </p>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span
                                        class="rounded-md bg-indigo-50 px-2 py-0.5 font-mono text-xs font-bold text-indigo-700"
                                    >
                                        {{ c.code }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-slate-700">
                                    {{ c.guru_name }}
                                </td>
                                <td class="px-5 py-3.5 text-slate-600">
                                    {{ c.students_count }} Siswa
                                </td>
                                <td class="px-5 py-3.5 text-slate-600">
                                    {{ c.items_count }} Item
                                </td>
                                <td
                                    class="px-5 py-3.5 text-right whitespace-nowrap"
                                >
                                    <div class="inline-flex items-center gap-3">
                                        <Link
                                            :href="`/pustaka?class_id=${c.id}`"
                                            class="font-semibold text-indigo-600 hover:text-indigo-800"
                                        >
                                            Buka Pustaka
                                        </Link>
                                        <button
                                            v-if="user?.role !== 'siswa'"
                                            type="button"
                                            class="text-slate-400 hover:text-rose-600"
                                            title="Hapus"
                                            @click="deleteClass(c)"
                                        >
                                            <FalconIcon
                                                name="trash"
                                                className="w-4 h-4"
                                            />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Create Class Modal (Guru/Admin) -->
                <div
                    v-if="showCreateModal"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
                    @click.self="showCreateModal = false"
                >
                    <div
                        class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"
                    >
                        <div
                            class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3"
                        >
                            <h3 class="text-base font-bold text-slate-900">
                                Buat Kelas Baru
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
                                    >Nama Kelas *</label
                                >
                                <input
                                    v-model="createForm.name"
                                    type="text"
                                    required
                                    placeholder="misal: X RPL 1"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:outline-hidden"
                                />
                            </div>

                            <div>
                                <label
                                    class="mb-1 block text-xs font-semibold text-slate-700"
                                    >Mata Pelajaran *</label
                                >
                                <input
                                    v-model="createForm.subject"
                                    type="text"
                                    required
                                    placeholder="misal: Pemrograman Web"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:outline-hidden"
                                />
                            </div>

                            <div>
                                <label
                                    class="mb-1 block text-xs font-semibold text-slate-700"
                                    >Ruangan (Opsional)</label
                                >
                                <input
                                    v-model="createForm.room"
                                    type="text"
                                    placeholder="misal: Lab Komputer 1"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:outline-hidden"
                                />
                            </div>

                            <div>
                                <label
                                    class="mb-1 block text-xs font-semibold text-slate-700"
                                    >Deskripsi Singkat</label
                                >
                                <textarea
                                    v-model="createForm.description"
                                    rows="2"
                                    placeholder="Catatan kelas atau deskripsi kurikulum..."
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:outline-hidden"
                                ></textarea>
                            </div>

                            <div
                                class="flex items-center justify-end gap-2 border-t border-slate-100 pt-3"
                            >
                                <button
                                    type="button"
                                    class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                                    @click="showCreateModal = false"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    :disabled="createForm.processing"
                                    class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                                >
                                    {{
                                        createForm.processing
                                            ? 'Menyimpan...'
                                            : 'Buat Kelas'
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Join Class Modal (Siswa) -->
                <div
                    v-if="showJoinModal"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
                    @click.self="showJoinModal = false"
                >
                    <div
                        class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl"
                    >
                        <div
                            class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3"
                        >
                            <h3 class="text-base font-bold text-slate-900">
                                Gabung ke Kelas
                            </h3>
                            <button
                                type="button"
                                class="p-1 text-slate-400 hover:text-slate-600"
                                @click="showJoinModal = false"
                            >
                                <FalconIcon name="x" className="w-5 h-5" />
                            </button>
                        </div>

                        <form class="space-y-4" @submit.prevent="submitJoin">
                            <div>
                                <label
                                    class="mb-1 block text-xs font-semibold text-slate-700"
                                    >Kode Kelas dari Guru *</label
                                >
                                <input
                                    v-model="joinForm.code"
                                    type="text"
                                    required
                                    placeholder="misal: RPL10A"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-mono text-center text-lg font-bold uppercase tracking-widest focus:border-indigo-600 focus:outline-hidden"
                                />
                                <p class="mt-1 text-center text-xs text-slate-400">
                                    Masukkan 6 karakter kode kelas
                                </p>
                            </div>

                            <div
                                class="flex items-center justify-end gap-2 border-t border-slate-100 pt-3"
                            >
                                <button
                                    type="button"
                                    class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                                    @click="showJoinModal = false"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    :disabled="joinForm.processing"
                                    class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                                >
                                    {{
                                        joinForm.processing
                                            ? 'Memproses...'
                                            : 'Gabung Sekarang'
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </template>
        </AuthenticatedLayout>
    </div>
</template>

