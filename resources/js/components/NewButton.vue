<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import {
    BookOpenText,
    ClipboardList,
    FileUp,
    FolderPlus,
    FolderUp,
    Link2,
    ListChecks,
    Plus,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useSidebar } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import type { CreationContext, NewMenuItem } from '@/types';

const MENU: Record<NewMenuItem, { label: string; icon: LucideIcon }> = {
    folder: { label: 'Folder baru', icon: FolderPlus },
    upload: { label: 'Unggah file', icon: FileUp },
    upload_folder: { label: 'Unggah folder', icon: FolderUp },
    material: { label: 'Materi', icon: BookOpenText },
    assignment: { label: 'Tugas', icon: ClipboardList },
    quiz: { label: 'Kuis', icon: ListChecks },
    link: { label: 'Tautan', icon: Link2 },
};

const GROUPS: NewMenuItem[][] = [
    ['folder'],
    ['upload', 'upload_folder'],
    ['material', 'assignment', 'quiz', 'link'],
];

const { floating = false } = defineProps<{
    /** Drive-style floating button for phones, where the sidebar is hidden. */
    floating?: boolean;
}>();

const page = usePage<{ context?: CreationContext }>();
const context = computed(() => page.props.context ?? null);

const groups = computed(() =>
    GROUPS.map((group) =>
        group.filter((item) => context.value?.allowed.includes(item)),
    ).filter((group) => group.length > 0),
);

// Always show the user where new content will land, like a breadcrumb.
const destination = computed(() =>
    [
        context.value?.space.name,
        context.value?.folder?.name,
        context.value?.topic?.name,
    ]
        .filter(Boolean)
        .join(' › '),
);

const { state, isMobile } = useSidebar();
const collapsed = computed(
    () => !floating && state.value === 'collapsed' && !isMobile.value,
);
</script>

<template>
    <DropdownMenu v-if="groups.length > 0">
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                :data-test="floating ? 'new-button-floating' : 'new-button'"
                :class="
                    cn(
                        'flex items-center gap-3 rounded-2xl bg-background font-medium shadow-md ring-1 ring-sidebar-border transition-shadow outline-none hover:shadow-lg focus-visible:ring-2 focus-visible:ring-ring data-[state=open]:shadow-lg',
                        collapsed
                            ? 'size-8 justify-center rounded-lg'
                            : 'h-14 w-fit px-5 pr-6',
                        floating &&
                            'fixed right-5 bottom-5 z-20 shadow-lg md:hidden',
                    )
                "
            >
                <Plus
                    :class="cn('text-primary', collapsed ? 'size-5' : 'size-6')"
                />
                <span v-if="!collapsed">Baru</span>
                <span v-else class="sr-only">Baru</span>
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent
            :align="floating ? 'end' : 'start'"
            :side-offset="8"
            class="w-64"
        >
            <DropdownMenuLabel
                class="text-xs font-normal text-muted-foreground"
                data-test="new-destination"
            >
                Disimpan di
                <span class="font-medium text-foreground">{{
                    destination
                }}</span>
            </DropdownMenuLabel>
            <template v-for="group in groups" :key="group.join()">
                <DropdownMenuSeparator />
                <DropdownMenuGroup>
                    <!-- Saving content arrives in the next phase. -->
                    <DropdownMenuItem
                        v-for="item in group"
                        :key="item"
                        disabled
                    >
                        <component :is="MENU[item].icon" />
                        {{ MENU[item].label }}
                        <span class="ml-auto text-xs text-muted-foreground"
                            >Segera</span
                        >
                    </DropdownMenuItem>
                </DropdownMenuGroup>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
