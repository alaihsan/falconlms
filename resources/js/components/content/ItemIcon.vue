<script setup lang="ts">
import {
    BookOpenText,
    ClipboardList,
    File,
    FileText,
    Film,
    Image,
    Link2,
    Music,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { computed } from 'vue';
import type { AttachmentPreview, ItemType } from '@/types';

const props = defineProps<{
    type: ItemType;
    preview?: AttachmentPreview;
}>();

const icon = computed(() => {
    if (props.type === 'file') {
        const byPreview: Partial<Record<string, LucideIcon>> = {
            image: Image,
            video: Film,
            audio: Music,
            pdf: FileText,
        };

        return byPreview[props.preview ?? ''] ?? File;
    }

    return { material: BookOpenText, assignment: ClipboardList, link: Link2 }[
        props.type
    ];
});

const color = computed(
    () =>
        ({
            material: 'bg-blue-100 text-blue-700',
            assignment: 'bg-amber-100 text-amber-700',
            link: 'bg-emerald-100 text-emerald-700',
            file: 'bg-slate-100 text-slate-700',
        })[props.type],
);
</script>

<template>
    <span
        :class="[
            color,
            'flex size-9 shrink-0 items-center justify-center rounded-full',
        ]"
    >
        <component :is="icon" class="size-4" />
    </span>
</template>
