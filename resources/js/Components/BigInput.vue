<script setup>
import { computed } from 'vue';
import { Plus, Minus } from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    label: {
        type: String,
        required: true
    },
    id: {
        type: String,
        required: true
    },
    unit: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: '0.0'
    },
    step: {
        type: Number,
        default: 0.1
    },
    min: {
        type: Number,
        default: 0
    },
    max: {
        type: Number,
        default: 200
    },
    error: {
        type: String,
        default: ''
    },
    hasStepper: {
        type: Boolean,
        default: true
    },
    inputmode: {
        type: String,
        default: 'decimal'
    },
    hint: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const numValue = computed(() => {
    const val = parseFloat(String(props.modelValue));
    return isNaN(val) ? 0 : val;
});

const increment = () => {
    if (props.disabled) return;
    const current = props.modelValue === '' ? props.min : numValue.value;
    const nextVal = Math.min(props.max, current + props.step);
    // Precision format
    const decimals = props.step < 0.1 ? 2 : 1;
    emit('update:modelValue', parseFloat(nextVal.toFixed(decimals)));
};

const decrement = () => {
    if (props.disabled) return;
    const current = props.modelValue === '' ? props.min : numValue.value;
    const nextVal = Math.max(props.min, current - props.step);
    const decimals = props.step < 0.1 ? 2 : 1;
    emit('update:modelValue', parseFloat(nextVal.toFixed(decimals)));
};

const handleInput = (event) => {
    emit('update:modelValue', event.target.value);
};
</script>

<template>
    <div class="w-full flex flex-col gap-1.5">
        <div class="flex items-center justify-between">
            <label :for="id" class="text-sm font-bold text-slate-800">
                {{ label }}
            </label>
            <span v-if="hint" class="text-xs text-slate-400">
                {{ hint }}
            </span>
        </div>

        <div
            :class="[
                'relative flex items-stretch rounded-2xl bg-white border-2 transition-all shadow-xs overflow-hidden',
                error
                    ? 'border-red-400 focus-within:border-red-500 focus-within:ring-4 focus-within:ring-red-100'
                    : 'border-slate-200 focus-within:border-rose-500 focus-within:ring-4 focus-within:ring-rose-100',
                disabled ? 'opacity-60 bg-slate-50 cursor-not-allowed' : ''
            ]"
        >
            <!-- Stepper Decrement (-) -->
            <button
                v-if="hasStepper"
                type="button"
                @click="decrement"
                :disabled="disabled"
                class="w-13 min-h-[52px] flex items-center justify-center bg-slate-50 hover:bg-slate-100 active:bg-slate-200 text-slate-700 transition-colors border-r border-slate-200 select-none touch-manipulation disabled:pointer-events-none"
                aria-label="Kurangi nilai"
            >
                <Minus class="w-5 h-5 stroke-[2.5]" />
            </button>

            <!-- Main Input -->
            <div class="relative flex-1 flex items-center">
                <input
                    :id="id"
                    type="text"
                    :inputmode="inputmode"
                    :value="modelValue"
                    @input="handleInput"
                    :placeholder="placeholder"
                    :disabled="disabled"
                    class="w-full h-full min-h-[52px] px-3.5 text-center sm:text-left text-xl sm:text-2xl font-bold text-slate-900 placeholder:text-slate-300 bg-transparent border-0 focus:ring-0 focus:outline-hidden"
                />

                <!-- Unit Suffix Badge -->
                <span
                    v-if="unit"
                    class="pr-3 text-sm font-semibold text-slate-400 select-none uppercase tracking-wider"
                >
                    {{ unit }}
                </span>
            </div>

            <!-- Stepper Increment (+) -->
            <button
                v-if="hasStepper"
                type="button"
                @click="increment"
                :disabled="disabled"
                class="w-13 min-h-[52px] flex items-center justify-center bg-slate-50 hover:bg-slate-100 active:bg-slate-200 text-slate-700 transition-colors border-l border-slate-200 select-none touch-manipulation disabled:pointer-events-none"
                aria-label="Tambah nilai"
            >
                <Plus class="w-5 h-5 stroke-[2.5]" />
            </button>
        </div>

        <!-- Field Error -->
        <p v-if="error" class="text-xs font-semibold text-red-600 mt-0.5 animate-in fade-in duration-200">
            {{ error }}
        </p>
    </div>
</template>
