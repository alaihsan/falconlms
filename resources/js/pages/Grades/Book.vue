<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    exportMethod,
    importMethod,
    index,
    update,
} from '@/actions/App/Http/Controllers/GradeController';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type {
    GradebookItem,
    GradebookScoreEntry,
    GradebookStudent,
    LibraryOption,
} from '@/types';

const props = defineProps<{
    classes: LibraryOption[];
    selectedClassId: number | null;
    students: GradebookStudent[];
    items: GradebookItem[];
    scores: Record<string, number>;
}>();

const draft = reactive<Record<string, string>>({});
const grid = ref<HTMLElement | null>(null);
const importInput = ref<HTMLInputElement | null>(null);
const saving = ref(false);
const saveError = ref<string | null>(null);

function cellKey(studentId: number, itemId: number): string {
    return `${studentId}:${itemId}`;
}

function resetDraft(): void {
    for (const key of Object.keys(draft)) {
        delete draft[key];
    }

    for (const student of props.students) {
        for (const item of props.items) {
            const key = cellKey(student.id, item.id);
            draft[key] =
                props.scores[key] !== undefined
                    ? String(props.scores[key])
                    : '';
        }
    }
}

watch(() => [props.scores, props.students, props.items], resetDraft, {
    immediate: true,
});

/**
 * Parse a cell: a number when valid, null when empty, undefined when invalid.
 */
function parseCell(value: string, maxScore: number): number | null | undefined {
    const text = value.trim();

    if (text === '') {
        return null;
    }

    if (!/^\d+$/.test(text)) {
        return undefined;
    }

    const score = Number(text);

    return score <= maxScore ? score : undefined;
}

const cellStates = computed(() => {
    const changes: GradebookScoreEntry[] = [];
    const invalid = new Set<string>();
    const dirty = new Set<string>();

    for (const student of props.students) {
        for (const item of props.items) {
            const key = cellKey(student.id, item.id);
            const parsed = parseCell(draft[key] ?? '', item.max_score);
            const original = props.scores[key] ?? null;

            if (parsed === undefined) {
                invalid.add(key);
            } else if (parsed !== original) {
                dirty.add(key);
                changes.push({
                    learning_item_id: item.id,
                    siswa_id: student.id,
                    score: parsed,
                });
            }
        }
    }

    return { changes, invalid, dirty };
});

const hasUnsavedWork = computed(
    () =>
        cellStates.value.changes.length > 0 ||
        cellStates.value.invalid.size > 0,
);
const canSave = computed(
    () =>
        cellStates.value.changes.length > 0 &&
        cellStates.value.invalid.size === 0 &&
        !saving.value,
);

function average(values: (number | null | undefined)[]): string {
    const numbers = values.filter(
        (value): value is number => typeof value === 'number',
    );

    if (numbers.length === 0) {
        return '–';
    }

    return (
        numbers.reduce((sum, value) => sum + value, 0) / numbers.length
    ).toFixed(1);
}

function studentAverage(student: GradebookStudent): string {
    return average(
        props.items.map((item) =>
            parseCell(
                draft[cellKey(student.id, item.id)] ?? '',
                item.max_score,
            ),
        ),
    );
}

function itemAverage(item: GradebookItem): string {
    return average(
        props.students.map((student) =>
            parseCell(
                draft[cellKey(student.id, item.id)] ?? '',
                item.max_score,
            ),
        ),
    );
}

function focusCell(row: number, column: number): void {
    const cell = grid.value?.querySelector<HTMLInputElement>(
        `[data-cell="${row}-${column}"]`,
    );

    if (cell) {
        cell.focus();
        cell.select();
    }
}

function onKeydown(event: KeyboardEvent, row: number, column: number): void {
    if (event.key === 'Enter' || event.key === 'ArrowDown') {
        event.preventDefault();
        focusCell(row + 1, column);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        focusCell(row - 1, column);
    }
}

/**
 * Paste a block copied from a spreadsheet, filling cells to the right and downward.
 */
function onPaste(event: ClipboardEvent, row: number, column: number): void {
    const text = event.clipboardData?.getData('text') ?? '';

    if (!/[\t\n]/.test(text)) {
        return;
    }

    event.preventDefault();

    const lines = text.replace(/\r/g, '').split('\n');
    if (lines[lines.length - 1] === '') {
        lines.pop();
    }

    lines.forEach((line, rowOffset) => {
        const student = props.students[row + rowOffset];

        if (!student) {
            return;
        }

        line.split('\t').forEach((value, columnOffset) => {
            const item = props.items[column + columnOffset];

            if (item) {
                draft[cellKey(student.id, item.id)] = value.trim();
            }
        });
    });
}

function changeClass(event: Event): void {
    const select = event.target as HTMLSelectElement;

    if (
        hasUnsavedWork.value &&
        !confirm(
            'Ada nilai yang belum disimpan. Pindah kelas dan buang perubahan?',
        )
    ) {
        select.value = String(props.selectedClassId ?? '');

        return;
    }

    router.get(
        index.url(),
        { class_id: Number(select.value) },
        { preserveScroll: true },
    );
}

function save(): void {
    if (!canSave.value || props.selectedClassId === null) {
        return;
    }

    saveError.value = null;
    saving.value = true;

    router.put(
        update.url(props.selectedClassId),
        { scores: cellStates.value.changes },
        {
            preserveScroll: true,
            onError: (errors) => {
                saveError.value =
                    Object.values(errors)[0] ?? 'Nilai gagal disimpan.';
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

function importFile(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file || props.selectedClassId === null) {
        return;
    }

    if (
        hasUnsavedWork.value &&
        !confirm(
            'Ada nilai yang belum disimpan. Lanjutkan impor dan buang perubahan?',
        )
    ) {
        input.value = '';

        return;
    }

    saveError.value = null;

    router.post(
        importMethod.url(props.selectedClassId),
        { file },
        {
            forceFormData: true,
            preserveScroll: true,
            onError: (errors) => {
                saveError.value =
                    Object.values(errors)[0] ?? 'Berkas gagal diimpor.';
            },
            onFinish: () => {
                input.value = '';
            },
        },
    );
}
</script>

<template>
    <div>
        <Head title="Buku Nilai - Falcon LMS" />

        <AuthenticatedLayout title="Buku Nilai">
            <template #header>
                <div>
                    <h1
                        class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl"
                    >
                        Buku Nilai
                    </h1>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Ketik nilai langsung di tabel, atau tempel blok nilai
                        dari Excel.
                    </p>
                </div>
            </template>

            <template #default>
                <div
                    v-if="classes.length === 0"
                    class="rounded-2xl border border-dashed border-slate-200 p-10 text-center"
                >
                    <p class="font-semibold text-slate-800">
                        Anda belum memiliki kelas.
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        Buat kelas terlebih dahulu untuk mulai mengisi buku
                        nilai.
                    </p>
                </div>

                <template v-else>
                    <!-- Toolbar -->
                    <div
                        class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
                    >
                        <select
                            :value="selectedClassId ?? ''"
                            aria-label="Pilih kelas"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 focus:outline-hidden"
                            @change="changeClass"
                        >
                            <option
                                v-for="c in classes"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.label }}
                            </option>
                        </select>

                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                v-if="cellStates.invalid.size > 0"
                                class="text-xs font-semibold text-rose-600"
                            >
                                {{ cellStates.invalid.size }} nilai tidak valid
                            </span>
                            <span
                                v-else-if="cellStates.changes.length > 0"
                                class="text-xs font-semibold text-amber-600"
                            >
                                {{ cellStates.changes.length }} perubahan belum
                                disimpan
                            </span>

                            <input
                                ref="importInput"
                                type="file"
                                accept=".csv,text/csv"
                                class="hidden"
                                @change="importFile"
                            />
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                @click="importInput?.click()"
                            >
                                <FalconIcon name="upload" className="w-4 h-4" />
                                <span>Impor CSV</span>
                            </button>
                            <a
                                v-if="selectedClassId !== null"
                                :href="exportMethod.url(selectedClassId)"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                            >
                                <FalconIcon
                                    name="download"
                                    className="w-4 h-4"
                                />
                                <span>Ekspor CSV</span>
                            </a>
                            <button
                                type="button"
                                :disabled="!canSave"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                                @click="save"
                            >
                                <FalconIcon name="check" className="w-4 h-4" />
                                <span>{{
                                    saving ? 'Menyimpan...' : 'Simpan Nilai'
                                }}</span>
                            </button>
                        </div>
                    </div>

                    <p
                        v-if="saveError"
                        class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-medium text-rose-700"
                        role="alert"
                    >
                        {{ saveError }}
                    </p>

                    <div
                        v-if="students.length === 0 || items.length === 0"
                        class="rounded-2xl border border-dashed border-slate-200 p-10 text-center"
                    >
                        <p class="font-semibold text-slate-800">
                            {{
                                students.length === 0
                                    ? 'Belum ada siswa di kelas ini.'
                                    : 'Belum ada konten berstatus dinilai di kelas ini.'
                            }}
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            {{
                                students.length === 0
                                    ? 'Bagikan kode kelas agar siswa dapat bergabung.'
                                    : 'Bagikan tugas atau kuis ke kelas ini, atau tandai materi sebagai dinilai di Pustaka.'
                            }}
                        </p>
                    </div>

                    <!-- Grid -->
                    <div
                        v-else
                        ref="grid"
                        class="overflow-auto rounded-2xl border border-slate-200 bg-white shadow-2xs"
                    >
                        <table
                            class="w-full border-separate border-spacing-0 text-sm"
                        >
                            <thead>
                                <tr>
                                    <th
                                        scope="col"
                                        class="sticky left-0 z-20 min-w-48 border-r border-b border-slate-200 bg-slate-50 px-4 py-3 text-left text-xs font-bold text-slate-600"
                                    >
                                        Siswa
                                    </th>
                                    <th
                                        v-for="item in items"
                                        :key="item.id"
                                        scope="col"
                                        class="min-w-32 border-r border-b border-slate-200 bg-slate-50 px-3 py-2 text-left align-bottom"
                                    >
                                        <span
                                            class="line-clamp-2 text-xs font-bold text-slate-800"
                                            :title="item.title"
                                        >
                                            {{ item.title }}
                                        </span>
                                        <span
                                            class="mt-0.5 block text-[10px] font-medium text-slate-400"
                                        >
                                            {{ item.type_label }} · maks
                                            {{ item.max_score }}
                                        </span>
                                    </th>
                                    <th
                                        scope="col"
                                        class="min-w-20 border-b border-slate-200 bg-slate-50 px-3 py-3 text-right text-xs font-bold text-slate-600"
                                    >
                                        Rata-rata
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(student, row) in students"
                                    :key="student.id"
                                >
                                    <th
                                        scope="row"
                                        class="sticky left-0 z-10 border-r border-b border-slate-100 bg-white px-4 py-2 text-left"
                                    >
                                        <span
                                            class="block truncate text-sm font-semibold text-slate-900"
                                            >{{ student.name }}</span
                                        >
                                        <span
                                            class="block truncate text-[11px] font-normal text-slate-400"
                                            >{{ student.identifier }}</span
                                        >
                                    </th>
                                    <td
                                        v-for="(item, column) in items"
                                        :key="item.id"
                                        class="border-r border-b border-slate-100 p-0"
                                    >
                                        <input
                                            v-model="
                                                draft[
                                                    cellKey(student.id, item.id)
                                                ]
                                            "
                                            type="text"
                                            inputmode="numeric"
                                            autocomplete="off"
                                            :data-cell="`${row}-${column}`"
                                            :aria-label="`Nilai ${student.name} untuk ${item.title}`"
                                            :aria-invalid="
                                                cellStates.invalid.has(
                                                    cellKey(
                                                        student.id,
                                                        item.id,
                                                    ),
                                                )
                                            "
                                            class="h-11 w-full border-2 border-transparent bg-transparent px-3 text-right text-sm text-slate-900 tabular-nums focus:border-indigo-600 focus:outline-hidden"
                                            :class="{
                                                'bg-rose-50 text-rose-700':
                                                    cellStates.invalid.has(
                                                        cellKey(
                                                            student.id,
                                                            item.id,
                                                        ),
                                                    ),
                                                'bg-amber-50':
                                                    cellStates.dirty.has(
                                                        cellKey(
                                                            student.id,
                                                            item.id,
                                                        ),
                                                    ),
                                            }"
                                            @keydown="
                                                onKeydown($event, row, column)
                                            "
                                            @paste="
                                                onPaste($event, row, column)
                                            "
                                            @focus="
                                                (
                                                    $event.target as HTMLInputElement
                                                ).select()
                                            "
                                        />
                                    </td>
                                    <td
                                        class="border-b border-slate-100 px-3 py-2 text-right text-sm font-semibold text-slate-700 tabular-nums"
                                    >
                                        {{ studentAverage(student) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th
                                        scope="row"
                                        class="sticky left-0 z-10 border-r border-slate-200 bg-slate-50 px-4 py-2.5 text-left text-xs font-bold text-slate-600"
                                    >
                                        Rata-rata kelas
                                    </th>
                                    <td
                                        v-for="item in items"
                                        :key="item.id"
                                        class="border-r border-slate-200 bg-slate-50 px-3 py-2.5 text-right text-sm font-semibold text-slate-700 tabular-nums"
                                    >
                                        {{ itemAverage(item) }}
                                    </td>
                                    <td class="bg-slate-50"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <p class="mt-3 text-[11px] text-slate-400">
                        Enter atau panah bawah pindah ke siswa berikutnya, Tab
                        pindah ke kolom berikutnya. Kosongkan sel untuk
                        menghapus nilai. Sel kuning belum disimpan; sel merah
                        bukan angka bulat atau melebihi nilai maksimal.
                    </p>
                </template>
            </template>
        </AuthenticatedLayout>
    </div>
</template>
