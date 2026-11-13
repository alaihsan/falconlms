<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import TopHeader from '@/components/layout/TopHeader.vue';
import SidebarNav from '@/components/layout/SidebarNav.vue';
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
        <TopHeader
            :user="user"
            :is-user-menu-open="isUserMenuOpen"
            @toggle-mobile-sidebar="isMobileSidebarOpen = !isMobileSidebarOpen"
            @toggle-user-menu="isUserMenuOpen = !isUserMenuOpen"
            @close-user-menu="isUserMenuOpen = false"
        />

        <!-- Main Body Wrapper -->
        <div class="flex flex-1 overflow-hidden">
            <SidebarNav
                :user="user"
                :current-url="currentUrl"
                :is-mobile-sidebar-open="isMobileSidebarOpen"
                :is-new-menu-open="isNewMenuOpen"
                @close-mobile-sidebar="isMobileSidebarOpen = false"
                @toggle-new-menu="isNewMenuOpen = !isNewMenuOpen"
            />

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
