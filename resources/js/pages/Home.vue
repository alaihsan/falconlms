<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { GraduationCap, LogIn, Upload, UserPlus, Users } from '@lucide/vue';
import { computed } from 'vue';
import ItemIcon from '@/components/content/ItemIcon.vue';
import { Button } from '@/components/ui/button';
import { useNewActions } from '@/composables/useNewActions';
import { formatDate } from '@/lib/format';
import { home } from '@/routes';
import { index as users } from '@/routes/admin/users';
import { show as courseShow } from '@/routes/courses';
import { show as itemShow } from '@/routes/items';
import { show as library } from '@/routes/library';
import type { CourseSummary, CreationContext, ItemSummary } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Beranda', href: home() }],
    },
});

defineProps<{
    context: CreationContext;
    courses: (CourseSummary & { role: 'teacher' | 'student' | null })[];
    recent?: ItemSummary[];
    stats?: { teachers: number; students: number; courses: number };
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const { run } = useNewActions();

const banners = [
    'from-blue-600 to-blue-500',
    'from-emerald-600 to-emerald-500',
    'from-amber-600 to-amber-500',
    'from-rose-600 to-rose-500',
    'from-violet-600 to-violet-500',
    'from-cyan-600 to-cyan-500',
];
</script>

<template>
    <Head title="Beranda" />

    <div class="flex flex-1 flex-col gap-8 p-6">
        <div>
            <h1 class="text-2xl font-semibold">
                Selamat datang, {{ user?.name }}
            </h1>
            <p class="text-sm text-muted-foreground">{{ user?.role_label }}</p>
        </div>

        <!-- Administrators -->
        <section v-if="stats" class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border p-5">
                <p class="text-sm text-muted-foreground">Guru</p>
                <p class="text-3xl font-semibold">{{ stats.teachers }}</p>
            </div>
            <div class="rounded-2xl border p-5">
                <p class="text-sm text-muted-foreground">Siswa</p>
                <p class="text-3xl font-semibold">{{ stats.students }}</p>
            </div>
            <div class="rounded-2xl border p-5">
                <p class="text-sm text-muted-foreground">Kelas</p>
                <p class="text-3xl font-semibold">{{ stats.courses }}</p>
            </div>
            <div class="flex flex-wrap gap-2 sm:col-span-3">
                <Button as-child variant="outline">
                    <Link :href="users()"><Users /> Kelola pengguna</Link>
                </Button>
                <Button variant="outline" @click="run('user')">
                    <UserPlus /> Pengguna baru
                </Button>
                <Button variant="outline" @click="run('import_users')">
                    <Upload /> Impor CSV
                </Button>
            </div>
        </section>

        <!-- Classes -->
        <section class="grid gap-3">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-medium">Kelas</h2>
                <Button
                    v-if="user?.role === 'student'"
                    variant="outline"
                    size="sm"
                    data-test="join-course"
                    @click="run('join_course')"
                >
                    <LogIn /> Gabung kelas
                </Button>
            </div>

            <div
                v-if="courses.length > 0"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <Link
                    v-for="(course, index) in courses"
                    :key="course.id"
                    :href="courseShow(course.id)"
                    class="overflow-hidden rounded-2xl border transition-shadow hover:shadow-md"
                >
                    <div
                        :class="[
                            banners[index % banners.length],
                            'bg-gradient-to-br p-4 text-white',
                        ]"
                    >
                        <p class="truncate text-lg font-medium">
                            {{ course.name }}
                        </p>
                        <p class="truncate text-sm text-white/80">
                            {{ course.section ?? course.subject ?? '\u00a0' }}
                        </p>
                    </div>
                    <p class="px-4 py-3 text-xs text-muted-foreground">
                        {{
                            course.role === 'teacher'
                                ? 'Anda mengajar'
                                : 'Anda siswa'
                        }}
                    </p>
                </Link>
            </div>
            <div
                v-else
                class="flex flex-col items-center gap-2 rounded-2xl border border-dashed p-10 text-center"
            >
                <GraduationCap class="size-10 text-primary/70" />
                <p class="text-sm text-muted-foreground">
                    <template v-if="user?.role === 'student'">
                        Belum ada kelas. Minta kode kelas kepada guru, lalu klik
                        <span class="font-medium text-foreground"
                            >Gabung kelas</span
                        >.
                    </template>
                    <template v-else>
                        Belum ada kelas. Buat lewat
                        <span class="font-medium text-foreground"
                            >+ Baru › Kelas baru</span
                        >.
                    </template>
                </p>
            </div>
        </section>

        <!-- Teachers: latest library content -->
        <section v-if="recent" class="grid gap-3">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-medium">Terbaru di Pustaka Saya</h2>
                <Link
                    :href="library()"
                    class="text-sm font-medium text-primary hover:underline"
                    >Buka Pustaka</Link
                >
            </div>
            <div
                v-if="recent.length > 0"
                class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
            >
                <Link
                    v-for="item in recent"
                    :key="item.id"
                    :href="itemShow(item.id)"
                    class="flex items-center gap-3 rounded-xl border p-3 hover:bg-accent"
                >
                    <ItemIcon :type="item.type" :preview="item.file?.preview" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium">
                            {{ item.title }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ formatDate(item.createdAt) }}
                        </p>
                    </div>
                </Link>
            </div>
            <p
                v-else
                class="rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground"
            >
                Klik <span class="font-medium text-foreground">+ Baru</span>
                untuk menambah folder, file, atau materi. Semua yang dibuat dari
                Beranda tersimpan di
                <span class="font-medium text-foreground">{{
                    context.space?.name
                }}</span
                >.
            </p>
        </section>
    </div>
</template>
