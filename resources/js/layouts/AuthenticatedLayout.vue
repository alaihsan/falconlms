<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import type { SharedProps } from '@/types';

defineProps<{
    title?: string;
}>();

const page = usePage<SharedProps>();
const user = computed(() => page.props.auth.user);
const flash = computed(() => page.props.flash);

const isMobileSidebarOpen = ref(false);
const isUserMenuOpen = ref(false);
const isNewMenuOpen = ref(false);
const viewMode = ref<'list' | 'grid'>('list');

const currentUrl = computed(() => page.url);

function isRouteActive(urlPrefix: string): boolean {
    if (urlPrefix === '/' || urlPrefix === '/dashboard') {
        return currentUrl.value === '/' || currentUrl.value === '/dashboard';
    }
    return currentUrl.value.startsWith(urlPrefix);
}

function closeAllMenus(): void {
    isUserMenuOpen.value = false;
    isNewMenuOpen.value = false;
}
</script>

<template>
    <div
        class="flex min-h-screen flex-col bg-slate-50 text-slate-800 antialiased selection:bg-indigo-500 selection:text-white"
        @click="closeAllMenus"
    >
        <!-- Top App Header -->
        <header
            class="sticky top-0 z-30 flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-3 py-2.5 shadow-xs sm:px-6"
        >
            <!-- Left: Mobile Menu Toggle & Brand -->
            <div class="flex shrink-0 items-center gap-2 sm:gap-4">
                <button
                    type="button"
                    class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-hidden lg:hidden"
                    aria-label="Buka Menu"
                    @click.stop="isMobileSidebarOpen = !isMobileSidebarOpen"
                >
                    <FalconIcon name="menu" className="w-5 h-5" />
                </button>

                <Link href="/" class="group flex items-center gap-2.5">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm shadow-indigo-600/30 transition group-hover:bg-indigo-700"
                    >
                        <FalconIcon name="falcon" className="w-5 h-5" />
                    </div>
                    <div class="hidden leading-tight sm:block">
                        <div
                            class="flex items-center gap-1.5 text-base font-bold tracking-tight text-slate-900"
                        >
                            Falcon LMS
                            <span
                                class="rounded-sm border border-indigo-200/60 bg-indigo-50 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-700 uppercase"
                            >
                                Intranet
                            </span>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Center: Big Google Drive-style Search Box -->
            <div class="mx-1 max-w-2xl flex-1 sm:mx-4">
                <div class="group relative">
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 transition group-focus-within:text-indigo-600"
                    >
                        <FalconIcon
                            name="search"
                            className="w-4 h-4 sm:w-5 sm:h-5"
                        />
                    </div>
                    <input
                        type="text"
                        placeholder="Telusuri judul, materi, tugas, kelas, atau mapel..."
                        class="w-full rounded-full border border-transparent bg-slate-100 py-2 pr-24 pl-10 text-xs shadow-inner transition placeholder:text-slate-400 hover:bg-slate-200/70 focus:border-indigo-500 focus:bg-white focus:ring-3 focus:ring-indigo-100 focus:outline-hidden sm:py-2.5 sm:text-sm"
                    />
                    <div
                        class="absolute inset-y-0 right-1.5 hidden items-center sm:flex"
                    >
                        <span
                            class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-500 shadow-2xs"
                        >
                            Semua
                        </span>
                    </div>
                </div>
            </div>

            <!-- Right: Status Badge & User Profile -->
            <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                <!-- Offline / Intranet Indicator -->
                <div
                    class="hidden items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 md:inline-flex"
                >
                    <span
                        class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"
                    ></span>
                    <span>Intranet Aktif</span>
                </div>

                <!-- User Dropdown Menu -->
                <div v-if="user" class="relative">
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-full p-1 transition hover:bg-slate-100 focus:outline-hidden sm:p-1.5"
                        @click.stop="isUserMenuOpen = !isUserMenuOpen"
                    >
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700 uppercase ring-2 ring-indigo-200/60"
                        >
                            {{ user.name.slice(0, 2) }}
                        </div>
                        <div
                            class="hidden pr-1 text-left text-xs leading-tight xl:block"
                        >
                            <p
                                class="max-w-[120px] truncate font-semibold text-slate-800"
                            >
                                {{ user.name }}
                            </p>
                            <span
                                class="text-[10px] font-medium text-slate-500 capitalize"
                                >{{ user.role_label }}</span
                            >
                        </div>
                        <FalconIcon
                            name="chevron-down"
                            className="w-3.5 h-3.5 text-slate-400 hidden xl:block"
                        />
                    </button>

                    <!-- User Menu Dropdown -->
                    <div
                        v-if="isUserMenuOpen"
                        class="absolute right-0 z-50 mt-2 w-56 rounded-xl border border-slate-200 bg-white py-1.5 text-xs shadow-xl"
                        @click.stop
                    >
                        <div class="border-b border-slate-100 px-4 py-2">
                            <p
                                class="truncate text-sm font-semibold text-slate-800"
                            >
                                {{ user.name }}
                            </p>
                            <p class="truncate text-[11px] text-slate-500">
                                {{ user.email }}
                            </p>
                            <span
                                class="mt-1.5 inline-block rounded-md border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold text-indigo-700"
                            >
                                {{ user.role_label }}
                            </span>
                        </div>

                        <div class="py-1">
                            <Link
                                href="/password/change"
                                class="flex items-center gap-2.5 px-4 py-2 text-slate-700 transition hover:bg-slate-50"
                                @click="isUserMenuOpen = false"
                            >
                                <FalconIcon
                                    name="key"
                                    className="w-4 h-4 text-slate-400"
                                />
                                <span>Ganti Kata Sandi</span>
                            </Link>

                            <Link
                                href="/logout"
                                method="post"
                                as="button"
                                class="flex w-full items-center gap-2.5 px-4 py-2 text-left text-rose-600 transition hover:bg-rose-50"
                                @click="isUserMenuOpen = false"
                            >
                                <FalconIcon
                                    name="logout"
                                    className="w-4 h-4 text-rose-500"
                                />
                                <span>Keluar</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Body Wrapper -->
        <div class="flex flex-1 overflow-hidden">
            <!-- Mobile Sidebar Backdrop -->
            <div
                v-if="isMobileSidebarOpen"
                class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-xs transition-opacity lg:hidden"
                @click="isMobileSidebarOpen = false"
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
                    <div
                        class="flex items-center gap-2 font-bold text-slate-800"
                    >
                        <FalconIcon
                            name="falcon"
                            className="w-5 h-5 text-indigo-600"
                        />
                        <span>Menu Navigasi</span>
                    </div>
                    <button
                        type="button"
                        class="rounded-md p-1 text-slate-500 hover:bg-slate-100"
                        @click="isMobileSidebarOpen = false"
                    >
                        <FalconIcon name="x" className="w-5 h-5" />
                    </button>
                </div>

                <!-- Big Google Drive "+ Baru" (+ New) Button -->
                <div class="relative mb-5">
                    <button
                        type="button"
                        class="flex w-full items-center justify-center gap-2.5 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 hover:shadow-md active:scale-98"
                        @click.stop="isNewMenuOpen = !isNewMenuOpen"
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

                    <!-- + New Dropdown Menu -->
                    <div
                        v-if="isNewMenuOpen"
                        class="absolute right-0 left-0 z-50 mt-2 rounded-xl border border-slate-200 bg-white py-1.5 text-xs shadow-xl"
                        @click.stop
                    >
                        <button
                            type="button"
                            class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-slate-700 transition hover:bg-slate-50"
                        >
                            <span
                                class="h-2 w-2 rounded-full bg-blue-500"
                            ></span>
                            <span>Pengumuman</span>
                        </button>
                        <button
                            type="button"
                            class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-slate-700 transition hover:bg-slate-50"
                        >
                            <span
                                class="h-2 w-2 rounded-full bg-emerald-500"
                            ></span>
                            <span>Materi (Berkas)</span>
                        </button>
                        <button
                            type="button"
                            class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-slate-700 transition hover:bg-slate-50"
                        >
                            <span
                                class="h-2 w-2 rounded-full bg-amber-500"
                            ></span>
                            <span>Tugas</span>
                        </button>
                        <button
                            type="button"
                            class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-slate-700 transition hover:bg-slate-50"
                        >
                            <span
                                class="h-2 w-2 rounded-full bg-purple-500"
                            ></span>
                            <span>Kuis (Falcon Forms)</span>
                        </button>
                        <div class="my-1 border-t border-slate-100"></div>
                        <button
                            type="button"
                            class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-slate-700 transition hover:bg-slate-50"
                        >
                            <FalconIcon
                                name="folder"
                                className="w-3.5 h-3.5 text-slate-400"
                            />
                            <span>Topik Baru</span>
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
                        @click="isMobileSidebarOpen = false"
                    >
                        <FalconIcon
                            name="home"
                            className="w-5 h-5 text-indigo-600"
                        />
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
                        @click="isMobileSidebarOpen = false"
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
                        @click="isMobileSidebarOpen = false"
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
                        @click="isMobileSidebarOpen = false"
                    >
                        <FalconIcon
                            name="tasks"
                            className="w-5 h-5 text-indigo-600"
                        />
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
                        @click="isMobileSidebarOpen = false"
                    >
                        <FalconIcon
                            name="grades"
                            className="w-5 h-5 text-indigo-600"
                        />
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
                        @click="isMobileSidebarOpen = false"
                    >
                        <FalconIcon
                            name="trash"
                            className="w-5 h-5 text-slate-500"
                        />
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
                            @click="isMobileSidebarOpen = false"
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
                    <span
                        class="inline-flex items-center gap-1 text-emerald-600"
                    >
                        <span
                            class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                        ></span>
                        Lokal
                    </span>
                </div>
            </aside>

            <!-- Main Content Area -->
            <main
                class="flex min-w-0 flex-1 flex-col overflow-y-auto border-t border-slate-200 bg-white lg:rounded-tl-2xl lg:border-l"
            >
                <!-- Flash Notification Banner -->
                <div
                    v-if="flash?.success"
                    class="m-4 mb-0 flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-xs text-emerald-800 sm:text-sm"
                >
                    <FalconIcon
                        name="check"
                        className="w-4 h-4 text-emerald-600 shrink-0"
                    />
                    <span>{{ flash.success }}</span>
                </div>

                <div
                    v-if="flash?.error"
                    class="m-4 mb-0 flex items-center gap-2.5 rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-xs text-rose-800 sm:text-sm"
                >
                    <FalconIcon
                        name="alert"
                        className="w-4 h-4 text-rose-600 shrink-0"
                    />
                    <span>{{ flash.error }}</span>
                </div>

                <!-- Page Header with View Toggle Slot -->
                <div
                    class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 pt-5 pb-3 sm:px-8"
                >
                    <div>
                        <slot name="header">
                            <h2
                                class="text-lg font-bold text-slate-900 sm:text-xl"
                            >
                                {{ title || 'Falcon LMS' }}
                            </h2>
                        </slot>
                    </div>

                    <!-- View Mode Toggle: List vs Grid -->
                    <div
                        class="flex items-center gap-1 rounded-xl bg-slate-100 p-1"
                    >
                        <button
                            type="button"
                            :class="[
                                'rounded-lg p-1.5 transition',
                                viewMode === 'list'
                                    ? 'bg-white font-semibold text-indigo-600 shadow-2xs'
                                    : 'text-slate-500 hover:text-slate-800',
                            ]"
                            title="Tampilan Daftar (Default)"
                            @click="viewMode = 'list'"
                        >
                            <FalconIcon name="list" className="w-4 h-4" />
                        </button>
                        <button
                            type="button"
                            :class="[
                                'rounded-lg p-1.5 transition',
                                viewMode === 'grid'
                                    ? 'bg-white font-semibold text-indigo-600 shadow-2xs'
                                    : 'text-slate-500 hover:text-slate-800',
                            ]"
                            title="Tampilan Kisi"
                            @click="viewMode = 'grid'"
                        >
                            <FalconIcon name="grid" className="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Page Body Content -->
                <div class="flex-1 p-4 sm:p-8">
                    <slot :viewMode="viewMode" />
                </div>
            </main>
        </div>
    </div>
</template>
