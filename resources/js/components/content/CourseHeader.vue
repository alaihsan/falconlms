<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Copy, RefreshCw } from '@lucide/vue';
import { toast } from 'vue-sonner';
import CourseController from '@/actions/App/Http/Controllers/Courses/CourseController';
import { classwork, members, show } from '@/routes/courses';
import type { CourseHeader } from '@/types';

const props = defineProps<{ course: CourseHeader }>();
const page = usePage();

const tabs = [
    {
        label: 'Forum',
        href: show(props.course.id),
        component: 'courses/Stream',
    },
    {
        label: 'Tugas Kelas',
        href: classwork(props.course.id),
        component: 'courses/Classwork',
    },
    {
        label: 'Anggota',
        href: members(props.course.id),
        component: 'courses/Members',
    },
];

async function copyCode(): Promise<void> {
    try {
        await navigator.clipboard.writeText(props.course.joinCode ?? '');
        toast.success('Kode kelas disalin.');
    } catch {
        toast.info(`Kode kelas: ${props.course.joinCode}`);
    }
}

function regenerate(): void {
    if (confirm('Buat kode kelas baru? Kode lama tidak bisa dipakai lagi.')) {
        router.post(
            CourseController.regenerateCode.url(props.course.id),
            {},
            { preserveScroll: true },
        );
    }
}
</script>

<template>
    <div class="border-b">
        <div
            class="flex flex-wrap items-end justify-between gap-4 px-6 pt-6 pb-3"
        >
            <div class="min-w-0">
                <h1 class="truncate text-2xl font-semibold">
                    {{ course.name }}
                </h1>
                <p
                    v-if="course.section || course.subject"
                    class="text-sm text-muted-foreground"
                >
                    {{
                        [course.section, course.subject]
                            .filter(Boolean)
                            .join(' · ')
                    }}
                </p>
            </div>
            <div
                v-if="course.joinCode"
                class="flex items-center gap-1 rounded-lg border px-3 py-1.5 text-sm"
                data-test="join-code"
            >
                <span class="text-muted-foreground">Kode kelas</span>
                <span class="font-mono font-semibold tracking-wider">{{
                    course.joinCode
                }}</span>
                <button
                    type="button"
                    class="rounded p-1 text-muted-foreground hover:bg-accent"
                    aria-label="Salin kode kelas"
                    @click="copyCode"
                >
                    <Copy class="size-3.5" />
                </button>
                <button
                    type="button"
                    class="rounded p-1 text-muted-foreground hover:bg-accent"
                    aria-label="Buat kode baru"
                    @click="regenerate"
                >
                    <RefreshCw class="size-3.5" />
                </button>
            </div>
        </div>
        <nav class="flex gap-1 px-4">
            <Link
                v-for="tab in tabs"
                :key="tab.label"
                :href="tab.href"
                :class="[
                    'border-b-2 px-3 py-2 text-sm font-medium',
                    page.component === tab.component
                        ? 'border-primary text-primary'
                        : 'border-transparent text-muted-foreground hover:text-foreground',
                ]"
                >{{ tab.label }}</Link
            >
        </nav>
    </div>
</template>
