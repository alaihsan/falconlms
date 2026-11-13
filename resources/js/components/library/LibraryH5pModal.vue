<script setup lang="ts">
import FalconIcon from '@/components/ui/FalconIcon.vue';
import type { LearningItemView } from '@/types';

defineProps<{
    item: LearningItemView | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        emit('close');
    }
}
</script>

<template>
    <div
        v-if="item && item.h5p_player_url"
        class="fixed inset-0 z-50 flex flex-col bg-slate-900/90 backdrop-blur-xs"
        role="dialog"
        aria-modal="true"
        :aria-label="`Konten interaktif ${item.title}`"
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
                    Konten interaktif H5P
                </p>
            </div>
            <button
                type="button"
                class="shrink-0 rounded-xl bg-white/10 p-2 transition hover:bg-white/20"
                aria-label="Tutup"
                autofocus
                @click="emit('close')"
            >
                <FalconIcon name="x" className="w-5 h-5" />
            </button>
        </div>

        <div
            class="flex min-h-0 flex-1 justify-center px-4 pb-4 sm:px-6 sm:pb-6"
            @click.self="emit('close')"
        >
            <!--
                H5P packages run their own JavaScript. The sandbox (without allow-same-origin) keeps that
                code in an opaque origin, away from the signed-in user's session.
            -->
            <iframe
                :src="item.h5p_player_url"
                :title="`Konten interaktif ${item.title}`"
                sandbox="allow-scripts"
                allow="fullscreen"
                class="h-full w-full max-w-5xl rounded-xl bg-white"
            ></iframe>
        </div>
    </div>
</template>
