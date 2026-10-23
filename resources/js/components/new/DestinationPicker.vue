<script setup lang="ts">
import {
    Check,
    Folder,
    FolderOpen,
    GraduationCap,
    MapPin,
    Tag,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import {
    destinationLabel,
    useDestinations,
} from '@/composables/useDestinations';
import type { Destination, SpaceRef } from '@/types';

type Option = {
    key: string;
    label: string;
    icon: LucideIcon;
    depth: number;
    value: Destination;
};

const destination = defineModel<Destination>({ required: true });

const {
    accepts = 'any',
    spacesOnly = false,
    fixed = false,
    title = 'Disimpan di',
} = defineProps<{
    /** Which kind of space the content can go to. */
    accepts?: 'library' | 'course' | 'any';
    /** Only a class may be chosen, not a topic inside it. */
    spacesOnly?: boolean;
    /** Show the place without letting the user change it. */
    fixed?: boolean;
    title?: string;
}>();

const { options, loading, failed, load } = useDestinations();
const open = ref(false);

function toggle(): void {
    open.value = !open.value;

    if (open.value) {
        // Reload every time, so new folders and topics show up.
        load();
    }
}

function place(
    space: SpaceRef,
    folder: Destination['folder'] = null,
    topic: Destination['topic'] = null,
): Destination {
    return { space, folder, topic };
}

const groups = computed(() => {
    const result: { title: string; options: Option[] }[] = [];

    if (!options.value) {
        return result;
    }

    if (accepts !== 'course') {
        for (const library of options.value.libraries) {
            result.push({
                title: 'Pustaka',
                options: [
                    {
                        key: `s${library.space.id}`,
                        label: library.space.name,
                        icon: FolderOpen,
                        depth: 0,
                        value: place(library.space),
                    },
                    ...library.folders.map((folder) => ({
                        key: `f${folder.id}`,
                        label: folder.name,
                        icon: Folder,
                        depth: folder.depth + 1,
                        value: place(library.space, {
                            id: folder.id,
                            name: folder.name,
                        }),
                    })),
                ],
            });
        }
    }

    if (accepts !== 'library' && options.value.courses.length > 0) {
        result.push({
            title: 'Kelas',
            options: options.value.courses.flatMap((course) => [
                {
                    key: `s${course.space.id}`,
                    label: course.space.name,
                    icon: GraduationCap,
                    depth: 0,
                    value: place(course.space),
                },
                ...(spacesOnly
                    ? []
                    : course.topics.map((topic) => ({
                          key: `t${topic.id}`,
                          label: topic.name,
                          icon: Tag,
                          depth: 1,
                          value: place(course.space, null, topic),
                      }))),
            ]),
        });
    }

    return result;
});

function isSelected(value: Destination): boolean {
    return (
        value.space.id === destination.value.space.id &&
        (value.folder?.id ?? null) === (destination.value.folder?.id ?? null) &&
        (value.topic?.id ?? null) === (destination.value.topic?.id ?? null)
    );
}

function choose(value: Destination): void {
    destination.value = value;
    open.value = false;
}

const icon = computed(() => {
    if (destination.value.space.kind === 'course') {
        return destination.value.topic ? Tag : GraduationCap;
    }

    return destination.value.folder ? Folder : FolderOpen;
});
</script>

<template>
    <div class="grid gap-2" data-test="destination">
        <p class="flex items-center gap-1.5 text-sm font-medium">
            <MapPin class="size-4 text-muted-foreground" />
            {{ title }}
        </p>
        <div
            class="flex items-center gap-3 rounded-lg border border-primary/30 bg-primary/5 px-3 py-2"
        >
            <component :is="icon" class="size-5 shrink-0 text-primary" />
            <span
                class="min-w-0 flex-1 truncate text-sm font-medium"
                data-test="destination-label"
                >{{ destinationLabel(destination) }}</span
            >
            <Button
                v-if="!fixed"
                type="button"
                variant="ghost"
                size="sm"
                :aria-expanded="open"
                data-test="destination-change"
                @click="toggle"
            >
                {{ open ? 'Tutup' : 'Ubah' }}
            </Button>
        </div>

        <div
            v-if="open"
            class="max-h-64 overflow-y-auto rounded-lg border p-1"
            role="listbox"
            aria-label="Pilih lokasi simpan"
        >
            <div
                v-if="loading && !options"
                class="flex justify-center p-4 text-muted-foreground"
            >
                <Spinner />
            </div>
            <p
                v-else-if="failed"
                class="p-4 text-center text-sm text-muted-foreground"
            >
                Daftar lokasi tidak bisa dimuat.
                <button
                    type="button"
                    class="font-medium text-primary underline-offset-4 hover:underline"
                    @click="load"
                >
                    Coba lagi
                </button>
            </p>
            <p
                v-else-if="groups.length === 0"
                class="p-4 text-center text-sm text-muted-foreground"
            >
                Tidak ada lokasi lain.
            </p>
            <template v-for="group in groups" v-else :key="group.title">
                <p
                    class="px-2 pt-2 pb-1 text-xs font-medium text-muted-foreground"
                >
                    {{ group.title }}
                </p>
                <button
                    v-for="option in group.options"
                    :key="option.key"
                    type="button"
                    role="option"
                    :aria-selected="isSelected(option.value)"
                    :data-test="`destination-option-${option.key}`"
                    class="flex w-full items-center gap-2 rounded-md py-1.5 pr-2 text-left text-sm hover:bg-accent aria-selected:bg-accent aria-selected:font-medium"
                    :style="{ paddingLeft: `${0.5 + option.depth * 1.25}rem` }"
                    @click="choose(option.value)"
                >
                    <component
                        :is="option.icon"
                        class="size-4 shrink-0 text-primary"
                    />
                    <span class="min-w-0 flex-1 truncate">{{
                        option.label
                    }}</span>
                    <Check
                        v-if="isSelected(option.value)"
                        class="size-4 text-primary"
                    />
                </button>
            </template>
        </div>
    </div>
</template>
