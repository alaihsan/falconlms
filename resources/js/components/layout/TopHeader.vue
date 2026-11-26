<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import type { User } from '@/types';

defineProps<{
    user: User | null;
    isUserMenuOpen: boolean;
}>();

const emit = defineEmits<{
    (e: 'toggle-mobile-sidebar'): void;
    (e: 'toggle-user-menu'): void;
    (e: 'close-user-menu'): void;
}>();

const globalSearch = ref('');

function submitGlobalSearch(): void {
    router.get('/pustaka', {
        search: globalSearch.value || undefined,
    });
}
</script>

<template>
    <header
        class="sticky top-0 z-30 flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-3 py-2.5 shadow-xs sm:px-6"
    >
        <!-- Left: Mobile Menu Toggle & Brand -->
        <div class="flex shrink-0 items-center gap-2 sm:gap-4">
            <button
                type="button"
                class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-hidden lg:hidden"
                aria-label="Buka Menu"
                @click.stop="emit('toggle-mobile-sidebar')"
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
        <form
            class="mx-1 max-w-2xl flex-1 sm:mx-4"
            @submit.prevent="submitGlobalSearch"
        >
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
                    v-model="globalSearch"
                    type="text"
                    placeholder="Telusuri judul, materi, tugas, kelas, atau mapel..."
                    class="w-full rounded-full border border-transparent bg-slate-100 py-2 pr-24 pl-10 text-xs shadow-inner transition placeholder:text-slate-400 hover:bg-slate-200/70 focus:border-indigo-500 focus:bg-white focus:ring-3 focus:ring-indigo-100 focus:outline-hidden sm:py-2.5 sm:text-sm"
                />
                <div
                    class="absolute inset-y-0 right-1.5 hidden items-center sm:flex"
                >
                    <button
                        type="submit"
                        class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-500 shadow-2xs hover:text-indigo-600"
                    >
                        Semua
                    </button>
                </div>
            </div>
        </form>

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
                    @click.stop="emit('toggle-user-menu')"
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
                            @click="emit('close-user-menu')"
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
                            @click="emit('close-user-menu')"
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
</template>
