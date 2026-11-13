<script setup lang="ts">
import { download } from '@/actions/App/Http/Controllers/LibraryController';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import type { LearningItemView } from '@/types';

defineProps<{
    item: LearningItemView;
}>();

const emit = defineEmits<{
    (e: 'preview'): void;
    (e: 'poll'): void;
    (e: 'play'): void;
    (e: 'share'): void;
    (e: 'move'): void;
    (e: 'copy'): void;
    (e: 'toggle-graded'): void;
    (e: 'delete'): void;
}>();

const buttonClass =
    'rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-indigo-600';
</script>

<template>
    <div class="flex shrink-0 items-center gap-0.5">
        <a
            v-if="item.url"
            :href="item.url"
            target="_blank"
            rel="noopener noreferrer"
            :class="buttonClass"
            title="Buka tautan di tab baru"
            aria-label="Buka tautan di tab baru"
        >
            <FalconIcon name="link" className="w-4 h-4" />
        </a>
        <button
            v-if="item.h5p_player_url"
            type="button"
            :class="buttonClass"
            title="Buka konten interaktif"
            aria-label="Buka konten interaktif"
            @click="emit('play')"
        >
            <FalconIcon name="play" className="w-4 h-4" />
        </button>
        <button
            v-if="item.poll"
            type="button"
            :class="buttonClass"
            :title="item.poll.can_vote ? 'Isi polling' : 'Lihat hasil polling'"
            :aria-label="
                item.poll.can_vote ? 'Isi polling' : 'Lihat hasil polling'
            "
            @click="emit('poll')"
        >
            <FalconIcon name="poll" className="w-4 h-4" />
        </button>
        <button
            v-if="item.preview_kind"
            type="button"
            :class="buttonClass"
            title="Pratinjau"
            aria-label="Pratinjau"
            @click="emit('preview')"
        >
            <FalconIcon name="eye" className="w-4 h-4" />
        </button>
        <a
            v-if="item.attachment_name"
            :href="download.url(item.id)"
            :class="buttonClass"
            title="Unduh berkas"
            aria-label="Unduh berkas"
        >
            <FalconIcon name="download" className="w-4 h-4" />
        </a>
        <button
            v-if="item.can_manage"
            type="button"
            :class="buttonClass"
            title="Bagikan"
            aria-label="Bagikan"
            @click="emit('share')"
        >
            <FalconIcon name="share" className="w-4 h-4" />
        </button>
        <button
            v-if="item.can_manage"
            type="button"
            :class="[buttonClass, item.is_graded ? 'text-indigo-600' : '']"
            :title="
                item.is_graded
                    ? 'Keluarkan dari buku nilai'
                    : 'Masukkan ke buku nilai'
            "
            :aria-label="
                item.is_graded
                    ? 'Keluarkan dari buku nilai'
                    : 'Masukkan ke buku nilai'
            "
            :aria-pressed="item.is_graded"
            @click="emit('toggle-graded')"
        >
            <FalconIcon name="grades" className="w-4 h-4" />
        </button>
        <button
            v-if="item.is_owner"
            type="button"
            :class="buttonClass"
            title="Pindahkan ke folder"
            aria-label="Pindahkan ke folder"
            @click="emit('move')"
        >
            <FalconIcon name="move" className="w-4 h-4" />
        </button>
        <button
            v-if="item.can_copy && !item.is_owner"
            type="button"
            :class="buttonClass"
            title="Salin ke Pustaka Saya"
            aria-label="Salin ke Pustaka Saya"
            @click="emit('copy')"
        >
            <FalconIcon name="copy" className="w-4 h-4" />
        </button>
        <button
            v-if="item.can_manage"
            type="button"
            class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
            title="Pindahkan ke Tempat Sampah"
            aria-label="Pindahkan ke Tempat Sampah"
            @click="emit('delete')"
        >
            <FalconIcon name="trash" className="w-4 h-4" />
        </button>
    </div>
</template>
