<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    CircleCheckBig,
    FolderOpen,
    House,
    Library,
    Trash2,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import NewButton from '@/components/NewButton.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { home } from '@/routes';
import type { NavItem, Role } from '@/types';

const page = usePage();
const role = computed<Role | undefined>(() => page.props.auth.user?.role);

const isTeacher = computed(() => role.value === 'teacher');
const canSeeSchoolLibrary = computed(
    () => role.value === 'teacher' || role.value === 'admin',
);

const mainNavItems = computed<NavItem[]>(() => [
    { title: 'Beranda', href: home(), icon: House },
    ...(isTeacher.value ? [{ title: 'Pustaka Saya', icon: FolderOpen }] : []),
    ...(canSeeSchoolLibrary.value
        ? [{ title: 'Pustaka Sekolah', icon: Library }]
        : []),
]);

const secondaryNavItems = computed<NavItem[]>(() => [
    { title: 'To-do', icon: CircleCheckBig },
    { title: 'Kalender', icon: CalendarDays },
    ...(isTeacher.value ? [{ title: 'Sampah', icon: Trash2 }] : []),
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="home()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <div class="px-1 pt-2 pb-1">
                <NewButton />
            </div>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />

            <SidebarGroup class="py-0 group-data-[collapsible=icon]:hidden">
                <SidebarGroupLabel>Kelas</SidebarGroupLabel>
                <p class="px-2 text-xs text-sidebar-foreground/60">
                    Belum ada kelas.
                </p>
            </SidebarGroup>

            <NavMain :items="secondaryNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
