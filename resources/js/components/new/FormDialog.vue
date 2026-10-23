<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

export type FormField = {
    name: string;
    label: string;
    type?: 'text' | 'url' | 'textarea' | 'select';
    placeholder?: string;
    hint?: string;
    required?: boolean;
    options?: { value: string; label: string }[];
};

const props = defineProps<{
    open: boolean;
    title: string;
    description?: string;
    fields: FormField[];
    action: string;
    submitLabel: string;
    extra?: Record<string, unknown>;
    initial?: Record<string, string>;
}>();

const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const form = useForm<Record<string, string>>({});

watch(
    () => props.open,
    (open) => {
        if (open) {
            form.defaults(
                Object.fromEntries(
                    props.fields.map((field) => [
                        field.name,
                        props.initial?.[field.name] ?? '',
                    ]),
                ),
            );
            form.reset();
            form.clearErrors();
        }
    },
    { immediate: true },
);

function submit(): void {
    form.transform((data) => ({ ...data, ...props.extra })).post(props.action, {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <form class="grid gap-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>{{ title }}</DialogTitle>
                    <DialogDescription v-if="description">{{
                        description
                    }}</DialogDescription>
                </DialogHeader>

                <!-- Where the result is saved (a location picker). -->
                <slot />

                <div
                    v-for="(field, index) in fields"
                    :key="field.name"
                    class="grid gap-2"
                >
                    <Label :for="`dialog-${field.name}`">{{
                        field.label
                    }}</Label>
                    <Textarea
                        v-if="field.type === 'textarea'"
                        :id="`dialog-${field.name}`"
                        v-model="form[field.name]"
                        :placeholder="field.placeholder"
                        :required="field.required"
                        rows="5"
                        v-focus="index === 0"
                    />
                    <select
                        v-else-if="field.type === 'select'"
                        :id="`dialog-${field.name}`"
                        v-model="form[field.name]"
                        :required="field.required"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                    >
                        <option
                            v-for="option in field.options"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                    <Input
                        v-else
                        :id="`dialog-${field.name}`"
                        v-model="form[field.name]"
                        :type="field.type ?? 'text'"
                        :placeholder="field.placeholder"
                        :required="field.required"
                        autocomplete="off"
                        v-focus="index === 0"
                    />
                    <p v-if="field.hint" class="text-xs text-muted-foreground">
                        {{ field.hint }}
                    </p>
                    <InputError :message="form.errors[field.name]" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="ghost"
                        @click="emit('update:open', false)"
                    >
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" />
                        {{ submitLabel }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
