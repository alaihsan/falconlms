<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { EllipsisVertical, GripVertical, Pencil, Trash2 } from '@lucide/vue';
import Sortable from 'sortablejs';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import ClassworkController from '@/actions/App/Http/Controllers/Courses/ClassworkController';
import TopicController from '@/actions/App/Http/Controllers/Courses/TopicController';
import CourseHeader from '@/components/content/CourseHeader.vue';
import DropZone from '@/components/content/DropZone.vue';
import ItemIcon from '@/components/content/ItemIcon.vue';
import FormDialog from '@/components/new/FormDialog.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { uploadFiles } from '@/composables/useNewActions';
import { formatDate, formatDateTime } from '@/lib/format';
import { classwork } from '@/routes/courses';
import { show as itemShow } from '@/routes/items';
import type {
    CourseHeader as Course,
    CreationContext,
    ItemSummary,
    Topic,
} from '@/types';

const props = defineProps<{
    course: Course;
    context: CreationContext;
    topics: Topic[];
    items: ItemSummary[];
    filterTopicId: number | null;
}>();

const canManage = computed(() => props.course.canManage);

// Local copies that drag & drop can reorder before the server confirms.
const topicOrder = ref<Topic[]>([]);
const groups = ref<Record<string, ItemSummary[]>>({});

function load(): void {
    topicOrder.value = [...props.topics];
    groups.value = { none: [] };

    for (const topic of props.topics) {
        groups.value[topic.id] = [];
    }

    for (const item of props.items) {
        (groups.value[item.topicId ?? 'none'] ??= []).push(item);
    }
}

load();

const visibleTopics = computed(() =>
    props.filterTopicId === null
        ? topicOrder.value
        : topicOrder.value.filter((topic) => topic.id === props.filterTopicId),
);

const sortables: Sortable[] = [];
const topicList = ref<HTMLElement | null>(null);

function save(): void {
    router.put(
        ClassworkController.reorder.url(props.course.id),
        {
            topics: topicOrder.value.map((topic) => topic.id),
            items: Object.fromEntries(
                Object.entries(groups.value).map(([key, list]) => [
                    key,
                    list.map((item) => item.id),
                ]),
            ),
        },
        { preserveScroll: true, preserveState: true },
    );
}

function setupSortables(): void {
    sortables.splice(0).forEach((sortable) => sortable.destroy());

    if (!canManage.value) {
        return;
    }

    document
        .querySelectorAll<HTMLElement>('[data-sortable-items]')
        .forEach((list) => {
            sortables.push(
                Sortable.create(list, {
                    group: 'classwork',
                    handle: '[data-drag-handle]',
                    animation: 150,
                    onEnd: (event) => {
                        const from = event.from.dataset.sortableItems!;
                        const to = event.to.dataset.sortableItems!;

                        // Undo Sortable's DOM move and let Vue render the new order instead.
                        event.from.insertBefore(
                            event.item,
                            event.from.children[event.oldIndex!] ?? null,
                        );

                        const [moved] = groups.value[from].splice(
                            event.oldIndex!,
                            1,
                        );
                        groups.value[to].splice(event.newIndex!, 0, {
                            ...moved,
                            topicId: to === 'none' ? null : Number(to),
                        });
                        save();
                    },
                }),
            );
        });

    if (topicList.value && props.filterTopicId === null) {
        sortables.push(
            Sortable.create(topicList.value, {
                handle: '[data-topic-handle]',
                animation: 150,
                onEnd: (event) => {
                    event.from.insertBefore(
                        event.item,
                        event.from.children[event.oldIndex!] ?? null,
                    );
                    const [moved] = topicOrder.value.splice(event.oldIndex!, 1);
                    topicOrder.value.splice(event.newIndex!, 0, moved);
                    save();
                },
            }),
        );
    }
}

onMounted(setupSortables);
onBeforeUnmount(() => sortables.forEach((sortable) => sortable.destroy()));

watch(
    () => [props.items, props.topics, props.filterTopicId],
    async () => {
        load();
        await nextTick();
        setupSortables();
    },
);

const renaming = ref<Topic | null>(null);

function deleteTopic(topic: Topic): void {
    if (
        confirm(
            `Hapus topik "${topic.name}"? Postingannya tetap ada, tanpa topik.`,
        )
    ) {
        router.delete(TopicController.destroy.url(topic.id));
    }
}

function dropInto(topicId: number | null, files: File[]): void {
    if (props.context.space) {
        uploadFiles(props.context.space, files, { topicId });
    }
}

function meta(item: ItemSummary): string {
    if (item.type === 'assignment') {
        return item.dueAt
            ? `Tenggat ${formatDateTime(item.dueAt)}`
            : 'Tanpa tenggat';
    }

    return item.isPublished
        ? `Diposting ${formatDate(item.publishedAt)}`
        : `Dibuat ${formatDate(item.createdAt)}`;
}
</script>

<template>
    <Head :title="`Tugas Kelas ${course.name}`" />

    <CourseHeader :course="course" />

    <div
        class="mx-auto grid w-full max-w-5xl gap-6 p-6 md:grid-cols-[12rem_1fr]"
    >
        <nav class="grid content-start gap-1 text-sm" aria-label="Topik">
            <Link
                :href="classwork(course.id)"
                :class="[
                    'rounded-full px-3 py-1.5',
                    filterTopicId === null
                        ? 'bg-accent font-medium text-accent-foreground'
                        : 'hover:bg-muted',
                ]"
                >Semua topik</Link
            >
            <Link
                v-for="topic in topicOrder"
                :key="topic.id"
                :href="classwork(course.id, { query: { topik: topic.id } })"
                :class="[
                    'truncate rounded-full px-3 py-1.5',
                    filterTopicId === topic.id
                        ? 'bg-accent font-medium text-accent-foreground'
                        : 'hover:bg-muted',
                ]"
                data-test="topic-filter"
                >{{ topic.name }}</Link
            >
        </nav>

        <div class="grid content-start gap-8">
            <p
                v-if="items.length === 0 && topics.length === 0"
                class="rounded-2xl border border-dashed p-10 text-center text-sm text-muted-foreground"
            >
                <template v-if="canManage">
                    Belum ada tugas kelas. Klik
                    <span class="font-medium text-foreground">+ Baru</span>
                    untuk membuat materi, tugas, atau topik, atau salin dari
                    Pustaka Saya.
                </template>
                <template v-else>Guru belum memposting apa pun.</template>
            </p>

            <!-- Posts without a topic stay at the top, as in Google Classroom. -->
            <DropZone
                v-if="
                    filterTopicId === null &&
                    (groups.none.length > 0 || canManage)
                "
                :disabled="!canManage"
                label="Lepaskan untuk membuat materi"
                @files="dropInto(null, $event)"
            >
                <ul data-sortable-items="none" class="grid min-h-2 gap-1">
                    <li v-for="item in groups.none" :key="item.id">
                        <div
                            class="group flex items-center gap-3 rounded-xl px-2 py-2 hover:bg-muted"
                        >
                            <GripVertical
                                v-if="canManage"
                                data-drag-handle
                                class="size-4 shrink-0 cursor-grab text-muted-foreground opacity-0 group-hover:opacity-100"
                            />
                            <ItemIcon :type="item.type" />
                            <Link
                                :href="itemShow(item.id)"
                                class="min-w-0 flex-1"
                                data-test="classwork-item"
                            >
                                <p class="truncate text-sm font-medium">
                                    {{ item.title }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ meta(item) }}
                                </p>
                            </Link>
                            <span
                                v-if="!item.isPublished"
                                class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800"
                                >Draf</span
                            >
                        </div>
                    </li>
                </ul>
            </DropZone>

            <div ref="topicList" class="grid gap-8">
                <DropZone
                    v-for="topic in visibleTopics"
                    :key="topic.id"
                    :disabled="!canManage"
                    label="Lepaskan untuk membuat materi di topik ini"
                    @files="dropInto(topic.id, $event)"
                >
                    <section class="grid gap-2" data-test="topic">
                        <div class="flex items-center gap-2 border-b pb-2">
                            <GripVertical
                                v-if="canManage && filterTopicId === null"
                                data-topic-handle
                                class="size-4 cursor-grab text-muted-foreground"
                            />
                            <h2
                                class="flex-1 truncate text-xl font-medium text-primary"
                            >
                                {{ topic.name }}
                            </h2>
                            <DropdownMenu v-if="canManage">
                                <DropdownMenuTrigger
                                    class="rounded-full p-1 text-muted-foreground hover:bg-accent"
                                    :aria-label="`Menu topik ${topic.name}`"
                                >
                                    <EllipsisVertical class="size-4" />
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem @select="renaming = topic"
                                        ><Pencil /> Ganti nama</DropdownMenuItem
                                    >
                                    <DropdownMenuItem
                                        variant="destructive"
                                        @select="deleteTopic(topic)"
                                        ><Trash2 /> Hapus</DropdownMenuItem
                                    >
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                        <ul
                            :data-sortable-items="topic.id"
                            class="grid min-h-8 gap-1"
                        >
                            <li
                                v-for="item in groups[topic.id] ?? []"
                                :key="item.id"
                            >
                                <div
                                    class="group flex items-center gap-3 rounded-xl px-2 py-2 hover:bg-muted"
                                >
                                    <GripVertical
                                        v-if="canManage"
                                        data-drag-handle
                                        class="size-4 shrink-0 cursor-grab text-muted-foreground opacity-0 group-hover:opacity-100"
                                    />
                                    <ItemIcon :type="item.type" />
                                    <Link
                                        :href="itemShow(item.id)"
                                        class="min-w-0 flex-1"
                                        data-test="classwork-item"
                                    >
                                        <p class="truncate text-sm font-medium">
                                            {{ item.title }}
                                        </p>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ meta(item) }}
                                        </p>
                                    </Link>
                                    <span
                                        v-if="!item.isPublished"
                                        class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800"
                                        >Draf</span
                                    >
                                </div>
                            </li>
                        </ul>
                        <p
                            v-if="(groups[topic.id] ?? []).length === 0"
                            class="px-2 text-sm text-muted-foreground"
                        >
                            Belum ada postingan di topik ini.
                        </p>
                    </section>
                </DropZone>
            </div>
        </div>
    </div>

    <FormDialog
        :open="renaming !== null"
        title="Ganti nama topik"
        :fields="[{ name: 'name', label: 'Nama topik', required: true }]"
        :initial="{ name: renaming?.name ?? '' }"
        :action="renaming ? TopicController.update.url(renaming.id) : ''"
        :extra="{ _method: 'patch' }"
        submit-label="Simpan"
        @update:open="(open) => !open && (renaming = null)"
    />
</template>
