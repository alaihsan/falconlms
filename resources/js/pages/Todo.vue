<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CircleCheckBig } from '@lucide/vue';
import { formatDateTime } from '@/lib/format';
import { index as submissionsIndex } from '@/routes/submissions';
import { show as itemShow } from '@/routes/items';

type Entry = {
    id: number;
    title: string;
    course: string;
    dueAt: string | null;
    status: string;
    score: number | null;
};

defineProps<{
    groups: { assigned: Entry[]; missing: Entry[]; done: Entry[] };
    toGrade: { id: number; title: string; course: string; waiting: number }[];
}>();

const sections = [
    {
        key: 'missing',
        title: 'Terlambat',
        empty: 'Tidak ada tugas yang terlambat.',
    },
    {
        key: 'assigned',
        title: 'Ditugaskan',
        empty: 'Tidak ada tugas yang perlu dikerjakan.',
    },
    {
        key: 'done',
        title: 'Selesai',
        empty: 'Belum ada tugas yang diserahkan.',
    },
] as const;
</script>

<template>
    <Head title="To-do" />

    <div class="mx-auto grid w-full max-w-3xl gap-8 p-6">
        <h1 class="text-2xl font-semibold">To-do</h1>

        <section v-if="toGrade.length > 0" class="grid gap-2">
            <h2 class="border-b pb-2 text-lg font-medium text-primary">
                Perlu dinilai
            </h2>
            <Link
                v-for="item in toGrade"
                :key="item.id"
                :href="submissionsIndex(item.id)"
                class="flex items-center justify-between gap-3 rounded-xl px-3 py-2 hover:bg-muted"
                data-test="to-grade"
            >
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium">{{ item.title }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ item.course }}
                    </p>
                </div>
                <span
                    class="rounded-full bg-primary px-2 py-0.5 text-xs font-medium text-primary-foreground"
                    >{{ item.waiting }} diserahkan</span
                >
            </Link>
        </section>

        <template
            v-if="
                groups.assigned.length +
                    groups.missing.length +
                    groups.done.length >
                0
            "
        >
            <section
                v-for="section in sections"
                :key="section.key"
                class="grid gap-2"
            >
                <h2
                    :class="[
                        'border-b pb-2 text-lg font-medium',
                        section.key === 'missing'
                            ? 'text-destructive'
                            : 'text-primary',
                    ]"
                >
                    {{ section.title }} ({{ groups[section.key].length }})
                </h2>
                <p
                    v-if="groups[section.key].length === 0"
                    class="px-3 text-sm text-muted-foreground"
                >
                    {{ section.empty }}
                </p>
                <Link
                    v-for="entry in groups[section.key]"
                    :key="entry.id"
                    :href="itemShow(entry.id)"
                    class="flex items-center justify-between gap-3 rounded-xl px-3 py-2 hover:bg-muted"
                    :data-test="`todo-${section.key}`"
                >
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium">
                            {{ entry.title }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ entry.course }}
                        </p>
                    </div>
                    <span class="shrink-0 text-xs text-muted-foreground">
                        <template v-if="entry.score !== null"
                            >Nilai {{ entry.score }}</template
                        >
                        <template v-else-if="entry.dueAt">{{
                            formatDateTime(entry.dueAt)
                        }}</template>
                        <template v-else>Tanpa tenggat</template>
                    </span>
                </Link>
            </section>
        </template>

        <div
            v-else-if="toGrade.length === 0"
            class="flex flex-col items-center gap-2 rounded-2xl border border-dashed p-10 text-center"
        >
            <CircleCheckBig class="size-10 text-primary/70" />
            <p class="text-sm text-muted-foreground">
                Tidak ada yang perlu dikerjakan saat ini.
            </p>
        </div>
    </div>
</template>
