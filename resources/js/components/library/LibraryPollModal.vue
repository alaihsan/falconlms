<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { vote } from '@/actions/App/Http/Controllers/PollController';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import type { LearningItemView } from '@/types';

const props = defineProps<{
    item: LearningItemView | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const form = useForm<{ poll_option_id: number | null }>({
    poll_option_id: null,
});

const poll = computed(() => props.item?.poll ?? null);
const showResults = computed(
    () => poll.value !== null && poll.value.total_votes !== null,
);

watch(
    () => props.item?.id,
    () => {
        form.clearErrors();
        form.poll_option_id = poll.value?.my_option_id ?? null;
    },
);

function percentage(votes: number | null): number {
    const total = poll.value?.total_votes ?? 0;

    return total > 0 && votes !== null ? Math.round((votes / total) * 100) : 0;
}

function submit(): void {
    if (!props.item) {
        return;
    }

    form.submit(vote(props.item.id), { preserveScroll: true });
}
</script>

<template>
    <div
        v-if="item && poll"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
        @click.self="emit('close')"
    >
        <div
            class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl"
        >
            <div
                class="mb-4 flex items-start justify-between gap-3 border-b border-slate-100 pb-3"
            >
                <div class="min-w-0">
                    <h3 class="text-base font-bold text-slate-900">
                        {{ item.title }}
                    </h3>
                    <p v-if="item.content" class="mt-1 text-xs text-slate-500">
                        {{ item.content }}
                    </p>
                </div>
                <button
                    type="button"
                    class="p-1 text-slate-400 hover:text-slate-600"
                    aria-label="Tutup"
                    @click="emit('close')"
                >
                    <FalconIcon name="x" className="w-5 h-5" />
                </button>
            </div>

            <form class="space-y-3" @submit.prevent="submit">
                <label
                    v-for="option in poll.options"
                    :key="option.id"
                    class="relative block overflow-hidden rounded-xl border px-3 py-2.5 text-sm"
                    :class="[
                        poll.can_vote ? 'cursor-pointer' : '',
                        form.poll_option_id === option.id
                            ? 'border-indigo-500'
                            : 'border-slate-200',
                    ]"
                >
                    <span
                        v-if="showResults"
                        class="absolute inset-y-0 left-0 bg-indigo-50"
                        :style="{ width: `${percentage(option.votes)}%` }"
                        aria-hidden="true"
                    ></span>
                    <span class="relative flex items-center gap-2.5">
                        <input
                            v-if="poll.can_vote"
                            v-model="form.poll_option_id"
                            type="radio"
                            name="poll_option_id"
                            :value="option.id"
                            class="border-slate-300 text-indigo-600"
                        />
                        <span class="flex-1 font-medium text-slate-800">{{
                            option.label
                        }}</span>
                        <span
                            v-if="showResults"
                            class="shrink-0 text-xs font-semibold text-slate-600 tabular-nums"
                        >
                            {{ option.votes }} · {{ percentage(option.votes) }}%
                        </span>
                    </span>
                </label>

                <p
                    v-if="form.errors.poll_option_id"
                    class="text-xs text-rose-600"
                >
                    {{ form.errors.poll_option_id }}
                </p>

                <p class="text-[11px] text-slate-400">
                    <template v-if="showResults"
                        >Total {{ poll.total_votes }} suara.</template
                    >
                    <template v-else
                        >Hasil polling terlihat setelah Anda memilih.</template
                    >
                </p>

                <div
                    class="flex items-center justify-end gap-2 border-t border-slate-100 pt-3"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                        @click="emit('close')"
                    >
                        Tutup
                    </button>
                    <button
                        v-if="poll.can_vote"
                        type="submit"
                        :disabled="
                            form.processing || form.poll_option_id === null
                        "
                        class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                    >
                        {{
                            poll.my_option_id === null
                                ? 'Kirim Jawaban'
                                : 'Ubah Jawaban'
                        }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
