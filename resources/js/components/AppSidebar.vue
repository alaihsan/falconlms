<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    CircleCheckBig,
    FolderOpen,
    House,
    Library,
    Trash2,
    Users,
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
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { home, todo } from '@/routes';
import { index as users } from '@/routes/admin/users';
import { show as courseShow } from '@/routes/courses';
import { show as library } from '@/routes/library';
import type { NavItem, Role } from '@/types';

const page = usePage();
const role = computed<Role | undefined>(() => page.props.auth.user?.role);
const courses = computed(() => page.props.nav?.courses ?? []);
const { isCurrentOrParentUrl } = useCurrentUrl();

const isTeacher = computed(() => role.value === 'teacher');

const mainNavItems = computed<NavItem[]>(() => [
    { title: 'Beranda', href: home(), icon: House },
    ...(isTeacher.value
        ? [{ title: 'Pustaka Saya', href: library(), icon: FolderOpen }]
        : []),
    ...(isTeacher.value || role.value === 'admin'
        ? [{ title: 'Pustaka Sekolah', icon: Library }]
        : []),
]);

const secondaryNavItems = computed<NavItem[]>(() => [
    ...(role.value !== 'admin'
        ? [{ title: 'To-do', href: todo(), icon: CircleCheckBig }]
        : []),
    { title: 'Kalender', icon: CalendarDays },
    ...(isTeacher.value ? [{ title: 'Sampah', icon: Trash2 }] : []),
    ...(role.value === 'admin'
        ? [{ title: 'Pengguna', href: users(), icon: Users }]
        : []),
]);

const colors = [
    'bg-blue-600',
    'bg-emerald-600',
    'bg-amber-600',
    'bg-rose-600',
    'bg-violet-600',
    'bg-cyan-600',
];
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

            <SidebarGroup class="py-0">
                <SidebarGroupLabel>Kelas</SidebarGroupLabel>
                <p
                    v-if="courses.length === 0"
                    class="px-2 text-xs text-sidebar-foreground/60 group-data-[collapsible=icon]:hidden"
                >
                    Belum ada kelas.
                </p>
                <SidebarMenu>
                    <SidebarMenuItem
                        v-for="(course, index) in courses"
                        :key="course.id"
                    >
                        <SidebarMenuButton
                            as-child
                            :is-active="
                                isCurrentOrParentUrl(courseShow(course.id))
                            "
                            :tooltip="course.name"
                        >
                            <Link :href="courseShow(course.id)">
                                <span
                                    :class="[
                                        colors[index % colors.length],
                                        'flex size-4 shrink-0 items-center justify-center rounded text-[10px] font-semibold text-white',
                                    ]"
                                    >{{ course.name.charAt(0) }}</span
                                >
                                <span class="truncate">{{ course.name }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>

            <NavMain :items="secondaryNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
