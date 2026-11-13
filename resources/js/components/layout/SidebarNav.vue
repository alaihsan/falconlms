<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import type { User } from '@/types';

const props = defineProps<{
    user: User | null;
    currentUrl: string;
    isMobileSidebarOpen: boolean;
    isNewMenuOpen: boolean;
}>();

const emit = defineEmits<{
    (e: 'close-mobile-sidebar'): void;
    (e: 'toggle-new-menu'): void;
}>();

function isRouteActive(urlPrefix: string): boolean {
    if (urlPrefix === '/' || urlPrefix === '/dashboard') {
        return props.currentUrl === '/' || props.currentUrl === '/dashboard';
    }
    return props.currentUrl.startsWith(urlPrefix);
}

function handleQuickAction(actionType: string): void {
    emit('close-mobile-sidebar');
    if (props.user?.role === 'siswa') {
        router.visit('/kelas', { data: { action: 'join' } });
        return;
    }

    if (actionType === 'class') {
        router.visit('/kelas', { data: { action: 'create' } });
    } else {
        router.visit('/pustaka', { data: { create: actionType } });
    }
}
</script>

<template>
    <!-- Mobile Sidebar Backdrop -->
    <div
        v-if="isMobileSidebarOpen"
        class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-xs transition-opacity lg:hidden"
        @click="emit('close-mobile-sidebar')"
    ></div>

    <!-- Left Navigation Sidebar (Google Drive style) -->
    <aside
        :class="[
            'fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 flex-col border-r border-slate-200 bg-white p-4 transition-transform duration-200 ease-in-out lg:static lg:bg-slate-50/80',
            isMobileSidebarOpen
                ? 'translate-x-0 shadow-2xl'
                : '-translate-x-full lg:translate-x-0',
        ]"
    >
        <!-- Mobile Drawer Header -->
        <div
            class="mb-2 flex items-center justify-between border-b border-slate-200 pb-3 lg:hidden"
        >
            <div class="flex items-center gap-2 font-bold text-slate-800">
                <FalconIcon name="falcon" className="w-5 h-5 text-indigo-600" />
                <span>Menu Navigasi</span>
            </div>
            <button
                type="button"
                class="rounded-md p-1 text-slate-500 hover:bg-slate-100"
                @click="emit('close-mobile-sidebar')"
            >
                <FalconIcon name="x" className="w-5 h-5" />
            </button>
        </div>

        <!-- Big Google Drive "+ Baru" / "Gabung Kelas" Button -->
        <div class="relative mb-5">
            <button
                v-if="user?.role === 'siswa'"
                type="button"
                class="flex w-full items-center justify-center gap-2.5 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 hover:shadow-md active:scale-98"
                @click="handleQuickAction('join')"
            >
                <div
                    class="flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-600 text-white shadow-xs"
                >
                    <FalconIcon name="plus" className="w-4 h-4" />
                </div>
                <span class="tracking-wide">Gabung Kelas</span>
            </button>

            <button
                v-else
                type="button"
                class="flex w-full items-center justify-center gap-2.5 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 hover:shadow-md active:scale-98"
                @click.stop="emit('toggle-new-menu')"
            >
                <div
                    class="flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-600 text-white shadow-xs"
                >
                    <FalconIcon name="plus" className="w-4 h-4" />
                </div>
                <span class="tracking-wide">Baru</span>
                <FalconIcon
                    name="chevron-down"
                    className="w-3.5 h-3.5 text-slate-400 ml-auto"
                />
            </button>

            <!-- + New Dropdown Menu (Guru & Admin) -->
            <div
                v-if="isNewMenuOpen && user?.role !== 'siswa'"
                class="absolute right-0 left-0 z-50 mt-2 rounded-xl border border-slate-200 bg-white py-1.5 text-xs shadow-xl"
                @click.stop
            >
                <button
                    type="button"
                    class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-slate-700 transition hover:bg-slate-50"
                    @click="handleQuickAction('announcement')"
                >
                    <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                    <span>Pengumuman</span>
                </button>
                <button
                    type="button"
                    class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-slate-700 transition hover:bg-slate-50"
                    @click="handleQuickAction('material')"
                >
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span>Materi (Berkas)</span>
                </button>
                <button
                    type="button"
                    class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-slate-700 transition hover:bg-slate-50"
                    @click="handleQuickAction('assignment')"
                >
                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                    <span>Tugas</span>
                </button>
                <button
                    type="button"
                    class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-slate-700 transition hover:bg-slate-50"
                    @click="handleQuickAction('quiz')"
                >
                    <span class="h-2 w-2 rounded-full bg-purple-500"></span>
                    <span>Kuis (Falcon Forms)</span>
                </button>
                <div class="my-1 border-t border-slate-100"></div>
                <button
                    type="button"
                    class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-slate-700 transition hover:bg-slate-50"
                    @click="handleQuickAction('class')"
                >
                    <FalconIcon
                        name="classes"
                        className="w-3.5 h-3.5 text-indigo-600"
                    />
                    <span>Kelas Baru</span>
                </button>
            </div>
        </div>

        <!-- Main Nav Links -->
        <nav
            class="flex-1 space-y-1 overflow-y-auto text-xs font-medium sm:text-sm"
        >
            <Link
                href="/"
                :class="[
                    'flex items-center gap-3 rounded-xl px-3 py-2.5 transition',
                    isRouteActive('/')
                        ? 'bg-indigo-100/70 font-semibold text-indigo-900 shadow-2xs'
                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                ]"
                @click="emit('close-mobile-sidebar')"
            >
                <FalconIcon name="home" className="w-5 h-5 text-indigo-600" />
                <span>Beranda</span>
            </Link>

            <Link
                href="/pustaka"
                :class="[
                    'flex items-center gap-3 rounded-xl px-3 py-2.5 transition',
                    isRouteActive('/pustaka')
                        ? 'bg-indigo-100/70 font-semibold text-indigo-900 shadow-2xs'
                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                ]"
                @click="emit('close-mobile-sidebar')"
            >
                <FalconIcon
                    name="library"
                    className="w-5 h-5 text-indigo-600"
                />
                <span>Pustaka</span>
            </Link>

            <Link
                href="/kelas"
                :class="[
                    'flex items-center gap-3 rounded-xl px-3 py-2.5 transition',
                    isRouteActive('/kelas')
                        ? 'bg-indigo-100/70 font-semibold text-indigo-900 shadow-2xs'
                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                ]"
                @click="emit('close-mobile-sidebar')"
            >
                <FalconIcon
                    name="classes"
                    className="w-5 h-5 text-indigo-600"
                />
                <span>Kelas</span>
            </Link>

            <Link
                href="/tugas-saya"
                :class="[
                    'flex items-center gap-3 rounded-xl px-3 py-2.5 transition',
                    isRouteActive('/tugas-saya')
                        ? 'bg-indigo-100/70 font-semibold text-indigo-900 shadow-2xs'
                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                ]"
                @click="emit('close-mobile-sidebar')"
            >
                <FalconIcon name="tasks" className="w-5 h-5 text-indigo-600" />
                <span>Tugas Saya</span>
            </Link>

            <Link
                href="/nilai"
                :class="[
                    'flex items-center gap-3 rounded-xl px-3 py-2.5 transition',
                    isRouteActive('/nilai')
                        ? 'bg-indigo-100/70 font-semibold text-indigo-900 shadow-2xs'
                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                ]"
                @click="emit('close-mobile-sidebar')"
            >
                <FalconIcon name="grades" className="w-5 h-5 text-indigo-600" />
                <span>Nilai</span>
            </Link>

            <Link
                href="/tempat-sampah"
                :class="[
                    'flex items-center gap-3 rounded-xl px-3 py-2.5 transition',
                    isRouteActive('/tempat-sampah')
                        ? 'bg-indigo-100/70 font-semibold text-indigo-900 shadow-2xs'
                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                ]"
                @click="emit('close-mobile-sidebar')"
            >
                <FalconIcon name="trash" className="w-5 h-5 text-slate-500" />
                <span>Tempat Sampah</span>
            </Link>

            <!-- Admin Only Navigation -->
            <template v-if="user?.role === 'admin'">
                <div class="pt-4 pb-1">
                    <p
                        class="px-3 text-[11px] font-bold tracking-wider text-slate-400 uppercase"
                    >
                        Administrasi
                    </p>
                </div>

                <Link
                    href="/admin/users"
                    :class="[
                        'flex items-center gap-3 rounded-xl px-3 py-2.5 transition',
                        isRouteActive('/admin/users')
                            ? 'bg-indigo-100/70 font-semibold text-indigo-900 shadow-2xs'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                    ]"
                    @click="emit('close-mobile-sidebar')"
                >
                    <FalconIcon
                        name="users"
                        className="w-5 h-5 text-indigo-600"
                    />
                    <span>Kelola Pengguna</span>
                </Link>
            </template>
        </nav>

        <!-- Sidebar Footer Info -->
        <div
            class="flex items-center justify-between border-t border-slate-200 pt-3 text-[11px] text-slate-400"
        >
            <span>Falcon LMS v1.0</span>
            <span class="inline-flex items-center gap-1 text-emerald-600">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                Lokal
            </span>
        </div>
    </aside>
</template>

