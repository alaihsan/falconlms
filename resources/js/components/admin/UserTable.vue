<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import type { PaginatedUsers, UserItem } from '@/types';

defineProps<{
    users: PaginatedUsers;
    currentUserId?: number;
}>();

const emit = defineEmits<{
    (e: 'edit', user: UserItem): void;
    (e: 'delete', user: UserItem): void;
}>();
</script>

<template>
    <div
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs"
    >
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 sm:text-sm">
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
                                    <p class="font-semibold text-slate-900">
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
                                <FalconIcon name="check" className="w-3 h-3" />
                                Aktif
                            </span>
                        </td>
                        <td
                            class="px-5 py-3.5 text-xs whitespace-nowrap text-slate-400"
                        >
                            {{ u.created_at }}
                        </td>
                        <td class="px-5 py-3.5 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-indigo-600"
                                    title="Ubah Data"
                                    @click="emit('edit', u)"
                                >
                                    Edit
                                </button>
                                <button
                                    v-if="currentUserId !== u.id"
                                    type="button"
                                    class="rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                    title="Hapus Pengguna"
                                    @click="emit('delete', u)"
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
                <span class="font-bold text-slate-800">{{ users.total }}</span>
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
                    >
                        <span v-if="link.label.includes('Previous')"
                            >&laquo; Sebelumnya</span
                        >
                        <span v-else-if="link.label.includes('Next')"
                            >Berikutnya &raquo;</span
                        >
                        <span v-else>{{ link.label }}</span>
                    </Link>
                    <span
                        v-else
                        class="rounded-lg border border-transparent px-2.5 py-1 text-xs text-slate-300 select-none"
                    >
                        <span v-if="link.label.includes('Previous')"
                            >&laquo; Sebelumnya</span
                        >
                        <span v-else-if="link.label.includes('Next')"
                            >Berikutnya &raquo;</span
                        >
                        <span v-else>{{ link.label }}</span>
                    </span>
                </template>
            </div>
        </div>
    </div>
</template>
