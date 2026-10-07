<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Search, ChevronDown, Check, X, Baby, Calendar } from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: [Number, String],
        default: null
    },
    items: {
        type: Array,
        default: () => []
    },
    label: {
        type: String,
        default: 'Pilih Balita'
    },
    placeholder: {
        type: String,
        default: 'Ketik nama anak atau 16 digit NIK...'
    },
    error: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue', 'selected']);

const isOpen = ref(false);
const searchQuery = ref('');
const dropdownRef = ref(null);

const selectedItem = computed(() => {
    return props.items.find(item => String(item.id) === String(props.modelValue)) || null;
});

const filteredItems = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.items.slice(0, 30);
    }
    const q = searchQuery.value.toLowerCase().trim();
    return props.items.filter(item => {
        const nama = (item.nama_anak || '').toLowerCase();
        const nik = (item.nik_anak || '').toLowerCase();
        const ibu = (item.orang_tua?.nama_ibu || '').toLowerCase();
        return nama.includes(q) || nik.includes(q) || ibu.includes(q);
    }).slice(0, 30);
});

const selectItem = (item) => {
    emit('update:modelValue', item.id);
    emit('selected', item);
    isOpen.value = false;
    searchQuery.value = '';
};

const clearSelection = (e) => {
    e.stopPropagation();
    emit('update:modelValue', null);
    emit('selected', null);
};

const hitungUsiaDeskripsi = (tglLahir) => {
    if (!tglLahir) return '';
    const birth = new Date(tglLahir);
    const now = new Date();
    let months = (now.getFullYear() - birth.getFullYear()) * 12 + (now.getMonth() - birth.getMonth());
    if (now.getDate() < birth.getDate()) months--;
    if (months < 0) return '0 bulan';
    const years = Math.floor(months / 12);
    const remMonths = months % 12;
    if (years > 0) {
        return remMonths > 0 ? `${years} th ${remMonths} bln` : `${years} tahun`;
    }
    return `${months} bulan`;
};

const handleClickOutside = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="w-full flex flex-col gap-1.5" ref="dropdownRef">
        <label class="text-sm font-bold text-slate-800">
            {{ label }}
        </label>

        <div class="relative">
            <!-- Selected Trigger Button -->
            <button
                type="button"
                @click="isOpen = !isOpen"
                :disabled="disabled"
                :class="[
                    'w-full min-h-[52px] px-4 py-2.5 rounded-2xl bg-white border-2 text-left flex items-center justify-between gap-3 transition-all shadow-xs',
                    error ? 'border-red-400 focus:border-red-500' : 'border-slate-200 hover:border-slate-300 focus:border-rose-500',
                    isOpen ? 'border-rose-500 ring-4 ring-rose-100' : '',
                    disabled ? 'opacity-60 bg-slate-50 cursor-not-allowed' : ''
                ]"
            >
                <div v-if="selectedItem" class="flex items-center gap-3 truncate">
                    <div
                        :class="[
                            'w-9 h-9 rounded-xl flex items-center justify-center shrink-0 text-xs font-bold',
                            selectedItem.jenis_kelamin === 'L' ? 'bg-sky-100 text-sky-700' : 'bg-rose-100 text-rose-700'
                        ]"
                    >
                        {{ selectedItem.jenis_kelamin }}
                    </div>
                    <div class="truncate">
                        <p class="text-base font-bold text-slate-900 leading-tight truncate">
                            {{ selectedItem.nama_anak }}
                        </p>
                        <p class="text-xs text-slate-500 leading-tight flex items-center gap-2 mt-0.5">
                            <span>Usia: <strong>{{ hitungUsiaDeskripsi(selectedItem.tanggal_lahir) }}</strong></span>
                            <span v-if="selectedItem.nik_anak" class="font-mono text-[11px]">&bull; {{ selectedItem.nik_anak }}</span>
                        </p>
                    </div>
                </div>

                <div v-else class="text-slate-400 text-sm font-medium flex items-center gap-2">
                    <Search class="w-4 h-4 text-slate-400" />
                    <span>{{ placeholder }}</span>
                </div>

                <div class="flex items-center gap-1 shrink-0">
                    <button
                        v-if="selectedItem && !disabled"
                        type="button"
                        @click="clearSelection"
                        class="p-1 rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100"
                    >
                        <X class="w-4 h-4" />
                    </button>
                    <ChevronDown class="w-5 h-5 text-slate-400 transition-transform" :class="{ 'rotate-180': isOpen }" />
                </div>
            </button>

            <!-- Dropdown Flyout Panel -->
            <div
                v-if="isOpen"
                class="absolute left-0 right-0 mt-2 bg-white rounded-2xl shadow-2xl border border-slate-200 p-2 z-50 animate-in fade-in zoom-in-95 duration-100"
            >
                <!-- Search Box -->
                <div class="relative mb-2">
                    <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input
                        type="text"
                        v-model="searchQuery"
                        placeholder="Cari nama balita, NIK, atau nama ibu..."
                        autofocus
                        class="w-full h-11 pl-10 pr-4 text-sm font-medium text-slate-900 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-rose-500 focus:ring-2 focus:ring-rose-200 focus:outline-hidden"
                    />
                </div>

                <!-- Result List -->
                <div class="max-h-64 overflow-y-auto divide-y divide-slate-100 overscroll-contain">
                    <button
                        v-for="item in filteredItems"
                        :key="item.id"
                        type="button"
                        @click="selectItem(item)"
                        class="w-full p-3 rounded-xl flex items-center justify-between text-left hover:bg-rose-50/60 active:bg-rose-100/70 transition-colors"
                    >
                        <div class="flex items-center gap-3 truncate">
                            <div
                                :class="[
                                    'w-8 h-8 rounded-lg flex items-center justify-center shrink-0 text-xs font-bold',
                                    item.jenis_kelamin === 'L' ? 'bg-sky-100 text-sky-700' : 'bg-rose-100 text-rose-700'
                                ]"
                            >
                                {{ item.jenis_kelamin }}
                            </div>
                            <div class="truncate">
                                <p class="text-sm font-bold text-slate-900 truncate">
                                    {{ item.nama_anak }}
                                </p>
                                <p class="text-xs text-slate-500 flex items-center gap-1.5 mt-0.5">
                                    <span>Usia: {{ hitungUsiaDeskripsi(item.tanggal_lahir) }}</span>
                                    <span v-if="item.orang_tua?.nama_ibu">&bull; Ibu {{ item.orang_tua.nama_ibu }}</span>
                                </p>
                            </div>
                        </div>

                        <Check
                            v-if="String(item.id) === String(modelValue)"
                            class="w-5 h-5 text-rose-600 shrink-0"
                        />
                    </button>

                    <div v-if="filteredItems.length === 0" class="py-8 text-center text-sm text-slate-400">
                        Tidak ada balita yang cocok dengan kata kunci pencarian.
                    </div>
                </div>
            </div>
        </div>

        <p v-if="error" class="text-xs font-semibold text-red-600 mt-0.5">
            {{ error }}
        </p>
    </div>
</template>
