<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue';
import {
    download,
    preview,
} from '@/actions/App/Http/Controllers/LibraryController';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import type { LearningItemView } from '@/types';

const props = defineProps<{
    item: LearningItemView | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const officeUrl = ref<string | null>(null);
const officeState = ref<'idle' | 'loading' | 'ready' | 'failed'>('idle');
let currentRequest: AbortController | null = null;

function releaseOfficePreview(): void {
    currentRequest?.abort();
    currentRequest = null;

    if (officeUrl.value) {
        URL.revokeObjectURL(officeUrl.value);
    }

    officeUrl.value = null;
    officeState.value = 'idle';
}

/**
 * Office documents are converted on the server, which can take a while and can fail,
 * so they are fetched first to show progress and a readable fallback.
 */
async function loadOfficePreview(item: LearningItemView): Promise<void> {
    const request = new AbortController();
    currentRequest = request;
    officeState.value = 'loading';

    try {
        const response = await fetch(preview.url(item.id), {
            credentials: 'same-origin',
            signal: request.signal,
        });

        if (!response.ok) {
            throw new Error(`Preview failed with status ${response.status}`);
        }

        const blob = await response.blob();

        if (request.signal.aborted) {
            return;
        }

        officeUrl.value = URL.createObjectURL(blob);
        officeState.value = 'ready';
    } catch {
        if (!request.signal.aborted) {
            officeState.value = 'failed';
        }
    }
}

watch(
    () => props.item,
    (item) => {
        releaseOfficePreview();

        if (item?.preview_kind === 'office') {
            void loadOfficePreview(item);
        }
    },
);

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        emit('close');
    }
}

onBeforeUnmount(releaseOfficePreview);
</script>

<template>
    <div
        v-if="item"
        class="fixed inset-0 z-50 flex flex-col bg-slate-900/90 backdrop-blur-xs"
        role="dialog"
        aria-modal="true"
        :aria-label="`Pratinjau ${item.title}`"
        tabindex="-1"
        @keydown="onKeydown"
    >
        <div
            class="flex items-center justify-between gap-3 px-4 py-3 text-white sm:px-6"
        >
            <div class="min-w-0">
                <h3 class="truncate text-sm font-bold sm:text-base">
                    {{ item.title }}
                </h3>
                <p class="truncate text-xs text-slate-300">
                    {{ item.attachment_name }}
                    <span v-if="item.attachment_size"
                        >· {{ item.attachment_size }}</span
                    >
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <a
                    :href="download.url(item.id)"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-white/10 px-3 py-2 text-xs font-semibold transition hover:bg-white/20"
                >
                    <FalconIcon name="download" className="w-4 h-4" />
                    <span>Unduh</span>
                </a>
                <button
                    type="button"
                    class="rounded-xl bg-white/10 p-2 transition hover:bg-white/20"
                    aria-label="Tutup pratinjau"
                    autofocus
                    @click="emit('close')"
                >
                    <FalconIcon name="x" className="w-5 h-5" />
                </button>
            </div>
        </div>

        <div
            class="flex min-h-0 flex-1 items-center justify-center px-4 pb-4 sm:px-6 sm:pb-6"
            @click.self="emit('close')"
        >
            <iframe
                v-if="item.preview_kind === 'pdf'"
                :src="preview.url(item.id)"
                :title="`Pratinjau ${item.title}`"
                class="h-full w-full max-w-5xl rounded-xl bg-white"
            ></iframe>

            <img
                v-else-if="item.preview_kind === 'image'"
                :src="preview.url(item.id)"
                :alt="item.title"
                class="max-h-full max-w-full rounded-xl object-contain"
            />

            <video
                v-else-if="item.preview_kind === 'video'"
                :src="preview.url(item.id)"
                controls
                class="max-h-full max-w-full rounded-xl bg-black"
            ></video>

            <div
                v-else-if="item.preview_kind === 'audio'"
                class="w-full max-w-lg rounded-2xl bg-white p-6 text-center"
            >
                <p class="mb-4 truncate text-sm font-semibold text-slate-800">
                    {{ item.attachment_name }}
                </p>
                <audio
                    :src="preview.url(item.id)"
                    controls
                    class="w-full"
                ></audio>
            </div>

            <template v-else-if="item.preview_kind === 'office'">
                <iframe
                    v-if="officeState === 'ready' && officeUrl"
                    :src="officeUrl"
                    :title="`Pratinjau ${item.title}`"
                    class="h-full w-full max-w-5xl rounded-xl bg-white"
                ></iframe>

                <div
                    v-else-if="officeState === 'failed'"
                    class="max-w-md rounded-2xl bg-white p-6 text-center"
                >
                    <p class="font-semibold text-slate-800">
                        Pratinjau belum dapat dibuat.
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        Dokumen ini tidak dapat dikonversi di server. Unduh
                        berkas untuk membukanya di komputer Anda.
                    </p>
                </div>

                <div
                    v-else
                    class="rounded-2xl bg-white px-6 py-5 text-center"
                    role="status"
                >
                    <div
                        class="mx-auto mb-3 h-2 w-40 animate-pulse rounded-full bg-indigo-200"
                    ></div>
                    <p class="text-sm font-semibold text-slate-800">
                        Menyiapkan pratinjau dokumen...
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        Pembukaan pertama bisa memakan beberapa detik.
                    </p>
                </div>
            </template>
        </div>
    </div>
</template>
