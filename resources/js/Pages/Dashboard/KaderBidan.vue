<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatTanggal } from '@/utils';
import {
    Activity,
    AlertCircle,
    AlertTriangle,
    Baby,
    Calendar,
    CheckCircle2,
    Clock,
    FileSpreadsheet,
    HeartPulse,
    MapPin,
    Plus,
    Search,
    ShieldAlert,
    Syringe,
    TrendingUp,
    UserCheck,
    Users
} from 'lucide-vue-next';

const props = defineProps({
    posyandu: {
        type: Object,
        default: null
    },
    summary: {
        type: Object,
        required: true
    },
    anakZonaMerah: {
        type: Array,
        default: () => []
    },
    anakBelumDitimbang: {
        type: Array,
        default: () => []
    },
    vaksinTertunda: {
        type: Array,
        default: () => []
    }
});

// Active Tab: 'zona_merah' | 'belum_ditimbang' | 'vaksin_tertunda'
const activeTab = ref('zona_merah');

// Perhitungan Persentase Kehadiran Posyandu (Cakupan Ditimbang D/S)
const persenKehadiran = computed(() => {
    if (!props.summary.total_balita || props.summary.total_balita === 0) return 0;
    return Math.round((props.summary.total_ditimbang_bulan_ini / props.summary.total_balita) * 100);
});

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
        return remMonths > 0 ? `${years} th ${remMonths} bln` : `${years} th`;
    }
    return `${months} bln`;
};
</script>

<template>
    <AppLayout title="Dashboard Kader & Bidan Posyandu">
        <Head title="Dashboard Posyandu" />

        <div class="space-y-6">
            <!-- Header Posyandu & Aksi Cepat -->
            <div class="bg-white rounded-3xl p-5 sm:p-7 border border-slate-200/80 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-50 text-rose-700 text-xs font-bold">
                            <HeartPulse class="w-3.5 h-3.5" />
                            <span>{{ posyandu ? posyandu.nama_posyandu : 'Posyandu Desa' }}</span>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            Ruang Kerja Posyandu & Pelacakan Balita
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 font-medium">
                            <span v-if="posyandu">RW {{ posyandu.rw || '-' }} / RT {{ posyandu.rt || '-' }} &bull; {{ posyandu.kelurahan_desa }}</span>
                            <span v-else>Monitoring Tumbuh Kembang & Imunisasi Balita</span>
                        </p>
                    </div>

                    <!-- Tombol Aksi Cepat Touch Friendly -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <Link
                            :href="route('pengukuran.create')"
                            class="min-h-[44px] px-4 rounded-xl bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-700 hover:to-rose-600 text-white font-bold text-xs sm:text-sm flex items-center gap-2 shadow-md shadow-rose-500/20 transition-all active:scale-95"
                        >
                            <Activity class="w-4 h-4" />
                            <span>Ukur Antropometri</span>
                        </Link>

                        <Link
                            :href="route('balita.create')"
                            class="min-h-[44px] px-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs sm:text-sm flex items-center gap-2 transition-all active:scale-95"
                        >
                            <Plus class="w-4 h-4" />
                            <span>Balita Baru</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- KARTU UTAMA PERSENTASE KEHADIRAN POSYANDU -->
            <div class="bg-gradient-to-br from-white via-white to-rose-50/40 rounded-3xl p-5 sm:p-6 border-2 border-rose-200/90 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div class="space-y-2 flex-1">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold">
                        <Calendar class="w-3.5 h-3.5" />
                        <span>Jadwal Rutin: Senin Minggu ke-2 Setiap Bulan</span>
                    </div>
                    <h2 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">
                        Persentase Kehadiran Penimbangan Posyandu (D/S)
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600 font-medium leading-relaxed">
                        Sebanyak <strong class="text-slate-900 font-black">{{ summary.total_ditimbang_bulan_ini }} dari {{ summary.total_balita }} balita</strong> hadir dan telah diukur pada bulan ini. 
                        <span v-if="summary.total_belum_ditimbang > 0" class="text-amber-700 font-bold">
                            Terdapat {{ summary.total_belum_ditimbang }} balita belum hadir (perlu sweeping kader).
                        </span>
                    </p>

                    <!-- Progress Bar Visual -->
                    <div class="pt-1 max-w-md">
                        <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden border border-slate-200 p-0.5">
                            <div
                                :class="[
                                    'h-full rounded-full transition-all duration-500',
                                    persenKehadiran >= 80 ? 'bg-emerald-500' : (persenKehadiran >= 60 ? 'bg-amber-500' : 'bg-rose-500')
                                ]"
                                :style="{ width: `${Math.min(persenKehadiran, 100)}%` }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Indikator Angka Persentase Besar -->
                <div class="flex items-center gap-4 shrink-0 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/90 shadow-2xs">
                    <div class="text-right">
                        <div class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight leading-none">
                            {{ persenKehadiran }}%
                        </div>
                        <p :class="[
                            'text-xs font-bold mt-1.5',
                            persenKehadiran >= 80 ? 'text-emerald-600' : (persenKehadiran >= 60 ? 'text-amber-600' : 'text-rose-600')
                        ]">
                            {{ persenKehadiran >= 80 ? 'Target Kemenkes Tercapai' : 'Target Kemenkes >= 80%' }}
                        </p>
                    </div>
                    <div
                        :class="[
                            'w-14 h-14 rounded-2xl flex items-center justify-center font-bold text-lg shrink-0 shadow-xs',
                            persenKehadiran >= 80 ? 'bg-emerald-100 text-emerald-700' : (persenKehadiran >= 60 ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700')
                        ]"
                    >
                        <TrendingUp class="w-7 h-7" />
                    </div>
                </div>
            </div>

            <!-- KPI Metric Cards Grid (5 Metrik Utama) -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
                <!-- 1. Sasaran Balita -->
                <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs space-y-1.5">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Sasaran Balita</span>
                    <div class="text-2xl font-black text-slate-900">{{ summary.total_balita }}</div>
                    <p class="text-[11px] text-slate-500 font-medium">Terdaftar di Posyandu</p>
                </div>

                <!-- 2. Persentase Kehadiran (D/S) -->
                <div class="bg-white rounded-2xl p-4 border border-emerald-200 bg-emerald-50/20 shadow-2xs space-y-1.5">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 block">Kehadiran (D/S)</span>
                    <div class="text-2xl font-black text-emerald-700">
                        {{ persenKehadiran }}%
                    </div>
                    <p class="text-[11px] text-emerald-700 font-medium">{{ summary.total_ditimbang_bulan_ini }} dari {{ summary.total_balita }} hadir</p>
                </div>

                <!-- 3. Belum Ditimbang (Sweeping) -->
                <div class="bg-white rounded-2xl p-4 border border-amber-200 bg-amber-50/20 shadow-2xs space-y-1.5">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 block">Belum Hadir</span>
                    <div class="text-2xl font-black text-amber-600">{{ summary.total_belum_ditimbang }}</div>
                    <p class="text-[11px] text-amber-700 font-medium">Perlu sweeping kader</p>
                </div>

                <!-- 4. Zona Merah (Tindak Lanjut) -->
                <div class="bg-white rounded-2xl p-4 border border-red-200 bg-red-50/25 shadow-2xs space-y-1.5">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-red-700 block">Zona Merah</span>
                    <div class="text-2xl font-black text-red-600">{{ summary.total_zona_merah }}</div>
                    <p class="text-[11px] text-red-700 font-bold">Intervensi & Rujukan</p>
                </div>

                <!-- 5. Vaksin Tertunda -->
                <div class="col-span-2 lg:col-span-1 bg-white rounded-2xl p-4 border border-rose-200 bg-rose-50/20 shadow-2xs space-y-1.5">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-rose-700 block">Vaksin Tertunda</span>
                    <div class="text-2xl font-black text-rose-600">{{ summary.total_vaksin_tertunda }}</div>
                    <p class="text-[11px] text-rose-700 font-medium">Jadwal vaksinasi lewat</p>
                </div>
            </div>

            <!-- Segmented Control Tabs -->
            <div class="flex items-center gap-1.5 sm:gap-2 p-1.5 bg-slate-200/60 rounded-2xl max-w-xl text-xs font-bold overflow-x-auto">
                <button
                    type="button"
                    @click="activeTab = 'zona_merah'"
                    :class="[
                        'flex-1 min-h-[42px] px-3 rounded-xl transition-all flex items-center justify-center gap-2 select-none whitespace-nowrap',
                        activeTab === 'zona_merah'
                            ? 'bg-white text-red-600 shadow-md shadow-slate-900/5'
                            : 'text-slate-600 hover:text-slate-900'
                    ]"
                >
                    <ShieldAlert class="w-4 h-4 shrink-0 text-red-600" />
                    <span>Zona Merah ({{ anakZonaMerah.length }})</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'belum_ditimbang'"
                    :class="[
                        'flex-1 min-h-[42px] px-3 rounded-xl transition-all flex items-center justify-center gap-2 select-none whitespace-nowrap',
                        activeTab === 'belum_ditimbang'
                            ? 'bg-white text-amber-600 shadow-md shadow-slate-900/5'
                            : 'text-slate-600 hover:text-slate-900'
                    ]"
                >
                    <Clock class="w-4 h-4 shrink-0 text-amber-600" />
                    <span>Sweeping Penimbangan ({{ anakBelumDitimbang.length }})</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'vaksin_tertunda'"
                    :class="[
                        'flex-1 min-h-[42px] px-3 rounded-xl transition-all flex items-center justify-center gap-2 select-none whitespace-nowrap',
                        activeTab === 'vaksin_tertunda'
                            ? 'bg-white text-rose-600 shadow-md shadow-slate-900/5'
                            : 'text-slate-600 hover:text-slate-900'
                    ]"
                >
                    <Syringe class="w-4 h-4 shrink-0 text-rose-600" />
                    <span>Vaksin Tertunda ({{ vaksinTertunda.length }})</span>
                </button>
            </div>

            <!-- TAB 1: ANAK ZONA MERAH (TINDAK LANJUT) -->
            <div v-if="activeTab === 'zona_merah'" class="space-y-4">
                <div class="bg-red-50/70 border border-red-200 rounded-2xl p-4 flex items-start gap-3 text-xs text-red-900">
                    <ShieldAlert class="w-5 h-5 text-red-600 shrink-0 mt-0.5" />
                    <div class="leading-relaxed">
                        <strong>Daftar Anak Perlu Tindak Lanjut:</strong> Anak-anak dengan status Gizi Buruk/Kurang, Severely Stunted/Stunted, atau Berat Badan Tidak Naik 2 kali berturut-turut (T-T). Prioritaskan kunjungan rumah (Pemberian Makanan Tambahan PMT lokal) atau rujukan ke Puskesmas.
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div
                        v-for="anak in anakZonaMerah"
                        :key="anak.id"
                        class="bg-white rounded-2xl p-4 border border-red-200 border-l-4 border-l-red-600 shadow-2xs space-y-3"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h3 class="font-extrabold text-sm text-slate-900">{{ anak.nama_anak }}</h3>
                                <p class="text-xs text-slate-500 font-medium">
                                    Usia: {{ hitungUsiaDeskripsi(anak.tanggal_lahir) }} &bull; Ibu: {{ anak.orang_tua?.nama_ibu || '-' }}
                                </p>
                            </div>
                            <span class="px-2 py-0.5 rounded-md bg-red-100 text-red-700 text-[10px] font-black uppercase">
                                Zona Merah
                            </span>
                        </div>

                        <!-- Status Terakhir Badges -->
                        <div class="flex flex-wrap items-center gap-1.5">
                            <StatusBadge
                                v-if="anak.status_gizi_terakhir"
                                :status="anak.status_gizi_terakhir"
                                type="gizi"
                                size="sm"
                            />
                            <StatusBadge
                                v-if="anak.status_stunting_terakhir"
                                :status="anak.status_stunting_terakhir"
                                type="stunting"
                                size="sm"
                            />
                            <StatusBadge
                                v-if="anak.status_bbtb_terakhir"
                                :status="anak.status_bbtb_terakhir"
                                type="bbtb"
                                size="sm"
                            />
                        </div>

                        <!-- Hasil Pengukuran Terakhir -->
                        <div v-if="anak.pengukuran_terbaru" class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-1">
                            <div class="flex justify-between text-slate-500 text-[11px]">
                                <span>Timbang: <strong class="text-slate-700">{{ formatTanggal(anak.pengukuran_terbaru.tanggal_kunjungan) }}</strong></span>
                                <StatusBadge :status="anak.pengukuran_terbaru.kenaikan_bb" type="kenaikan" size="sm" />
                            </div>
                            <div class="flex justify-between font-bold text-slate-800">
                                <span>BB: {{ anak.pengukuran_terbaru.berat_badan_kg }} kg</span>
                                <span>TB: {{ anak.pengukuran_terbaru.tinggi_badan_cm }} cm</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-1 flex items-center justify-between gap-2">
                            <Link
                                :href="route('balita.show', anak.id)"
                                class="text-xs font-bold text-slate-700 hover:text-rose-600"
                            >
                                Buka Profil & KMS &rarr;
                            </Link>

                            <Link
                                :href="route('pengukuran.create', { balita_id: anak.id })"
                                class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs"
                            >
                                Ukur Ulang
                            </Link>
                        </div>
                    </div>

                    <div v-if="anakZonaMerah.length === 0" class="col-span-full py-12 text-center text-xs text-slate-400 bg-white rounded-3xl border border-slate-200">
                        <CheckCircle2 class="w-8 h-8 text-emerald-500 mx-auto mb-2" />
                        <p class="font-bold text-slate-700 text-sm">Alhamdulillah, Tidak Ada Balita di Zona Merah</p>
                        <p class="text-slate-400 mt-1">Seluruh balita terpantau memiliki status gizi dan pertumbuhan yang baik.</p>
                    </div>
                </div>
            </div>

            <!-- TAB 2: SWEEPING BELUM DITIMBANG (BULAN BERJALAN) -->
            <div v-if="activeTab === 'belum_ditimbang'" class="space-y-4">
                <div class="bg-amber-50/70 border border-amber-200 rounded-2xl p-4 flex items-start gap-3 text-xs text-amber-900">
                    <Clock class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                    <div class="leading-relaxed">
                        <strong>Target Sweeping Posyandu:</strong> Balita-balita berikut belum memiliki catatan pengukuran pada bulan berjalan. Lakukan kunjungan rumah atau ingatkan orang tua untuk hadir di Posyandu agar tidak terputus pemantauan KMS.
                    </div>
                </div>

                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="divide-y divide-slate-100">
                        <div
                            v-for="anak in anakBelumDitimbang"
                            :key="anak.id"
                            class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ anak.jenis_kelamin === 'L' ? 'L' : 'P' }}
                                </div>
                                <div>
                                    <h4 class="font-extrabold text-sm text-slate-900">{{ anak.nama_anak }}</h4>
                                    <p class="text-xs text-slate-500">
                                        Usia: {{ hitungUsiaDeskripsi(anak.tanggal_lahir) }} &bull; Ibu: {{ anak.orang_tua?.nama_ibu || '-' }}
                                    </p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">
                                        Alamat: {{ anak.orang_tua?.alamat || '-' }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 self-end sm:self-center">
                                <Link
                                    :href="route('balita.show', anak.id)"
                                    class="px-3 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-bold"
                                >
                                    Profil
                                </Link>
                                <Link
                                    :href="route('pengukuran.create', { balita_id: anak.id })"
                                    class="px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs flex items-center gap-1.5"
                                >
                                    <Activity class="w-3.5 h-3.5" />
                                    <span>Timbang Sekarang</span>
                                </Link>
                            </div>
                        </div>

                        <div v-if="anakBelumDitimbang.length === 0" class="py-12 text-center text-xs text-slate-400">
                            <CheckCircle2 class="w-8 h-8 text-emerald-500 mx-auto mb-2" />
                            <p class="font-bold text-slate-700 text-sm">Semua Balita Sudah Ditimbang Bulan Ini</p>
                            <p class="text-slate-400 mt-1">Cakupan penimbangan posyandu telah mencapai 100%.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: DETEKSI VAKSIN TERTUNDA -->
            <div v-if="activeTab === 'vaksin_tertunda'" class="space-y-4">
                <div class="bg-rose-50/70 border border-rose-200 rounded-2xl p-4 flex items-start gap-3 text-xs text-rose-900">
                    <Syringe class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
                    <div class="leading-relaxed">
                        <strong>Deteksi Imunisasi Tertunda:</strong> Balita-balita berikut telah melewati target usia pemberian imunisasi nasional namun belum menerima dosis vaksin yang ditentukan. Jadwalkan pemberian saat Posyandu berikutnya atau koordinasikan dengan Bidan Desa.
                    </div>
                </div>

                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="divide-y divide-slate-100">
                        <div
                            v-for="(item, idx) in vaksinTertunda"
                            :key="`${item.balita_id}-${item.vaksin_kode}`"
                            class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                                    <Syringe class="w-5 h-5" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-extrabold text-sm text-slate-900">{{ item.nama_anak }}</h4>
                                        <span class="px-2 py-0.5 rounded-md bg-rose-100 text-rose-800 text-[10px] font-black">
                                            Terlambat {{ item.keterlambatan_bulan }} Bulan
                                        </span>
                                    </div>
                                    <p class="text-xs font-bold text-rose-600 mt-0.5">
                                        {{ item.vaksin_nama }} (Target: Usia {{ item.usia_target }} Bulan)
                                    </p>
                                    <p class="text-[11px] text-slate-400">
                                        Usia saat ini: {{ item.usia_bulan }} bulan &bull; Ibu: {{ item.nama_ibu }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 self-end sm:self-center">
                                <Link
                                    :href="route('balita.show', item.balita_id)"
                                    class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs flex items-center gap-1.5"
                                >
                                    <span>Buka Jadwal Vaksin</span>
                                </Link>
                            </div>
                        </div>

                        <div v-if="vaksinTertunda.length === 0" class="py-12 text-center text-xs text-slate-400">
                            <CheckCircle2 class="w-8 h-8 text-emerald-500 mx-auto mb-2" />
                            <p class="font-bold text-slate-700 text-sm">Tidak Ada Jadwal Vaksin yang Tertunda</p>
                            <p class="text-slate-400 mt-1">Seluruh imunisasi balita telah terpenuhi sesuai target usianya.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
