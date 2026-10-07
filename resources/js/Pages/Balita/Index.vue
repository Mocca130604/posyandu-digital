<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import {
    Search,
    Plus,
    Baby,
    Filter,
    Activity,
    ChevronRight,
    ShieldAlert,
    Calendar,
    UserPlus,
    X,
    Users
} from 'lucide-vue-next';

const props = defineProps({
    balita: {
        type: Object,
        required: true
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    posyanduList: {
        type: Array,
        default: () => []
    },
    statusStuntingOptions: {
        type: Array,
        default: () => []
    },
    statusGiziOptions: {
        type: Array,
        default: () => []
    },
    statusBbtbOptions: {
        type: Array,
        default: () => []
    }
});

const page = usePage();
const permissions = computed(() => page.props.auth?.permissions || {});

const search = ref(props.filters.cari || '');
const selectedPosyandu = ref(props.filters.posyandu_id || '');
const selectedStunting = ref(props.filters.status_stunting || '');
const selectedGizi = ref(props.filters.status_gizi || '');
const selectedDomisili = ref(props.filters.status_domisili || '');
const isZonaMerahOnly = ref(Boolean(props.filters.zona_merah));

const isFilterDrawerOpen = ref(false);

let searchTimeout = null;

const applyFilters = () => {
    router.get(
        route('balita.index'),
        {
            cari: search.value || undefined,
            posyandu_id: selectedPosyandu.value || undefined,
            status_stunting: selectedStunting.value || undefined,
            status_gizi: selectedGizi.value || undefined,
            status_domisili: selectedDomisili.value || undefined,
            zona_merah: isZonaMerahOnly.value ? 1 : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});

const resetFilters = () => {
    search.value = '';
    selectedPosyandu.value = '';
    selectedStunting.value = '';
    selectedGizi.value = '';
    selectedDomisili.value = '';
    isZonaMerahOnly.value = false;
    applyFilters();
};

const hitungUsiaDeskripsi = (tglLahir) => {
    if (!tglLahir) return '-';
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
</script>

<template>
    <AppLayout title="Data Balita">
        <Head title="Data Balita & Pertumbuhan" />

        <div class="space-y-5">
            <!-- Header & Action Row -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                        <Users class="w-6 h-6 text-rose-600" />
                        <span>Data Pemantauan Balita</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 font-medium">
                        Daftar anak usia 0-60 bulan dengan status pertumbuhan terkini
                    </p>
                </div>

                <div class="flex items-center gap-2.5" v-if="permissions.can_create_balita">
                    <Link
                        :href="route('balita.create')"
                        class="min-h-[48px] px-5 rounded-2xl bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-700 hover:to-rose-600 text-white font-bold text-sm shadow-lg shadow-rose-500/20 flex items-center justify-center gap-2 transition-all active:scale-95"
                    >
                        <UserPlus class="w-4 h-4" />
                        <span>Daftarkan Balita</span>
                    </Link>
                </div>
            </div>

            <!-- Search Bar & Filter Controls -->
            <div class="bg-white rounded-3xl p-4 border border-slate-200/80 shadow-xs space-y-3">
                <div class="flex items-center gap-2.5">
                    <div class="relative flex-1">
                        <Search class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            type="text"
                            v-model="search"
                            placeholder="Cari nama balita, NIK, atau nama ibu..."
                            class="w-full min-h-[48px] pl-12 pr-4 text-sm font-semibold text-slate-900 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-rose-500 focus:ring-4 focus:ring-rose-100 focus:outline-hidden transition-all"
                        />
                    </div>

                    <button
                        type="button"
                        @click="isFilterDrawerOpen = !isFilterDrawerOpen"
                        :class="[
                            'min-h-[48px] px-4 rounded-2xl border-2 flex items-center gap-2 text-sm font-bold transition-all select-none',
                            isFilterDrawerOpen || selectedStunting || selectedGizi || isZonaMerahOnly || selectedPosyandu
                                ? 'bg-rose-50 text-rose-600 border-rose-500'
                                : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300'
                        ]"
                    >
                        <Filter class="w-4 h-4" />
                        <span class="hidden sm:inline">Filter</span>
                    </button>
                </div>

                <!-- Collapsible Filter Tray -->
                <div v-if="isFilterDrawerOpen" class="pt-3 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-5 gap-3 animate-in fade-in duration-150">
                    <!-- Status Stunting -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Status Stunting (TB/U)</label>
                        <select
                            v-model="selectedStunting"
                            @change="applyFilters"
                            class="w-full h-11 text-xs font-semibold rounded-xl border-slate-200 bg-slate-50 focus:border-rose-500"
                        >
                            <option value="">Semua Status</option>
                            <option v-for="opt in statusStuntingOptions" :key="opt" :value="opt">{{ opt }}</option>
                        </select>
                    </div>

                    <!-- Status Gizi -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Status Gizi (BB/U)</label>
                        <select
                            v-model="selectedGizi"
                            @change="applyFilters"
                            class="w-full h-11 text-xs font-semibold rounded-xl border-slate-200 bg-slate-50 focus:border-rose-500"
                        >
                            <option value="">Semua Status</option>
                            <option v-for="opt in statusGiziOptions" :key="opt" :value="opt">{{ opt }}</option>
                        </select>
                    </div>

                    <!-- Status Domisili (Aktif / Pindah) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Status Domisili</label>
                        <select
                            v-model="selectedDomisili"
                            @change="applyFilters"
                            class="w-full h-11 text-xs font-semibold rounded-xl border-slate-200 bg-slate-50 focus:border-rose-500"
                        >
                            <option value="">Semua Domisili</option>
                            <option value="aktif">Aktif Saja</option>
                            <option value="pindah">Pindah Saja</option>
                        </select>
                    </div>

                    <!-- Filter Posyandu (Admin Desa) -->
                    <div v-if="posyanduList.length > 0">
                        <label class="block text-xs font-bold text-slate-600 mb-1">Posyandu</label>
                        <select
                            v-model="selectedPosyandu"
                            @change="applyFilters"
                            class="w-full h-11 text-xs font-semibold rounded-xl border-slate-200 bg-slate-50 focus:border-rose-500"
                        >
                            <option value="">Semua Posyandu</option>
                            <option v-for="p in posyanduList" :key="p.id" :value="p.id">{{ p.nama_posyandu }}</option>
                        </select>
                    </div>

                    <!-- Toggle Zona Merah -->
                    <div class="flex items-end">
                        <button
                            type="button"
                            @click="isZonaMerahOnly = !isZonaMerahOnly; applyFilters()"
                            :class="[
                                'w-full h-11 rounded-xl text-xs font-bold flex items-center justify-center gap-2 border-2 transition-all',
                                isZonaMerahOnly
                                    ? 'bg-red-600 text-white border-red-600 shadow-md shadow-red-500/20'
                                    : 'bg-white text-red-600 border-red-200 hover:bg-red-50'
                            ]"
                        >
                            <ShieldAlert class="w-4 h-4" />
                            <span>Hanya Zona Merah</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- List Balita: Responsive Card Layout for Mobile Screens -->
            <div class="space-y-3">
                <div
                    v-for="item in balita.data"
                    :key="item.id"
                    class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/80 hover:border-rose-200 shadow-xs hover:shadow-md transition-all group"
                >
                    <div class="flex items-start justify-between gap-3">
                        <Link :href="route('balita.show', item.id)" class="flex items-start gap-3.5 flex-1 min-w-0">
                            <!-- Avatar Gender Icon -->
                            <div
                                :class="[
                                    'w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 font-bold text-sm shadow-xs',
                                    item.jenis_kelamin === 'L' ? 'bg-sky-100 text-sky-700' : 'bg-rose-100 text-rose-700'
                                ]"
                            >
                                {{ item.jenis_kelamin === 'L' ? 'L' : 'P' }}
                            </div>

                            <div class="space-y-1 min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-base font-extrabold text-slate-900 group-hover:text-rose-600 transition-colors leading-tight">
                                        {{ item.nama_anak }}
                                    </h3>
                                    <span v-if="item.nik_anak" class="font-mono text-[11px] text-slate-400">
                                        {{ item.nik_anak }}
                                    </span>
                                    <span
                                        v-if="item.status_domisili === 'pindah'"
                                        class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300"
                                    >
                                        PINDAH
                                    </span>
                                </div>

                                <p class="text-xs text-slate-500 leading-snug flex items-center gap-2 flex-wrap">
                                    <span>Usia: <strong>{{ hitungUsiaDeskripsi(item.tanggal_lahir) }}</strong></span>
                                    <span>&bull; Ibu: <strong>{{ item.orang_tua?.nama_ibu || '-' }}</strong></span>
                                    <span v-if="item.posyandu">&bull; {{ item.posyandu.nama_posyandu }}</span>
                                </p>

                                <!-- Status Badges Row -->
                                <div class="flex items-center gap-1.5 flex-wrap pt-2">
                                    <StatusBadge
                                        v-if="item.status_gizi_terakhir"
                                        :status="item.status_gizi_terakhir"
                                        type="gizi"
                                        size="sm"
                                    />
                                    <StatusBadge
                                        v-if="item.status_stunting_terakhir"
                                        :status="item.status_stunting_terakhir"
                                        type="stunting"
                                        size="sm"
                                    />
                                    <StatusBadge
                                        v-if="item.status_bbtb_terakhir"
                                        :status="item.status_bbtb_terakhir"
                                        type="bbtb"
                                        size="sm"
                                    />
                                    <span
                                        v-if="item.kriteria_pmt?.layak_pmt"
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-black bg-amber-100 text-amber-900 border border-amber-300 shadow-2xs"
                                    >
                                        🍲 Sasaran PMT Pemulihan
                                    </span>
                                </div>
                            </div>
                        </Link>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-1.5 shrink-0 pt-1">
                            <Link
                                v-if="permissions.can_create_pengukuran"
                                :href="route('pengukuran.create', { balita_id: item.id })"
                                class="min-h-[40px] px-3 rounded-xl bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-700 text-xs font-bold flex items-center gap-1.5 transition-colors shadow-2xs"
                                title="Catat Pengukuran"
                            >
                                <Activity class="w-3.5 h-3.5" />
                                <span class="hidden sm:inline">Ukur</span>
                            </Link>

                            <Link
                                :href="route('balita.show', item.id)"
                                class="w-10 h-10 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center transition-colors"
                            >
                                <ChevronRight class="w-5 h-5" />
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="balita.data.length === 0" class="py-16 text-center bg-white rounded-3xl border border-slate-200 p-8 space-y-3">
                    <Baby class="w-12 h-12 mx-auto text-slate-300 stroke-[1.5]" />
                    <h3 class="text-base font-bold text-slate-800">Tidak Ada Data Balita Ditemukan</h3>
                    <p class="text-xs text-slate-400 max-w-sm mx-auto">
                        Coba periksa kembali kata kunci pencarian atau bersihkan filter yang sedang aktif.
                    </p>
                    <button
                        type="button"
                        @click="resetFilters"
                        class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors inline-block"
                    >
                        Reset Filter
                    </button>
                </div>
            </div>

            <!-- Pagination Bar -->
            <div v-if="balita.links && balita.links.length > 3" class="flex items-center justify-center gap-1 pt-4">
                <Component
                    v-for="(link, i) in balita.links"
                    :key="i"
                    :is="link.url ? Link : 'span'"
                    :href="link.url || undefined"
                    :class="[
                        'min-h-[40px] min-w-[40px] px-3.5 py-2 rounded-xl text-xs font-bold flex items-center justify-center transition-all select-none',
                        link.active
                            ? 'bg-rose-600 text-white shadow-md shadow-rose-500/20'
                            : link.url
                                ? 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100'
                                : 'text-slate-300 pointer-events-none'
                    ]"
                    v-html="link.label"
                />
            </div>
        </div>
    </AppLayout>
</template>
