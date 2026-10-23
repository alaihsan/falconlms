<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Paperclip, Plus, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import ItemController from '@/actions/App/Http/Controllers/Content/ItemController';
import AttachmentList from '@/components/content/AttachmentList.vue';
import RichEditor from '@/components/content/RichEditor.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { Attachment, ItemType, SpaceRef, Topic } from '@/types';

const props = defineProps<{
    mode: 'create' | 'edit';
    type: ItemType;
    space: SpaceRef;
    folderId: number | null;
    topicId: number | null;
    topics: Topic[];
    item: {
        id: number;
        title: string;
        body: string | null;
        url: string | null;
        dueAt: string | null;
        maxPoints: number | null;
        isPublished: boolean;
        attachments: Attachment[];
    } | null;
    canAlsoSaveToLibrary: boolean;
    cancelUrl: string;
}>();

const isCourse = props.space.kind === 'course';
const typeLabel = {
    material: 'Materi',
    assignment: 'Tugas',
    link: 'Tautan',
    file: 'File',
}[props.type];

const form = useForm({
    type: props.mode === 'create' ? props.type : undefined,
    title: props.item?.title ?? '',
    body: props.item?.body ?? '',
    url: props.item?.url ?? '',
    due_at: props.item?.dueAt ?? '',
    max_points:
        props.item?.maxPoints?.toString() ??
        (props.type === 'assignment' ? '100' : ''),
    topic_id: isCourse ? props.topicId : undefined,
    folder_id:
        !isCourse && props.mode === 'create' ? props.folderId : undefined,
    files: [] as File[],
    links: [] as string[],
    remove_attachments: [] as number[],
    also_save_to_library: false,
    publish: false,
});

const existing = computed(() =>
    (props.item?.attachments ?? []).filter(
        (a) => !form.remove_attachments.includes(a.id),
    ),
);
const newLink = ref('');
const fileInput = ref<HTMLInputElement | null>(null);

function addFiles(event: Event): void {
    const input = event.target as HTMLInputElement;
    form.files.push(...Array.from(input.files ?? []));
    input.value = '';
}

function addLink(): void {
    const url = newLink.value.trim();

    if (url !== '') {
        form.links.push(url);
        newLink.value = '';
    }
}

function submit(publish: boolean): void {
    form.publish = publish;

    if (props.mode === 'create') {
        form.post(ItemController.store.url(props.space.id), {
            forceFormData: true,
        });
    } else if (props.item) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(
            ItemController.update.url(props.item.id),
            { forceFormData: true },
        );
    }
}
</script>

<template>
    <Head
        :title="
            mode === 'create'
                ? `${typeLabel} baru`
                : `Ubah ${typeLabel.toLowerCase()}`
        "
    />

    <form
        class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-6"
        @submit.prevent="submit(!isCourse || item?.isPublished === true)"
    >
        <div>
            <h1 class="text-2xl font-semibold">
                {{
                    mode === 'create'
                        ? `${typeLabel} baru`
                        : `Ubah ${typeLabel.toLowerCase()}`
                }}
            </h1>
            <p class="text-sm text-muted-foreground">
                Disimpan di {{ space.name }}
            </p>
        </div>

        <div class="grid gap-2">
            <Label for="title">Judul</Label>
            <Input
                id="title"
                v-model="form.title"
                required
                maxlength="200"
                v-focus
            />
            <InputError :message="form.errors.title" />
        </div>

        <div v-if="type === 'link'" class="grid gap-2">
            <Label for="url">Alamat</Label>
            <Input
                id="url"
                v-model="form.url"
                type="url"
                placeholder="http://"
                required
            />
            <InputError :message="form.errors.url" />
        </div>

        <template v-else>
            <div class="grid gap-2">
                <Label>{{
                    type === 'assignment' ? 'Petunjuk' : 'Isi materi'
                }}</Label>
                <RichEditor
                    v-model="form.body"
                    :placeholder="
                        type === 'assignment' ? 'Petunjuk' : 'Isi materi'
                    "
                />
                <InputError :message="form.errors.body" />
            </div>

            <div v-if="type === 'assignment'" class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="due_at">Tenggat</Label>
                    <Input
                        id="due_at"
                        v-model="form.due_at"
                        type="datetime-local"
                    />
                    <InputError :message="form.errors.due_at" />
                </div>
                <div class="grid gap-2">
                    <Label for="max_points">Poin maksimal</Label>
                    <Input
                        id="max_points"
                        v-model="form.max_points"
                        type="number"
                        min="0"
                        max="1000"
                    />
                    <InputError :message="form.errors.max_points" />
                </div>
            </div>

            <div v-if="isCourse" class="grid gap-2">
                <Label for="topic">Topik</Label>
                <select
                    id="topic"
                    v-model="form.topic_id"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                >
                    <option :value="null">Tanpa topik</option>
                    <option
                        v-for="topic in topics"
                        :key="topic.id"
                        :value="topic.id"
                    >
                        {{ topic.name }}
                    </option>
                </select>
                <InputError :message="form.errors.topic_id" />
            </div>

            <div class="grid gap-3">
                <Label>Lampiran</Label>
                <AttachmentList
                    v-if="existing.length > 0"
                    :attachments="existing"
                    removable
                    @remove="
                        (attachment) =>
                            form.remove_attachments.push(attachment.id)
                    "
                />
                <ul
                    v-if="form.files.length > 0 || form.links.length > 0"
                    class="grid gap-2"
                >
                    <li
                        v-for="(file, index) in form.files"
                        :key="`f-${index}`"
                        class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm"
                    >
                        <Paperclip class="size-4 text-muted-foreground" />
                        <span class="flex-1 truncate">{{ file.name }}</span>
                        <button
                            type="button"
                            class="text-muted-foreground hover:text-destructive"
                            :aria-label="`Hapus ${file.name}`"
                            @click="form.files.splice(index, 1)"
                        >
                            <X class="size-4" />
                        </button>
                    </li>
                    <li
                        v-for="(link, index) in form.links"
                        :key="`l-${index}`"
                        class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm"
                    >
                        <Paperclip class="size-4 text-muted-foreground" />
                        <span class="flex-1 truncate">{{ link }}</span>
                        <button
                            type="button"
                            class="text-muted-foreground hover:text-destructive"
                            aria-label="Hapus tautan"
                            @click="form.links.splice(index, 1)"
                        >
                            <X class="size-4" />
                        </button>
                    </li>
                </ul>
                <InputError
                    :message="
                        Object.entries(form.errors).find(
                            ([key]) =>
                                key.startsWith('files') ||
                                key.startsWith('links'),
                        )?.[1]
                    "
                />
                <div class="flex flex-wrap gap-2">
                    <input
                        ref="fileInput"
                        type="file"
                        multiple
                        class="hidden"
                        @change="addFiles"
                    />
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="fileInput?.click()"
                    >
                        <Paperclip /> Lampirkan file
                    </Button>
                    <div class="flex flex-1 gap-2">
                        <Input
                            v-model="newLink"
                            type="url"
                            placeholder="Tambah tautan http://"
                            class="h-8"
                            @keydown.enter.prevent="addLink"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="addLink"
                            ><Plus /> Tautan</Button
                        >
                    </div>
                </div>
            </div>

            <label
                v-if="canAlsoSaveToLibrary && mode === 'create'"
                class="flex items-center gap-2 text-sm"
            >
                <input
                    v-model="form.also_save_to_library"
                    type="checkbox"
                    class="size-4 accent-primary"
                />
                Simpan juga ke Pustaka Saya
            </label>
        </template>

        <div
            class="flex flex-wrap items-center justify-end gap-2 border-t pt-4"
        >
            <Button as-child variant="ghost">
                <Link :href="cancelUrl">Batal</Link>
            </Button>
            <template v-if="isCourse && !item?.isPublished">
                <Button
                    type="button"
                    variant="outline"
                    :disabled="form.processing"
                    data-test="save-draft"
                    @click="submit(false)"
                >
                    Simpan draf
                </Button>
                <Button
                    type="button"
                    :disabled="form.processing"
                    data-test="publish"
                    @click="submit(true)"
                >
                    <Spinner v-if="form.processing" /> Posting
                </Button>
            </template>
            <Button
                v-else
                type="submit"
                :disabled="form.processing"
                data-test="save"
            >
                <Spinner v-if="form.processing" /> Simpan
            </Button>
        </div>
    </form>
</template>
