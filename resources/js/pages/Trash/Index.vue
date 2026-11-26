<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import type { SharedProps } from '@/types';

type TrashedItem = {
    id: number;
    kind: 'class' | 'item';
    title: string;
    subtitle: string;
    deleted_at: string;
};

defineProps<{
    items: TrashedItem[];
}>();

const page = usePage<SharedProps>();
const user = page.props.auth.user;

function restore(item: TrashedItem): void {
    router.post(`/tempat-sampah/${item.kind}/${item.id}/restore`);
}

function forceDelete(item: TrashedItem): void {
    if (
        confirm(
            `Hapus permanen "${item.title}"? Data yang dihapus permanen tidak dapat dikembalikan lagi.`,
        )
    ) {
        router.delete(`/tempat-sampah/${item.kind}/${item.id}`);
    }
}
</script>

<template>
    <div>
        <Head title="Tempat Sampah - Falcon LMS" />

        <AuthenticatedLayout title="Tempat Sampah">
            <template #header>
                <div>
                    <h1
                        class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl"
                    >
                        Tempat Sampah
                    </h1>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Berkas, materi, atau kelas yang dihapus sementara dapat
                        dipulihkan atau dihapus permanen.
                    </p>
                </div>
            </template>

            <template #default>
                <!-- Siswa notice -->
                <div
                    v-if="user?.role === 'siswa'"
                    class="rounded-2xl border border-slate-200 bg-white p-8 text-center"
                >
                    <p class="text-sm text-slate-600">
                        Tempat sampah hanya dapat diakses oleh Guru dan
                        Administrator.
                    </p>
                </div>

                <!-- Empty State -->
                <div
                    v-else-if="items.length === 0"
                    class="rounded-2xl border border-dashed border-slate-200 p-10 text-center"
                >
                    <div
                        class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500"
                    >
                        <FalconIcon name="trash" className="w-6 h-6" />
                    </div>
                    <p class="font-semibold text-slate-800">
                        Tempat sampah kosong.
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        Tidak ada item atau kelas yang sedang berada di tempat
                        sampah.
                    </p>
                </div>

                <!-- Trashed Items List -->
                <div
                    v-else
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs"
                >
                    <div class="divide-y divide-slate-100">
                        <div
                            v-for="item in items"
                            :key="`${item.kind}-${item.id}`"
                            class="flex flex-col gap-2 p-4 transition hover:bg-slate-50/80 sm:flex-row sm:items-center sm:justify-between sm:p-5"
                        >
                            <div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="rounded bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700 uppercase"
                                    >
                                        {{
                                            item.kind === 'class'
                                                ? 'Kelas'
                                                : 'Materi'
                                        }}
                                    </span>
                                    <h2 class="font-bold text-slate-900">
                                        {{ item.title }}
                                    </h2>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ item.subtitle }}
                                </p>
                                <p class="mt-0.5 text-[11px] text-slate-400">
                                    Dihapus pada: {{ item.deleted_at }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                    @click="restore(item)"
                                >
                                    Pulihkan
                                </button>
                                <button
                                    type="button"
                                    class="rounded-xl bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-100"
                                    @click="forceDelete(item)"
                                >
                                    Hapus Permanen
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </AuthenticatedLayout>
    </div>
</template>
