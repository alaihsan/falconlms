<script setup lang="ts">
import { ref } from 'vue';

const props = defineProps<{ disabled?: boolean; label?: string }>();
const emit = defineEmits<{ files: [files: File[]] }>();

const active = ref(false);
let depth = 0;

function hasFiles(event: DragEvent): boolean {
    return Array.from(event.dataTransfer?.types ?? []).includes('Files');
}

function enter(event: DragEvent): void {
    if (props.disabled || !hasFiles(event)) {
        return;
    }

    depth++;
    active.value = true;
}

function leave(): void {
    depth = Math.max(0, depth - 1);
    active.value = depth > 0;
}

function drop(event: DragEvent): void {
    depth = 0;
    active.value = false;

    if (props.disabled || !hasFiles(event)) {
        return;
    }

    emit('files', Array.from(event.dataTransfer?.files ?? []));
}
</script>

<template>
    <div
        class="relative"
        @dragenter.prevent="enter"
        @dragover.prevent
        @dragleave.prevent="leave"
        @drop.prevent="drop"
    >
        <slot />
        <div
            v-if="active"
            class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center rounded-2xl border-2 border-dashed border-primary bg-primary/10 text-sm font-medium text-primary"
        >
            {{ label ?? 'Lepaskan untuk mengunggah' }}
        </div>
    </div>
</template>
