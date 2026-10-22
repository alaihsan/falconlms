<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <!-- A falcon diving with its wings spread. -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 32 32"
        :class="className"
        v-bind="$attrs"
    >
        <path
            fill="currentColor"
            d="M16 9.5c1.3 0 2.4.7 2.9 1.8L30 6c-2 4.6-5.5 8.3-10.3 10.4l-1.3 6.4L16 29l-2.4-6.2-1.3-6.4C7.5 14.3 4 10.6 2 6l11.1 5.3c.5-1.1 1.6-1.8 2.9-1.8Z"
        />
    </svg>
</template>
