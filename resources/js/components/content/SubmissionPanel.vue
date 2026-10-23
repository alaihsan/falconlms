<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Paperclip } from '@lucide/vue';
import { computed, ref } from 'vue';
import SubmissionController from '@/actions/App/Http/Controllers/Submissions/SubmissionController';
import AttachmentList from '@/components/content/AttachmentList.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime, isPast } from '@/lib/format';
import type { Attachment } from '@/types';

export type StudentSubmission = {
    status: 'draft' | 'turned_in' | 'returned';
    body: string | null;
    turnedInAt: string | null;
    isLate: boolean;
    score: number | null;
    feedback: string | null;
    attachments: Attachment[];
};

const props = defineProps<{
    itemId: number;
    dueAt: string | null;
    maxPoints: number | null;
    submission: StudentSubmission | null;
}>();

const status = computed(() => props.submission?.status ?? 'assigned');
const editable = computed(
    () => status.value === 'assigned' || status.value === 'draft',
);
const label = computed(() => {
    switch (status.value) {
        case 'turned_in':
            return props.submission?.isLate
                ? 'Diserahkan terlambat'
                : 'Diserahkan';
        case 'returned':
            return 'Dikembalikan';
        default:
            return isPast(props.dueAt) ? 'Terlambat' : 'Ditugaskan';
    }
});

const turnIn = useForm({ body: props.submission?.body ?? '' });
const uploading = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

function addFiles(event: Event): void {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files ?? []);
    input.value = '';

    if (files.length === 0) {
        return;
    }

    router.post(
        SubmissionController.addFiles.url(props.itemId),
        { files },
        {
            forceFormData: true,
            preserveScroll: true,
            onStart: () => (uploading.value = true),
            onFinish: () => (uploading.value = false),
        },
    );
}

function removeFile(attachment: Attachment): void {
    router.delete(SubmissionController.removeFile.url(attachment.id), {
        preserveScroll: true,
    });
}

function unsubmit(): void {
    router.post(
        SubmissionController.unsubmit.url(props.itemId),
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <aside
        class="grid gap-4 rounded-2xl border p-5 shadow-sm"
        data-test="submission-panel"
    >
        <div class="flex items-center justify-between">
            <h2 class="font-medium">Tugas Anda</h2>
            <span
                :class="[
                    'text-sm',
                    label === 'Terlambat' || submission?.isLate
                        ? 'text-destructive'
                        : 'text-muted-foreground',
                ]"
                data-test="submission-status"
                >{{ label }}</span
            >
        </div>

        <div
            v-if="status === 'returned'"
            class="rounded-lg bg-accent p-3 text-sm"
        >
            <p class="font-medium" data-test="submission-score">
                Nilai: {{ submission?.score ?? '–'
                }}<template v-if="maxPoints">/{{ maxPoints }}</template>
            </p>
            <p
                v-if="submission?.feedback"
                class="mt-1 whitespace-pre-line text-accent-foreground"
            >
                {{ submission.feedback }}
            </p>
        </div>

        <AttachmentList
            v-if="(submission?.attachments.length ?? 0) > 0"
            :attachments="submission?.attachments ?? []"
            :removable="editable"
            @remove="removeFile"
        />

        <template v-if="editable">
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
                :disabled="uploading"
                @click="fileInput?.click()"
            >
                <Spinner v-if="uploading" /><Paperclip v-else /> Tambah file
            </Button>
            <Textarea
                v-model="turnIn.body"
                placeholder="Jawaban atau catatan untuk guru (opsional)"
                rows="3"
            />
            <InputError :message="turnIn.errors.body" />
            <Button
                :disabled="turnIn.processing"
                data-test="turn-in"
                @click="
                    turnIn.post(SubmissionController.turnIn.url(itemId), {
                        preserveScroll: true,
                    })
                "
            >
                <Spinner v-if="turnIn.processing" /> Serahkan
            </Button>
        </template>

        <template v-else>
            <p
                v-if="submission?.body"
                class="rounded-lg border p-3 text-sm whitespace-pre-line"
            >
                {{ submission.body }}
            </p>
            <p
                v-if="submission?.turnedInAt"
                class="text-xs text-muted-foreground"
            >
                Diserahkan {{ formatDateTime(submission.turnedInAt) }}
            </p>
            <Button
                v-if="status === 'turned_in'"
                variant="outline"
                data-test="unsubmit"
                @click="unsubmit"
            >
                Batalkan penyerahan
            </Button>
        </template>
    </aside>
</template>
