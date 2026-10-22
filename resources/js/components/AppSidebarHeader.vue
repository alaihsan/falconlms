<script setup lang="ts">
import { Search } from '@lucide/vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
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

        <!-- Search arrives together with content in the next phase. -->
        <div
            class="ml-auto hidden w-full max-w-md items-center gap-2 rounded-full bg-muted px-4 py-2 text-sm text-muted-foreground md:flex"
            title="Pencarian hadir di tahap berikutnya"
            aria-disabled="true"
        >
            <Search class="size-4" />
            <span>Cari materi, kelas, tugas…</span>
        </div>
    </header>
</template>
