<script setup lang="ts">
import { Download, ExternalLink, File, Link2, X } from '@lucide/vue';
import { ref } from 'vue';
import { formatBytes } from '@/lib/format';
import type { Attachment } from '@/types';

defineProps<{
    attachments: Attachment[];
    removable?: boolean;
    /** Show images, video, audio and PDF inline. */
    previews?: boolean;
}>();

const emit = defineEmits<{ remove: [attachment: Attachment] }>();

const openPdf = ref<number | null>(null);
</script>

<template>
    <ul class="grid gap-3">
        <li
            v-for="attachment in attachments"
            :key="attachment.id"
            class="overflow-hidden rounded-xl border"
            data-test="attachment"
        >
            <template v-if="previews && attachment.kind === 'file'">
                <img
                    v-if="attachment.preview === 'image'"
                    :src="attachment.url"
                    :alt="attachment.name"
                    class="max-h-96 w-full bg-muted object-contain"
                    loading="lazy"
                />
                <video
                    v-else-if="attachment.preview === 'video'"
                    :src="attachment.url"
                    controls
                    preload="metadata"
                    class="max-h-[28rem] w-full bg-black"
                />
                <audio
                    v-else-if="attachment.preview === 'audio'"
                    :src="attachment.url"
                    controls
                    preload="metadata"
                    class="w-full p-3"
                />
                <iframe
                    v-else-if="
                        attachment.preview === 'pdf' &&
                        openPdf === attachment.id
                    "
                    :src="attachment.url"
                    :title="attachment.name"
                    class="h-[32rem] w-full bg-muted"
                />
            </template>

            <div class="flex items-center gap-3 px-4 py-3">
                <Link2
                    v-if="attachment.kind === 'link'"
                    class="size-5 shrink-0 text-emerald-600"
                />
                <File v-else class="size-5 shrink-0 text-muted-foreground" />
                <div class="min-w-0 flex-1">
                    <a
                        :href="attachment.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="block truncate text-sm font-medium hover:underline"
                        >{{ attachment.name }}</a
                    >
                    <p
                        v-if="attachment.size"
                        class="text-xs text-muted-foreground"
                    >
                        {{ formatBytes(attachment.size) }}
                    </p>
                </div>
                <button
                    v-if="
                        previews &&
                        attachment.preview === 'pdf' &&
                        openPdf !== attachment.id
                    "
                    type="button"
                    class="rounded-md px-2 py-1 text-xs font-medium text-primary hover:bg-accent"
                    @click="openPdf = attachment.id"
                >
                    Lihat
                </button>
                <a
                    v-if="attachment.kind === 'file'"
                    :href="`${attachment.url}?download=1`"
                    class="rounded-md p-1.5 text-muted-foreground hover:bg-accent"
                    :aria-label="`Unduh ${attachment.name}`"
                >
                    <Download class="size-4" />
                </a>
                <a
                    v-else
                    :href="attachment.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="rounded-md p-1.5 text-muted-foreground hover:bg-accent"
                    aria-label="Buka tautan"
                >
                    <ExternalLink class="size-4" />
                </a>
                <button
                    v-if="removable"
                    type="button"
                    class="rounded-md p-1.5 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                    :aria-label="`Hapus ${attachment.name}`"
                    @click="emit('remove', attachment)"
                >
                    <X class="size-4" />
                </button>
            </div>
        </li>
    </ul>
</template>
