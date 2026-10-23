<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Bell } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/format';
import { open, readAll } from '@/routes/notifications';

defineProps<{
    notifications: {
        id: string;
        title: string;
        body: string;
        isRead: boolean;
        date: string;
    }[];
}>();
</script>

<template>
    <Head title="Notifikasi" />

    <div class="mx-auto grid w-full max-w-2xl gap-4 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Notifikasi</h1>
            <Button
                v-if="notifications.some((n) => !n.isRead)"
                variant="outline"
                size="sm"
                @click="
                    router.post(readAll.url(), {}, { preserveScroll: true })
                "
                >Tandai semua dibaca</Button
            >
        </div>

        <div
            v-if="notifications.length === 0"
            class="flex flex-col items-center gap-2 rounded-2xl border border-dashed p-10 text-center"
        >
            <Bell class="size-10 text-primary/70" />
            <p class="text-sm text-muted-foreground">Belum ada notifikasi.</p>
        </div>

        <ul class="grid gap-1">
            <li v-for="notification in notifications" :key="notification.id">
                <Link
                    :href="open(notification.id)"
                    :class="[
                        'flex gap-3 rounded-xl px-3 py-3 hover:bg-muted',
                        notification.isRead ? '' : 'bg-accent/60',
                    ]"
                    data-test="notification"
                >
                    <span
                        :class="[
                            'mt-1.5 size-2 shrink-0 rounded-full',
                            notification.isRead
                                ? 'bg-transparent'
                                : 'bg-primary',
                        ]"
                    />
                    <div class="min-w-0">
                        <p class="truncate text-xs text-muted-foreground">
                            {{ notification.title }}
                        </p>
                        <p class="text-sm">{{ notification.body }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ formatDateTime(notification.date) }}
                        </p>
                    </div>
                </Link>
            </li>
        </ul>
    </div>
</template>
