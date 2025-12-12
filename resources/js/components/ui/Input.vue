<template>
    <input
        :class="cn(
            'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
            $attrs.class
        )"
        :value="modelValue !== undefined ? modelValue : undefined"
        @input="handleInput"
        v-bind="$attrs"
    />
</template>

<script setup>
import { cn } from '@/lib/utils';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: undefined,
    },
});

const emit = defineEmits(['update:modelValue']);

const handleInput = (event) => {
    // Only emit update for non-file inputs
    if (event.target.type !== 'file') {
        emit('update:modelValue', event.target.value);
    }
};
</script>

