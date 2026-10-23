<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';
import GradingController from '@/actions/App/Http/Controllers/Submissions/GradingController';
import AttachmentList from '@/components/content/AttachmentList.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime } from '@/lib/format';
import type { Attachment, SubmissionStatus } from '@/types';

type StudentWork = {
    id: number;
    name: string;
    username: string;
    status: SubmissionStatus;
    turnedInAt: string | null;
    isLate: boolean;
    body: string | null;
    score: number | null;
    feedback: string | null;
    attachments: Attachment[];
};

const props = defineProps<{
    item: {
        id: number;
        title: string;
        maxPoints: number | null;
        dueAt: string | null;
    };
    students: StudentWork[];
}>();

const labels: Record<SubmissionStatus, string> = {
    assigned: 'Ditugaskan',
    draft: 'Ditugaskan',
    turned_in: 'Diserahkan',
    returned: 'Dikembalikan',
};

// One small form per student, so each row saves on its own.
const forms = reactive(
    Object.fromEntries(
        props.students.map((student) => [
            student.id,
            useForm({
                score: student.score?.toString() ?? '',
                feedback: student.feedback ?? '',
                return: false,
            }),
        ]),
    ),
);

function save(student: StudentWork, returnWork: boolean): void {
    const form = forms[student.id];
    form.return = returnWork;
    form.put(
        GradingController.grade.url({
            item: props.item.id,
            student: student.id,
        }),
        {
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <Head :title="`Jawaban: ${item.title}`" />

    <div class="mx-auto grid w-full max-w-5xl gap-6 p-6">
        <div>
            <h1 class="text-2xl font-semibold">{{ item.title }}</h1>
            <p class="text-sm text-muted-foreground">
                <span v-if="item.maxPoints !== null"
                    >{{ item.maxPoints }} poin ·
                </span>
                <span v-if="item.dueAt"
                    >Tenggat {{ formatDateTime(item.dueAt) }}</span
                >
                <span v-else>Tanpa tenggat</span>
            </p>
        </div>

        <p
            v-if="students.length === 0"
            class="rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Belum ada siswa di kelas ini.
        </p>

        <div
            v-for="student in students"
            :key="student.id"
            class="grid gap-4 rounded-2xl border p-5 md:grid-cols-[1fr_18rem]"
            data-test="student-work"
        >
            <div class="grid content-start gap-3">
                <div
                    class="flex flex-wrap items-baseline justify-between gap-2"
                >
                    <div>
                        <p class="font-medium">{{ student.name }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ student.username }}
                        </p>
                    </div>
                    <span
                        :class="[
                            'text-sm',
                            student.isLate
                                ? 'text-destructive'
                                : 'text-muted-foreground',
                        ]"
                    >
                        {{ labels[student.status]
                        }}<template v-if="student.isLate">
                            (terlambat)</template
                        >
                        <template v-if="student.turnedInAt">
                            · {{ formatDateTime(student.turnedInAt) }}</template
                        >
                    </span>
                </div>
                <p
                    v-if="student.body"
                    class="rounded-lg bg-muted p-3 text-sm whitespace-pre-line"
                >
                    {{ student.body }}
                </p>
                <AttachmentList
                    v-if="student.attachments.length > 0"
                    :attachments="student.attachments"
                />
                <p
                    v-else-if="!student.body"
                    class="text-sm text-muted-foreground"
                >
                    Belum ada file.
                </p>
            </div>

            <form
                class="grid content-start gap-2"
                @submit.prevent="save(student, true)"
            >
                <label class="text-sm font-medium" :for="`score-${student.id}`"
                    >Nilai</label
                >
                <div class="flex items-center gap-2">
                    <Input
                        :id="`score-${student.id}`"
                        v-model="forms[student.id].score"
                        type="number"
                        min="0"
                        :max="item.maxPoints ?? 1000"
                        step="0.5"
                        class="w-24"
                    />
                    <span
                        v-if="item.maxPoints !== null"
                        class="text-sm text-muted-foreground"
                        >/ {{ item.maxPoints }}</span
                    >
                </div>
                <p
                    v-if="forms[student.id].errors.score"
                    class="text-sm text-destructive"
                >
                    {{ forms[student.id].errors.score }}
                </p>
                <Textarea
                    v-model="forms[student.id].feedback"
                    placeholder="Catatan untuk siswa"
                    rows="2"
                />
                <div class="flex gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="forms[student.id].processing"
                        @click="save(student, false)"
                        >Simpan</Button
                    >
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="forms[student.id].processing"
                        data-test="return"
                        >Kembalikan</Button
                    >
                </div>
            </form>
        </div>
    </div>
</template>
