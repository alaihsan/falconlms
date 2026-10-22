<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Toaster } from '@/components/ui/sonner';

defineProps<{
    title?: string;
    description?: string;
}>();

const name = usePage().props.name;
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-sidebar p-6 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div
                class="flex flex-col gap-8 rounded-2xl border bg-card p-8 shadow-sm"
            >
                <div class="flex flex-col items-center gap-4">
                    <div class="flex flex-col items-center gap-2 font-medium">
                        <div
                            class="flex size-12 items-center justify-center rounded-xl bg-primary text-primary-foreground"
                        >
                            <AppLogoIcon class="size-7" />
                        </div>
                        <span class="text-sm text-muted-foreground">{{
                            name
                        }}</span>
                    </div>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-medium">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
        <Toaster />
    </div>
</template>
