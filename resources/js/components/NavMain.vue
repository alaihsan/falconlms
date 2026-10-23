<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
    label?: string;
}>();

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

// Sections such as Pustaka Saya stay highlighted inside their sub-pages.
const isActive = (href: NonNullable<NavItem['href']>) =>
    isCurrentUrl(href) || (toUrl(href) !== '/' && isCurrentOrParentUrl(href));
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel v-if="label">{{ label }}</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    v-if="item.href"
                    as-child
                    :is-active="isActive(item.href)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
                <template v-else>
                    <SidebarMenuButton
                        aria-disabled="true"
                        class="cursor-default text-sidebar-foreground/50 hover:bg-transparent hover:text-sidebar-foreground/50"
                        :tooltip="`${item.title} (segera hadir)`"
                    >
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </SidebarMenuButton>
                    <SidebarMenuBadge class="text-sidebar-foreground/50"
                        >Segera</SidebarMenuBadge
                    >
                </template>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
