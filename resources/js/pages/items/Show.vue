<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ExternalLink, Pencil, Send, Trash2, Users } from '@lucide/vue';
import ItemController from '@/actions/App/Http/Controllers/Content/ItemController';
import AttachmentList from '@/components/content/AttachmentList.vue';
import ItemIcon from '@/components/content/ItemIcon.vue';
import SubmissionPanel from '@/components/content/SubmissionPanel.vue';
import type { StudentSubmission } from '@/components/content/SubmissionPanel.vue';
import { Button } from '@/components/ui/button';
import { formatDateTime, isPast } from '@/lib/format';
import { index as submissionsIndex } from '@/routes/submissions';
import type { Attachment, ItemSummary, SpaceRef } from '@/types';

const props = defineProps<{
    item: ItemSummary & {
        body: string | null;
        maxPoints: number | null;
        creator: string | null;
        topic: string | null;
        attachments: Attachment[];
    };
    space: SpaceRef;
    can: { update: boolean; submit: boolean };
    submission?: StudentSubmission | null;
    submissionStats?: { students: number; turnedIn: number };
}>();

function destroy(): void {
    if (confirm(`Hapus "${props.item.title}"?`)) {
        router.delete(ItemController.destroy.url(props.item.id));
    }
}
</script>

<template>
    <Head :title="item.title" />

    <div
        class="mx-auto grid w-full max-w-5xl gap-6 p-6 lg:grid-cols-[1fr_20rem]"
    >
        <article class="grid content-start gap-5">
            <header class="flex gap-4 border-b pb-4">
                <ItemIcon :type="item.type" :preview="item.file?.preview" />
                <div class="min-w-0 flex-1">
                    <h1 class="text-2xl font-semibold break-words">
                        {{ item.title }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        <span v-if="item.creator">{{ item.creator }} · </span>
                        {{ formatDateTime(item.publishedAt ?? item.createdAt) }}
                        <span v-if="item.topic"> · {{ item.topic }}</span>
                    </p>
                    <div
                        v-if="item.type === 'assignment'"
                        class="mt-2 flex flex-wrap gap-4 text-sm"
                    >
                        <span v-if="item.maxPoints !== null"
                            >{{ item.maxPoints }} poin</span
                        >
                        <span
                            v-if="item.dueAt"
                            :class="
                                isPast(item.dueAt) ? 'text-destructive' : ''
                            "
                        >
                            Tenggat {{ formatDateTime(item.dueAt) }}
                        </span>
                        <span v-else class="text-muted-foreground"
                            >Tanpa tenggat</span
                        >
                    </div>
                    <p
                        v-if="!item.isPublished"
                        class="mt-2 inline-block rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800"
                    >
                        Draf, belum terlihat oleh siswa
                    </p>
                </div>
            </header>

            <div v-if="can.update" class="flex flex-wrap gap-2">
                <Button
                    v-if="!item.isPublished"
                    size="sm"
                    data-test="publish"
                    @click="router.post(ItemController.publish.url(item.id))"
                >
                    <Send /> Posting
                </Button>
                <Button
                    v-if="submissionStats"
                    as-child
                    size="sm"
                    variant="outline"
                >
                    <Link :href="submissionsIndex(item.id)">
                        <Users /> Jawaban siswa ({{
                            submissionStats.turnedIn
                        }}/{{ submissionStats.students }})
                    </Link>
                </Button>
                <Button as-child size="sm" variant="outline">
                    <Link :href="ItemController.edit.url(item.id)"
                        ><Pencil /> Ubah</Link
                    >
                </Button>
                <Button
                    size="sm"
                    variant="ghost"
                    class="text-destructive"
                    @click="destroy"
                >
                    <Trash2 /> Hapus
                </Button>
            </div>

            <a
                v-if="item.type === 'link' && item.url"
                :href="item.url"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center gap-2 rounded-xl border p-4 text-sm font-medium text-primary hover:bg-accent"
            >
                <ExternalLink class="size-4" /> {{ item.url }}
            </a>

            <!-- Sanitized on the server by App\Support\RichText. -->
            <div v-if="item.body" class="prose-falcon" v-html="item.body" />

            <AttachmentList
                v-if="item.attachments.length > 0"
                :attachments="item.attachments"
                previews
            />
        </article>

        <SubmissionPanel
            v-if="can.submit"
            :item-id="item.id"
            :due-at="item.dueAt"
            :max-points="item.maxPoints"
            :submission="submission ?? null"
        />
    </div>
</template>
