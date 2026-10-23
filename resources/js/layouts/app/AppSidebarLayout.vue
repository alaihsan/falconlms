<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import NewActions from '@/components/new/NewActions.vue';
import NewButton from '@/components/NewButton.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

// Pages with dynamic paths (folders, classes) send their breadcrumbs as a prop.
const page = usePage();
const crumbs = computed(() =>
    props.breadcrumbs.length > 0
        ? props.breadcrumbs
        : (page.props.breadcrumbs ?? []),
);
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="min-w-0 overflow-x-clip">
            <AppSidebarHeader :breadcrumbs="crumbs" />
            <slot />
            <NewButton floating />
        </AppContent>
        <NewActions />
        <Toaster rich-colors />
    </AppShell>
</template>
