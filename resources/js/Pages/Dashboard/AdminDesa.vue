<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Activity,
    AlertTriangle,
    ArrowUpRight,
    Building2,
    CheckCircle2,
    ChevronRight,
    MapPin,
    Search,
    ShieldAlert,
    TrendingDown,
    TrendingUp,
    Users
} from 'lucide-vue-next';

const props = defineProps({
    summary: {
        type: Object,
        required: true
    },
    posyanduStats: {
        type: Array,
        required: true
    }
});

const searchQuery = ref('');

const filteredPosyandu = computed(() => {
    if (!searchQuery.value) return props.posyanduStats;
    const q = searchQuery.value.toLowerCase();
    return props.posyanduStats.filter(p =>
        p.nama_posyandu.toLowerCase().includes(q) ||
        (p.rw && p.rw.toLowerCase().includes(q)) ||
        (p.kelurahan_desa && p.kelurahan_desa.toLowerCase().includes(q))
    );
});

// Deteksi Posyandu Perhatian Khusus (Zona Merah Terbanyak atau Cakupan Rendah)
const posyanduPrioritas = computed(() => {
    return [...props.posyanduStats]
        .filter(p => p.total_zona_merah > 0 || p.cakupan_persen < 70)
        .sort((a, b) => b.total_zona_merah - a.total_zona_merah);
});
</script>

<template>
    <AppLayout title="Dashboard Administrator Desa">
        <Head title="Dashboard Pemantauan Desa" />

        <div class="space-y-6">
            <!-- Header Ringkasan Wilayah -->
            <div class="bg-white rounded-3xl p-5 sm:p-7 border border-slate-200/80 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-50 text-rose-700 text-xs font-bold">
                            <Building2 class="w-3.5 h-3.5" />
                            <span>Pemerintahan Desa / Kelurahan</span>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            Monitoring Kesehatan Balita & Status Gizi Desa
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 font-medium">
                            Rekapitulasi cakupan penimbangan (D/S) dan kewaspadaan zona merah seluruh Posyandu
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('balita.index')"
                            class="min-h-[44px] px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs sm:text-sm flex items-center gap-2 shadow-sm transition-all"
                        >
                            <Users class="w-4 h-4" />
                            <span>Semua Balita Desa</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- KPI Summary Cards Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <!-- 1. Total Sasaran Balita (S) -->
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between text-slate-400">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Sasaran (S)</span>
                        <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center">
                            <Users class="w-4 h-4" />
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900">{{ summary.total_balita }}</div>
                        <p class="text-[11px] text-slate-500 font-medium mt-0.5">
                            Dari {{ summary.total_posyandu }} Posyandu aktif
                        </p>
                    </div>
                </div>

                <!-- 2. Persentase Kehadiran Posyandu Desa (D/S) -->
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between text-slate-400">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-700">Kehadiran Posyandu (D/S)</span>
                        <div
                            :class="[
                                'w-8 h-8 rounded-xl flex items-center justify-center font-bold text-xs',
                                summary.cakupan_desa_persen >= 80 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'
                            ]"
                        >
                            {{ summary.cakupan_desa_persen }}%
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900">
                            {{ summary.total_ditimbang_bulan_ini }} <span class="text-sm font-bold text-slate-500">/ {{ summary.total_balita }}</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2 overflow-hidden">
                            <div
                                :class="[
                                    'h-full rounded-full transition-all',
                                    summary.cakupan_desa_persen >= 80 ? 'bg-emerald-500' : 'bg-amber-500'
                                ]"
                                :style="{ width: `${Math.min(100, summary.cakupan_desa_persen)}%` }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- 3. Balita Zona Merah (Perlu Perhatian) -->
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-red-200 bg-red-50/20 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between text-red-500">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-red-700">Zona Merah Desa</span>
                        <div class="w-8 h-8 rounded-xl bg-red-100 text-red-700 flex items-center justify-center">
                            <ShieldAlert class="w-4 h-4" />
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-red-600">
                            {{ summary.total_zona_merah }}
                        </div>
                        <p class="text-[11px] text-red-700 font-bold mt-0.5">
                            Balita butuh intervensi gizi / rujukan
                        </p>
                    </div>
                </div>

                <!-- 4. Prevalensi Stunting Desa -->
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between text-slate-400">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Prevalensi Stunting</span>
                        <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center">
                            <Activity class="w-4 h-4" />
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900">
                            {{ summary.total_stunting }}
                            <span class="text-sm font-bold text-slate-500">
                                ({{ summary.total_balita > 0 ? Math.round((summary.total_stunting / summary.total_balita) * 1000) / 10 : 0 }}%)
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 font-medium mt-0.5">
                            Target Nasional: &lt; 14%
                        </p>
                    </div>
                </div>
            </div>

            <!-- Panel Peringatan Prioritas / Atensi Khusus -->
            <div v-if="posyanduPrioritas.length > 0" class="bg-amber-50/60 border border-amber-200 rounded-3xl p-5 sm:p-6 space-y-3">
                <div class="flex items-center gap-2 text-amber-900 font-black text-sm">
                    <AlertTriangle class="w-5 h-5 text-amber-600 shrink-0" />
                    <span>Perhatian Prioritas Intervensi Desa</span>
                </div>
                <p class="text-xs text-amber-800 leading-relaxed">
                    Ditemukan posyandu dengan balita di <strong>Zona Merah</strong> (stunting / gizi buruk) atau cakupan penimbangan di bawah 70%. Koordinasikan dengan Bidan Desa dan Kader terkait Pemberian Makanan Tambahan (PMT) serta sweeping penimbangan ke rumah sasaran.
                </p>
            </div>

            <!-- Tabel & Kartu Rincian Per Posyandu -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden space-y-4">
                <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">
                            Data Cakupan & Status Gizi Per Posyandu
                        </h2>
                        <p class="text-xs text-slate-500 font-medium">
                            Menampilkan seluruh {{ posyanduStats.length }} Posyandu terdaftar di wilayah desa
                        </p>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full sm:w-72">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="Cari nama posyandu / RW..."
                            class="w-full h-10 pl-10 pr-4 rounded-xl border border-slate-200 text-xs font-semibold focus:border-rose-500"
                        />
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-5">Nama Posyandu</th>
                                <th class="py-3.5 px-4 text-center">Sasaran (S)</th>
                                <th class="py-3.5 px-4 text-center">Ditimbang (D)</th>
                                <th class="py-3.5 px-4 text-center">Cakupan (D/S)</th>
                                <th class="py-3.5 px-4 text-center">Stunting</th>
                                <th class="py-3.5 px-4 text-center">Gizi Buruk/Kurang</th>
                                <th class="py-3.5 px-4 text-center">Zona Merah</th>
                                <th class="py-3.5 px-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="p in filteredPosyandu"
                                :key="p.id"
                                :class="[
                                    'transition-colors',
                                    p.total_zona_merah > 0 ? 'bg-red-50/30 hover:bg-red-50/60' : 'hover:bg-slate-50'
                                ]"
                            >
                                <td class="py-3.5 px-5">
                                    <div class="font-bold text-slate-900 text-sm">{{ p.nama_posyandu }}</div>
                                    <div class="text-[11px] text-slate-500">
                                        RW {{ p.rw || '-' }} / RT {{ p.rt || '-' }} &bull; {{ p.kelurahan_desa }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold text-slate-800">{{ p.total_balita }}</td>
                                <td class="py-3.5 px-4 text-center font-bold text-slate-800">{{ p.ditimbang_bulan_ini }}</td>
                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        :class="[
                                            'px-2.5 py-1 rounded-full text-xs font-black',
                                            p.cakupan_persen >= 80 ? 'bg-emerald-100 text-emerald-800' :
                                            p.cakupan_persen >= 60 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800'
                                        ]"
                                    >
                                        {{ p.cakupan_persen }}%
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span :class="p.total_stunting > 0 ? 'font-bold text-rose-700' : 'text-slate-600'">
                                        {{ p.total_stunting }} ({{ p.prevalensi_stunting }}%)
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span :class="p.total_gizi_buruk_kurang > 0 ? 'font-bold text-amber-700' : 'text-slate-600'">
                                        {{ p.total_gizi_buruk_kurang }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        v-if="p.total_zona_merah > 0"
                                        class="px-2.5 py-1 rounded-full bg-red-600 text-white font-black text-xs inline-block"
                                    >
                                        {{ p.total_zona_merah }} Kasus
                                    </span>
                                    <span v-else class="text-emerald-600 font-bold text-xs">
                                        Nihil
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-right">
                                    <Link
                                        :href="route('balita.index', { posyandu_id: p.id })"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-700 font-bold transition-colors"
                                    >
                                        <span>Rincian</span>
                                        <ChevronRight class="w-3.5 h-3.5" />
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View for Mid-Range Android Phones -->
                <div class="md:hidden px-4 pb-4 space-y-3">
                    <div
                        v-for="p in filteredPosyandu"
                        :key="p.id"
                        :class="[
                            'p-4 rounded-2xl border transition-all space-y-3',
                            p.total_zona_merah > 0
                                ? 'bg-red-50/40 border-red-200 border-l-4 border-l-red-600'
                                : 'bg-white border-slate-200'
                        ]"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h3 class="font-extrabold text-sm text-slate-900">{{ p.nama_posyandu }}</h3>
                                <p class="text-xs text-slate-500">RW {{ p.rw || '-' }} / RT {{ p.rt || '-' }}</p>
                            </div>
                            <span
                                :class="[
                                    'px-2 py-0.5 rounded-md text-[11px] font-black',
                                    p.cakupan_persen >= 80 ? 'bg-emerald-100 text-emerald-800' :
                                    p.cakupan_persen >= 60 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800'
                                ]"
                            >
                                Cakupan: {{ p.cakupan_persen }}%
                            </span>
                        </div>

                        <div class="grid grid-cols-3 gap-2 text-center text-xs">
                            <div class="p-2 rounded-xl bg-slate-50 border border-slate-200">
                                <span class="text-[10px] text-slate-400 block font-bold">Sasaran</span>
                                <span class="font-black text-slate-800 text-sm">{{ p.total_balita }}</span>
                            </div>
                            <div class="p-2 rounded-xl bg-slate-50 border border-slate-200">
                                <span class="text-[10px] text-slate-400 block font-bold">Ditimbang</span>
                                <span class="font-black text-slate-800 text-sm">{{ p.ditimbang_bulan_ini }}</span>
                            </div>
                            <div class="p-2 rounded-xl bg-slate-50 border border-slate-200">
                                <span class="text-[10px] text-slate-400 block font-bold">Zona Merah</span>
                                <span :class="p.total_zona_merah > 0 ? 'font-black text-red-600 text-sm' : 'font-bold text-slate-600 text-sm'">
                                    {{ p.total_zona_merah }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <span class="text-xs text-slate-500">
                                Stunting: <strong>{{ p.total_stunting }}</strong> ({{ p.prevalensi_stunting }}%)
                            </span>
                            <Link
                                :href="route('balita.index', { posyandu_id: p.id })"
                                class="inline-flex items-center gap-1 text-xs font-bold text-rose-600 hover:text-rose-700"
                            >
                                <span>Lihat Data Balita</span>
                                <ChevronRight class="w-3.5 h-3.5" />
                            </Link>
                        </div>
                    </div>

                    <div v-if="filteredPosyandu.length === 0" class="py-8 text-center text-xs text-slate-400">
                        Tidak ada posyandu yang cocok dengan pencarian "{{ searchQuery }}".
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
