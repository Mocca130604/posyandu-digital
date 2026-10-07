<script setup>
import { computed } from 'vue';
import {
    CheckCircle2,
    AlertCircle,
    AlertTriangle,
    Info,
    X
} from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    type: {
        type: String,
        default: 'success' // 'success' | 'error' | 'warning' | 'info'
    },
    title: {
        type: String,
        default: ''
    },
    message: {
        type: String,
        default: ''
    },
    errors: {
        type: [Array, Object],
        default: () => []
    },
    confirmText: {
        type: String,
        default: 'Mengerti'
    }
});

const emit = defineEmits(['close']);

const errorList = computed(() => {
    if (!props.errors) return [];
    if (Array.isArray(props.errors)) return props.errors;
    return Object.values(props.errors).flat();
});

const modalConfig = computed(() => {
    switch (props.type) {
        case 'error':
            return {
                icon: AlertCircle,
                iconBg: 'bg-rose-100 text-rose-600 ring-rose-200',
                btnBg: 'bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white shadow-rose-600/25',
                defaultTitle: 'Aksi Gagal Dilakukan'
            };
        case 'warning':
            return {
                icon: AlertTriangle,
                iconBg: 'bg-amber-100 text-amber-700 ring-amber-200',
                btnBg: 'bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white shadow-amber-600/25',
                defaultTitle: 'Perhatian'
            };
        case 'info':
            return {
                icon: Info,
                iconBg: 'bg-sky-100 text-sky-600 ring-sky-200',
                btnBg: 'bg-sky-600 hover:bg-sky-700 active:bg-sky-800 text-white shadow-sky-600/25',
                defaultTitle: 'Informasi Sistem'
            };
        default:
            return {
                icon: CheckCircle2,
                iconBg: 'bg-emerald-100 text-emerald-600 ring-emerald-200',
                btnBg: 'bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white shadow-emerald-600/25',
                defaultTitle: 'Aksi Berhasil!'
            };
    }
});

const close = () => {
    emit('close');
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-slate-900/60 backdrop-blur-xs"
                @click.self="close"
            >
                <div
                    class="relative w-full max-w-md bg-white rounded-3xl p-6 sm:p-7 shadow-2xl border border-slate-100 text-center transform transition-all animate-in fade-in zoom-in-95 duration-200"
                    role="dialog"
                    aria-modal="true"
                >
                    <!-- Close button in top-right -->
                    <button
                        type="button"
                        @click="close"
                        class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-700 flex items-center justify-center transition cursor-pointer"
                        aria-label="Tutup modal"
                    >
                        <X class="w-4 h-4" />
                    </button>

                    <!-- Icon Banner -->
                    <div class="mx-auto w-16 h-16 rounded-2xl flex items-center justify-center ring-8 mb-4" :class="[modalConfig.iconBg]">
                        <component :is="modalConfig.icon" class="w-8 h-8" />
                    </div>

                    <!-- Title -->
                    <h3 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight mb-2">
                        {{ title || modalConfig.defaultTitle }}
                    </h3>

                    <!-- Message Body -->
                    <p v-if="message" class="text-sm font-medium text-slate-600 leading-relaxed mb-4">
                        {{ message }}
                    </p>

                    <!-- Validation Error List (if any) -->
                    <div
                        v-if="errorList.length > 0"
                        class="mb-5 text-left bg-rose-50 border border-rose-200 rounded-2xl p-3.5 max-h-48 overflow-y-auto"
                    >
                        <p class="text-xs font-bold text-rose-800 mb-1.5 flex items-center gap-1.5">
                            <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                            <span>Detail kendala yang perlu diperbaiki:</span>
                        </p>
                        <ul class="space-y-1 text-xs text-rose-700 list-disc list-inside font-medium">
                            <li v-for="(err, idx) in errorList" :key="idx" class="leading-snug">
                                {{ err }}
                            </li>
                        </ul>
                    </div>

                    <!-- Confirm Button -->
                    <div class="mt-4">
                        <button
                            type="button"
                            @click="close"
                            :class="[
                                'w-full py-3 px-5 rounded-2xl text-sm font-bold shadow-lg transition-all cursor-pointer',
                                modalConfig.btnBg
                            ]"
                        >
                            {{ confirmText }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
