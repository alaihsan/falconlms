<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Megaphone, Trash2 } from '@lucide/vue';
import PostController from '@/actions/App/Http/Controllers/Courses/PostController';
import CourseHeader from '@/components/content/CourseHeader.vue';
import ItemIcon from '@/components/content/ItemIcon.vue';
import { useNewActions } from '@/composables/useNewActions';
import { formatDateTime } from '@/lib/format';
import { show as itemShow } from '@/routes/items';
import type {
    CourseHeader as Course,
    CreationContext,
    ItemSummary,
    ItemType,
} from '@/types';

type FeedEntry =
    | {
          kind: 'post';
          id: number;
          body: string;
          author: string | null;
          date: string;
          canDelete: boolean;
      }
    | {
          kind: 'item';
          id: number;
          type: ItemType;
          title: string;
          author: string | null;
          date: string;
      };

defineProps<{
    course: Course;
    context: CreationContext;
    feed: FeedEntry[];
    upcoming: ItemSummary[];
}>();

const { run } = useNewActions();

function deletePost(id: number): void {
    if (confirm('Hapus pengumuman ini?')) {
        router.delete(PostController.destroy.url(id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="course.name" />

    <CourseHeader :course="course" />

    <div
        class="mx-auto grid w-full max-w-5xl gap-6 p-6 md:grid-cols-[14rem_1fr]"
    >
        <aside class="grid content-start gap-2 rounded-2xl border p-4">
            <h2 class="text-sm font-medium">Tugas mendatang</h2>
            <p
                v-if="upcoming.length === 0"
                class="text-sm text-muted-foreground"
            >
                Tidak ada tugas yang segera jatuh tempo.
            </p>
            <Link
                v-for="item in upcoming"
                :key="item.id"
                :href="itemShow(item.id)"
                class="text-sm hover:underline"
            >
                <span class="block text-xs text-muted-foreground">{{
                    formatDateTime(item.dueAt)
                }}</span>
                {{ item.title }}
            </Link>
        </aside>

        <section class="grid content-start gap-3">
            <button
                v-if="context.allowed.includes('announcement')"
                type="button"
                class="flex items-center gap-3 rounded-2xl border p-4 text-left text-sm text-muted-foreground shadow-sm hover:bg-accent"
                data-test="announce"
                @click="run('announcement')"
            >
                <Megaphone class="size-5 text-primary" /> Umumkan sesuatu ke
                kelas
            </button>

            <p
                v-if="feed.length === 0"
                class="rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground"
            >
                Belum ada kabar di kelas ini.
            </p>

            <template v-for="entry in feed" :key="`${entry.kind}-${entry.id}`">
                <article
                    v-if="entry.kind === 'post'"
                    class="rounded-2xl border p-4"
                    data-test="post"
                >
                    <div class="mb-2 flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-medium">
                                {{ entry.author }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ formatDateTime(entry.date) }}
                            </p>
                        </div>
                        <button
                            v-if="entry.canDelete"
                            type="button"
                            class="rounded p-1 text-muted-foreground hover:bg-accent"
                            aria-label="Hapus pengumuman"
                            @click="deletePost(entry.id)"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </div>
                    <p class="text-sm whitespace-pre-line">{{ entry.body }}</p>
                </article>
                <Link
                    v-else
                    :href="itemShow(entry.id)"
                    class="flex items-center gap-3 rounded-2xl border p-4 hover:bg-accent"
                >
                    <ItemIcon :type="entry.type" />
                    <div class="min-w-0">
                        <p class="truncate text-sm">
                            {{ entry.author }} memposting
                            {{
                                entry.type === 'assignment' ? 'tugas' : 'materi'
                            }}
                            baru:
                            <span class="font-medium">{{ entry.title }}</span>
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ formatDateTime(entry.date) }}
                        </p>
                    </div>
                </Link>
            </template>
        </section>
    </div>
</template>
