<script setup lang="ts">
import {
    Bold,
    Heading2,
    Heading3,
    Italic,
    Link2,
    List,
    ListOrdered,
    Quote,
    Redo2,
    Strikethrough,
    Underline,
    Undo2,
} from '@lucide/vue';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { onBeforeUnmount, watch } from 'vue';

// The server sanitizes the HTML again (App\Support\RichText); this list only
// keeps the editor to the formatting Falcon supports.
const props = defineProps<{
    modelValue: string | null;
    placeholder?: string;
}>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const editor = useEditor({
    content: props.modelValue ?? '',
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3] },
            link: {
                openOnClick: false,
                autolink: true,
                protocols: ['http', 'https', 'mailto'],
            },
        }),
    ],
    editorProps: {
        attributes: {
            class: 'prose-falcon min-h-48 px-4 py-3 outline-none',
            'aria-label': props.placeholder ?? 'Isi',
        },
    },
    onUpdate: ({ editor }) => emit('update:modelValue', editor.getHTML()),
});

watch(
    () => props.modelValue,
    (value) => {
        if (editor.value && value !== editor.value.getHTML()) {
            editor.value.commands.setContent(value ?? '', {
                emitUpdate: false,
            });
        }
    },
);

onBeforeUnmount(() => editor.value?.destroy());

function setLink(): void {
    const previous = editor.value?.getAttributes('link').href as
        | string
        | undefined;
    const url = window.prompt(
        'Alamat tautan (http:// atau https://)',
        previous ?? 'http://',
    );

    if (url === null) {
        return;
    }

    if (url === '') {
        editor.value?.chain().focus().unsetLink().run();

        return;
    }

    editor.value
        ?.chain()
        .focus()
        .extendMarkRange('link')
        .setLink({ href: url })
        .run();
}

const buttons = [
    {
        icon: Bold,
        label: 'Tebal',
        run: () => editor.value?.chain().focus().toggleBold().run(),
        active: () => editor.value?.isActive('bold'),
    },
    {
        icon: Italic,
        label: 'Miring',
        run: () => editor.value?.chain().focus().toggleItalic().run(),
        active: () => editor.value?.isActive('italic'),
    },
    {
        icon: Underline,
        label: 'Garis bawah',
        run: () => editor.value?.chain().focus().toggleUnderline().run(),
        active: () => editor.value?.isActive('underline'),
    },
    {
        icon: Strikethrough,
        label: 'Coret',
        run: () => editor.value?.chain().focus().toggleStrike().run(),
        active: () => editor.value?.isActive('strike'),
    },
    {
        icon: Heading2,
        label: 'Judul',
        run: () =>
            editor.value?.chain().focus().toggleHeading({ level: 2 }).run(),
        active: () => editor.value?.isActive('heading', { level: 2 }),
    },
    {
        icon: Heading3,
        label: 'Subjudul',
        run: () =>
            editor.value?.chain().focus().toggleHeading({ level: 3 }).run(),
        active: () => editor.value?.isActive('heading', { level: 3 }),
    },
    {
        icon: List,
        label: 'Daftar',
        run: () => editor.value?.chain().focus().toggleBulletList().run(),
        active: () => editor.value?.isActive('bulletList'),
    },
    {
        icon: ListOrdered,
        label: 'Daftar bernomor',
        run: () => editor.value?.chain().focus().toggleOrderedList().run(),
        active: () => editor.value?.isActive('orderedList'),
    },
    {
        icon: Quote,
        label: 'Kutipan',
        run: () => editor.value?.chain().focus().toggleBlockquote().run(),
        active: () => editor.value?.isActive('blockquote'),
    },
    {
        icon: Link2,
        label: 'Tautan',
        run: setLink,
        active: () => editor.value?.isActive('link'),
    },
    {
        icon: Undo2,
        label: 'Urungkan',
        run: () => editor.value?.chain().focus().undo().run(),
        active: () => false,
    },
    {
        icon: Redo2,
        label: 'Ulangi',
        run: () => editor.value?.chain().focus().redo().run(),
        active: () => false,
    },
];
</script>

<template>
    <div
        class="overflow-hidden rounded-md border border-input shadow-xs focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50"
    >
        <div class="flex flex-wrap gap-0.5 border-b bg-muted/50 p-1">
            <button
                v-for="button in buttons"
                :key="button.label"
                type="button"
                :title="button.label"
                :aria-label="button.label"
                :class="[
                    'rounded p-1.5 text-muted-foreground hover:bg-background hover:text-foreground',
                    button.active()
                        ? 'bg-background text-foreground shadow-xs'
                        : '',
                ]"
                @click="button.run()"
            >
                <component :is="button.icon" class="size-4" />
            </button>
        </div>
        <EditorContent :editor="editor" />
    </div>
</template>
