<script setup lang="ts">
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';

type Props = {
    user: User;
    showDetails?: boolean;
};

withDefaults(defineProps<Props>(), {
    showDetails: false,
});

const { getInitials } = useInitials();
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
        <AvatarFallback class="rounded-lg text-black dark:text-white">
            {{ getInitials(user.name) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">{{ user.name }}</span>
        <span v-if="showDetails" class="truncate text-xs text-muted-foreground"
            >{{ user.username }} · {{ user.role_label }}</span
        >
    </div>
</template>
