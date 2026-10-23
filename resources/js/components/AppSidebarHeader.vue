<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell } from '@lucide/vue';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { index as notifications } from '@/routes/notifications';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const unread = computed(() => page.props.nav?.unreadNotifications ?? 0);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-4 border-b border-sidebar-border/70 px-6 md:px-4"
    >
        <div class="flex min-w-0 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <Link
            :href="notifications()"
            class="relative ml-auto flex size-9 shrink-0 items-center justify-center rounded-full text-muted-foreground hover:bg-accent hover:text-accent-foreground"
            :aria-label="`Notifikasi (${unread} belum dibaca)`"
            data-test="notifications-bell"
        >
            <Bell class="size-5" />
            <span
                v-if="unread > 0"
                class="absolute top-1 right-1 flex min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] leading-4 font-medium text-white"
                >{{ unread > 99 ? '99+' : unread }}</span
            >
        </Link>
    </header>
</template>
