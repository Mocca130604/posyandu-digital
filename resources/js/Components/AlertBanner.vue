<script setup>
import { computed } from 'vue';
import { AlertCircle, AlertTriangle, CheckCircle2, Info, X } from 'lucide-vue-next';

const props = defineProps({
    type: {
        type: String,
        default: 'info', // 'danger' | 'warning' | 'success' | 'info'
    },
    title: {
        type: String,
        default: ''
    },
    messages: {
        type: Array,
        default: () => []
    },
    dismissible: {
        type: Boolean,
        default: false
    }
});

defineEmits(['dismiss']);

const config = computed(() => {
    switch (props.type) {
        case 'danger':
        case 'red-zone':
            return {
                bg: 'bg-red-50',
                border: 'border-red-200',
                text: 'text-red-900',
                iconColor: 'text-red-600',
                icon: AlertCircle,
            };
        case 'warning':
            return {
                bg: 'bg-amber-50',
                border: 'border-amber-200',
                text: 'text-amber-900',
                iconColor: 'text-amber-600',
                icon: AlertTriangle,
            };
        case 'success':
            return {
                bg: 'bg-emerald-50',
                border: 'border-emerald-200',
                text: 'text-emerald-900',
                iconColor: 'text-emerald-600',
                icon: CheckCircle2,
            };
        default:
            return {
                bg: 'bg-sky-50',
                border: 'border-sky-200',
                text: 'text-sky-900',
                iconColor: 'text-sky-600',
                icon: Info,
            };
    }
});
</script>

<template>
    <div
        :class="[
            'p-4 rounded-2xl border flex items-start gap-3.5 shadow-xs transition-all',
            config.bg,
            config.border,
            config.text
        ]"
    >
        <component
            :is="config.icon"
            :class="['w-5 h-5 shrink-0 mt-0.5', config.iconColor]"
        />

        <div class="flex-1 min-w-0">
            <h4 v-if="title" class="text-sm font-bold tracking-tight">
                {{ title }}
            </h4>

            <div v-if="$slots.default" class="text-xs font-medium leading-relaxed mt-1">
                <slot />
            </div>

            <ul v-else-if="messages.length > 0" class="mt-1 space-y-1 text-xs font-medium">
                <li v-for="(msg, index) in messages" :key="index" class="flex items-start gap-1.5">
                    <span class="text-xs opacity-75">&bull;</span>
                    <span>{{ msg }}</span>
                </li>
            </ul>
        </div>

        <button
            v-if="dismissible"
            type="button"
            @click="$emit('dismiss')"
            class="text-slate-400 hover:text-slate-600 p-1 rounded-lg"
            aria-label="Tutup pemberitahuan"
        >
            <X class="w-4 h-4" />
        </button>
    </div>
</template>
