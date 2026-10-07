<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    label: {
        type: String,
        required: true
    },
    description: {
        type: String,
        default: ''
    },
    id: {
        type: String,
        required: true
    },
    disabled: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const toggle = () => {
    if (props.disabled) return;
    emit('update:modelValue', !props.modelValue);
};
</script>

<template>
    <div
        @click="toggle"
        :class="[
            'w-full min-h-[56px] p-3.5 rounded-2xl border-2 flex items-center justify-between gap-3.5 cursor-pointer transition-all select-none touch-manipulation',
            modelValue
                ? 'bg-rose-50/70 border-rose-500 shadow-xs'
                : 'bg-white border-slate-200 hover:border-slate-300',
            disabled ? 'opacity-60 cursor-not-allowed bg-slate-50' : ''
        ]"
        role="checkbox"
        :aria-checked="modelValue"
        tabindex="0"
        @keydown.space.prevent="toggle"
    >
        <div class="flex-1 min-w-0 pr-2">
            <p :class="['text-sm font-bold leading-tight', modelValue ? 'text-rose-950' : 'text-slate-800']">
                {{ label }}
            </p>
            <p v-if="description" class="text-xs text-slate-500 leading-snug mt-0.5 truncate">
                {{ description }}
            </p>
        </div>

        <!-- Toggle Switch UI -->
        <div
            :class="[
                'w-13 h-7 rounded-full p-0.5 transition-colors duration-200 ease-in-out shrink-0 flex items-center',
                modelValue ? 'bg-rose-600 justify-end' : 'bg-slate-300 justify-start'
            ]"
        >
            <div
                class="w-6 h-6 rounded-full bg-white shadow-md transform transition-transform duration-200 ease-in-out"
            />
        </div>
    </div>
</template>
