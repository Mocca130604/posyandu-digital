<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useForm, usePage, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BigInput from '@/Components/BigInput.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AlertBanner from '@/Components/AlertBanner.vue';
import ToggleCheckbox from '@/Components/ToggleCheckbox.vue';
import {
    Activity,
    ArrowLeft,
    Baby,
    Calendar,
    Check,
    CheckCircle2,
    FileText,
    HelpCircle,
    Info,
    Loader2,
    Pill,
    Ruler,
    Scale,
    ShieldAlert,
    Sparkles,
    Syringe,
    WifiOff
} from 'lucide-vue-next';

const props = defineProps({
    balitaList: {
        type: Array,
        default: () => []
    },
    balitaTerpilih: {
        type: Object,
        default: null
    },
    pengukuranTerakhir: {
        type: Object,
        default: null
    },
    caraUkurOptions: {
        type: Array,
        default: () => []
    },
    defaultTanggalKunjungan: {
        type: String,
        default: ''
    }
});

const page = usePage();
const permissions = computed(() => page.props.auth?.permissions || {});

// Inertia Form State
const form = useForm({
    balita_id: props.balitaTerpilih?.id || null,
    tanggal_kunjungan: props.defaultTanggalKunjungan || new Date().toISOString().split('T')[0],
    berat_badan_kg: '',
    tinggi_badan_cm: '',
    cara_ukur_tinggi: 'telentang',
    lingkar_lengan_atas_cm: '',
    lingkar_kepala_cm: '',
    vit_a_diberikan: false,
    obat_cacing_diberikan: false,
    catatan_kader: '',
});

// Selected Child Detail Object
const selectedChild = ref(props.balitaTerpilih);

// Watcher when SearchableSelect changes
watch(() => form.balita_id, (newId) => {
    selectedChild.value = props.balitaList.find(b => b.id === Number(newId)) || null;
    // Set auto-suggested cara_ukur based on age
    if (clientComputedAgeMonths.value >= 24) {
        form.cara_ukur_tinggi = 'berdiri';
    } else {
        form.cara_ukur_tinggi = 'telentang';
    }
});

// Instant Client-Side Age Computation (Completed Months / Floor)
const clientComputedAgeMonths = computed(() => {
    if (!selectedChild.value?.tanggal_lahir || !form.tanggal_kunjungan) {
        return null;
    }
    const birth = new Date(selectedChild.value.tanggal_lahir);
    const visit = new Date(form.tanggal_kunjungan);

    let months = (visit.getFullYear() - birth.getFullYear()) * 12 + (visit.getMonth() - birth.getMonth());
    if (visit.getDate() < birth.getDate()) {
        months--;
    }
    return Math.max(0, months);
});

// Real-time Preview State
const previewLoading = ref(false);
const previewOffline = ref(false);
const previewData = ref(null);
let abortController = null;
let debounceTimeout = null;

// Fetch Live Preview from Endpoint with 400ms Debounce and AbortController
const fetchLivePreview = () => {
    if (!form.balita_id || !form.berat_badan_kg || !form.tinggi_badan_cm || !form.tanggal_kunjungan) {
        previewData.value = null;
        previewLoading.value = false;
        return;
    }

    const bb = parseFloat(form.berat_badan_kg);
    const tb = parseFloat(form.tinggi_badan_cm);

    if (isNaN(bb) || bb <= 0 || isNaN(tb) || tb <= 0) {
        previewData.value = null;
        return;
    }

    if (abortController) {
        abortController.abort();
    }
    abortController = new AbortController();

    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(async () => {
        previewLoading.value = true;
        previewOffline.value = false;

        try {
            const response = await fetch(route('pengukuran.preview'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    balita_id: form.balita_id,
                    tanggal_kunjungan: form.tanggal_kunjungan,
                    berat_badan_kg: form.berat_badan_kg,
                    tinggi_badan_cm: form.tinggi_badan_cm,
                    cara_ukur_tinggi: form.cara_ukur_tinggi,
                    lingkar_lengan_atas_cm: form.lingkar_lengan_atas_cm || null,
                    lingkar_kepala_cm: form.lingkar_kepala_cm || null,
                }),
                signal: abortController.signal
            });

            if (!response.ok) {
                throw new Error('Gagal memuat pratinjau');
            }

            const json = await response.json();
            if (json.success) {
                previewData.value = json.data;
            }
        } catch (err) {
            if (err.name !== 'AbortError') {
                previewOffline.value = !navigator.onLine;
            }
        } finally {
            previewLoading.value = false;
        }
    }, 400);
};

// Trigger Preview on Inputs Change
watch(
    [
        () => form.balita_id,
        () => form.tanggal_kunjungan,
        () => form.berat_badan_kg,
        () => form.tinggi_badan_cm,
        () => form.cara_ukur_tinggi,
    ],
    () => {
        fetchLivePreview();
    }
);

// Form Submission
const submit = () => {
    form.post(route('pengukuran.store'), {
        preserveScroll: true,
    });
};

onMounted(() => {
    if (form.balita_id && form.berat_badan_kg && form.tinggi_badan_cm) {
        fetchLivePreview();
    }
});

onUnmounted(() => {
    if (abortController) {
        abortController.abort();
    }
    clearTimeout(debounceTimeout);
});
</script>

<template>
    <AppLayout title="Pencatatan Pengukuran Antropometri">
        <Head title="Ukur Antropometri Balita" />

        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Header Action Title -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('balita.index')"
                        class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-100 transition-colors shadow-2xs"
                    >
                        <ArrowLeft class="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            Pencatatan Antropometri
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 font-medium">
                            KMS Standar WHO Permenkes No. 2 Tahun 2020
                        </p>
                    </div>
                </div>

                <!-- Live Status Indicator -->
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 text-slate-600 text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Kalkulator Aktif
                </div>
            </div>

            <!-- Form Container -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- SECTION 1: Identitas & Waktu Kunjungan -->
                <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-xs space-y-5">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100 text-rose-600 font-bold text-sm">
                        <Baby class="w-4 h-4" />
                        <span>Identitas Balita & Waktu Kunjungan</span>
                    </div>

                    <!-- Balita Selector -->
                    <SearchableSelect
                        v-model="form.balita_id"
                        :items="balitaList"
                        label="Pilih Balita yang Ditimbang *"
                        placeholder="Ketik nama anak atau NIK..."
                        :error="form.errors.balita_id"
                    />

                    <!-- Banner Info Balita Terpilih -->
                    <div
                        v-if="selectedChild"
                        class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs"
                    >
                        <div class="space-y-0.5">
                            <span class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">Data Balita</span>
                            <p class="font-bold text-slate-800 text-sm">
                                {{ selectedChild.nama_anak }} ({{ selectedChild.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }})
                            </p>
                            <p class="text-slate-500">
                                Lahir: {{ selectedChild.tanggal_lahir }} &bull; Berat Lahir: {{ selectedChild.berat_lahir_kg }} kg
                            </p>
                        </div>

                        <div class="bg-white px-3.5 py-2 rounded-xl border border-slate-200 text-right shadow-2xs">
                            <span class="text-slate-400 font-medium text-[10px] block">Usia Saat Kunjungan</span>
                            <span class="text-base font-black text-rose-600">
                                {{ clientComputedAgeMonths !== null ? `${clientComputedAgeMonths} Bulan` : '-' }}
                            </span>
                        </div>
                    </div>

                    <!-- Tanggal Kunjungan -->
                    <div class="space-y-1.5">
                        <label for="tanggal_kunjungan" class="text-sm font-bold text-slate-800 flex items-center justify-between">
                            <span>Tanggal Kunjungan / Pengukuran *</span>
                        </label>
                        <div class="relative">
                            <Calendar class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                            <input
                                id="tanggal_kunjungan"
                                type="date"
                                v-model="form.tanggal_kunjungan"
                                :max="new Date().toISOString().split('T')[0]"
                                class="w-full min-h-[52px] pl-12 pr-4 text-base font-semibold text-slate-900 bg-white border-2 border-slate-200 rounded-2xl focus:border-rose-500 focus:ring-4 focus:ring-rose-100 focus:outline-hidden transition-all shadow-xs"
                            />
                        </div>
                        <p v-if="form.errors.tanggal_kunjungan" class="text-xs font-semibold text-red-600 mt-1">
                            {{ form.errors.tanggal_kunjungan }}
                        </p>
                    </div>
                </div>

                <!-- SECTION 2: Pengukuran Fisik Utama (BB & TB dengan Stepper) -->
                <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-xs space-y-5">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100 text-rose-600 font-bold text-sm">
                        <Scale class="w-4 h-4" />
                        <span>Pengukuran Antropometri Utama</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <!-- Berat Badan -->
                        <BigInput
                            id="berat_badan_kg"
                            v-model="form.berat_badan_kg"
                            label="Berat Badan (BB) *"
                            unit="kg"
                            placeholder="0.00"
                            :step="0.05"
                            :min="0.5"
                            :max="30"
                            hint="Akurasi 2 desimal"
                            :error="form.errors.berat_badan_kg"
                        />

                        <!-- Tinggi / Panjang Badan -->
                        <BigInput
                            id="tinggi_badan_cm"
                            v-model="form.tinggi_badan_cm"
                            label="Tinggi / Panjang Badan (TB/PB) *"
                            unit="cm"
                            placeholder="0.0"
                            :step="0.1"
                            :min="35"
                            :max="130"
                            hint="Akurasi 1 desimal"
                            :error="form.errors.tinggi_badan_cm"
                        />
                    </div>

                    <!-- Posisi Ukur Tinggi (Segmented Control) -->
                    <div class="space-y-2 pt-1">
                        <label class="text-sm font-bold text-slate-800 flex items-center justify-between">
                            <span>Posisi Pengukuran Tinggi Badan *</span>
                            <span class="text-xs font-medium text-slate-500">
                                {{ clientComputedAgeMonths !== null && clientComputedAgeMonths < 24 ? 'Rekomendasi: Telentang (<24 bln)' : 'Rekomendasi: Berdiri (≥24 bln)' }}
                            </span>
                        </label>

                        <div class="grid grid-cols-2 gap-2 p-1.5 bg-slate-100 rounded-2xl border border-slate-200">
                            <button
                                type="button"
                                @click="form.cara_ukur_tinggi = 'telentang'"
                                :class="[
                                    'min-h-[48px] px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all select-none touch-manipulation',
                                    form.cara_ukur_tinggi === 'telentang'
                                        ? 'bg-white text-rose-600 shadow-md ring-1 ring-slate-900/5'
                                        : 'text-slate-600 hover:text-slate-900'
                                ]"
                            >
                                <span class="w-2.5 h-2.5 rounded-full" :class="form.cara_ukur_tinggi === 'telentang' ? 'bg-rose-600' : 'bg-transparent'"></span>
                                Telentang (PB)
                            </button>

                            <button
                                type="button"
                                @click="form.cara_ukur_tinggi = 'berdiri'"
                                :class="[
                                    'min-h-[48px] px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all select-none touch-manipulation',
                                    form.cara_ukur_tinggi === 'berdiri'
                                        ? 'bg-white text-rose-600 shadow-md ring-1 ring-slate-900/5'
                                        : 'text-slate-600 hover:text-slate-900'
                                ]"
                            >
                                <span class="w-2.5 h-2.5 rounded-full" :class="form.cara_ukur_tinggi === 'berdiri' ? 'bg-rose-600' : 'bg-transparent'"></span>
                                Berdiri (TB)
                            </button>
                        </div>
                        <p v-if="form.errors.cara_ukur_tinggi" class="text-xs font-semibold text-red-600 mt-1">
                            {{ form.errors.cara_ukur_tinggi }}
                        </p>
                    </div>

                    <!-- Lingkar Lengan & Lingkar Kepala Tambahan -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2">
                        <BigInput
                            id="lingkar_lengan_atas_cm"
                            v-model="form.lingkar_lengan_atas_cm"
                            label="Lingkar Lengan Atas (LiLA)"
                            unit="cm"
                            placeholder="0.0"
                            :step="0.1"
                            :min="5"
                            :max="30"
                            hint="Opsional (Skrining KEK)"
                            :error="form.errors.lingkar_lengan_atas_cm"
                        />

                        <BigInput
                            id="lingkar_kepala_cm"
                            v-model="form.lingkar_kepala_cm"
                            label="Lingkar Kepala (LK)"
                            unit="cm"
                            placeholder="0.0"
                            :step="0.1"
                            :min="25"
                            :max="65"
                            hint="Opsional"
                            :error="form.errors.lingkar_kepala_cm"
                        />
                    </div>
                </div>

                <!-- SECTION 3: HASIL KALKULASI & ZONA MERAH LIVE PREVIEW -->
                <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2 text-rose-600 font-bold text-sm">
                            <Sparkles class="w-4 h-4" />
                            <span>Hasil Analisis & Status Gizi Klinis</span>
                        </div>

                        <div v-if="previewLoading" class="flex items-center gap-1.5 text-xs text-rose-600 font-semibold">
                            <Loader2 class="w-4 h-4 animate-spin" />
                            <span>Menghitung...</span>
                        </div>
                    </div>

                    <!-- Offline State Alert -->
                    <div v-if="previewOffline" class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-center gap-3">
                        <WifiOff class="w-5 h-5 text-amber-600 shrink-0" />
                        <div>Koneksi lambat/terputus. Nilai Z-score akan dikalkulasi otomatis saat data disimpan ke server.</div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="!previewData && !previewLoading" class="py-8 text-center text-slate-400 text-sm space-y-1">
                        <Scale class="w-8 h-8 mx-auto text-slate-300 stroke-[1.5]" />
                        <p class="font-medium text-slate-500">Masukkan Berat dan Tinggi Badan</p>
                        <p class="text-xs text-slate-400">Hasil Z-Score dan status gizi akan muncul secara otomatis.</p>
                    </div>

                    <!-- Calculated Results Panel -->
                    <div v-if="previewData" class="space-y-4">
                        <!-- Red Zone Alert Panel -->
                        <div
                            v-if="previewData.is_zona_merah"
                            class="p-4 sm:p-5 rounded-2xl bg-red-500 text-white shadow-lg shadow-red-500/20 animate-in fade-in zoom-in-95 duration-200"
                        >
                            <div class="flex items-start gap-3.5">
                                <ShieldAlert class="w-7 h-7 text-white shrink-0 mt-0.5" />
                                <div class="space-y-1">
                                    <h4 class="text-base font-black tracking-tight leading-tight uppercase">
                                        Peringatan: Masuk Zona Merah!
                                    </h4>
                                    <p class="text-xs sm:text-sm text-red-100 font-medium leading-relaxed">
                                        Balita terindikasi mengalami gangguan pertumbuhan kritis. Lakukan rujukan ke Bidan Desa atau Puskesmas serta jadwalkan konseling gizi segera.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Data Plausibility Outlier Banner -->
                        <AlertBanner
                            v-if="previewData.flag_data_tidak_wajar"
                            type="warning"
                            title="Peringatan Outlier Biologis (Data Tidak Wajar)"
                            :messages="previewData.pesan_peringatan"
                        />

                        <!-- Grid Cards: 3 Indikator WHO + Kenaikan BB KMS -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <!-- BB/U -->
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                                    Berat Badan / Usia (BB/U)
                                </span>
                                <div class="flex items-baseline justify-between">
                                    <span class="text-2xl font-black text-slate-900">
                                        {{ previewData.zscore_bbu !== null ? `${previewData.zscore_bbu} SD` : '-' }}
                                    </span>
                                </div>
                                <div>
                                    <StatusBadge :status="previewData.status_gizi" type="gizi" />
                                </div>
                            </div>

                            <!-- TB/U (Stunting) -->
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                                    Tinggi Badan / Usia (TB/U)
                                </span>
                                <div class="flex items-baseline justify-between">
                                    <span class="text-2xl font-black text-slate-900">
                                        {{ previewData.zscore_tbu !== null ? `${previewData.zscore_tbu} SD` : '-' }}
                                    </span>
                                </div>
                                <div>
                                    <StatusBadge :status="previewData.status_stunting" type="stunting" />
                                </div>
                            </div>

                            <!-- BB/TB -->
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                                    BB / TB (Gizi Akut)
                                </span>
                                <div class="flex items-baseline justify-between">
                                    <span class="text-2xl font-black text-slate-900">
                                        {{ previewData.zscore_bbtb !== null ? `${previewData.zscore_bbtb} SD` : '-' }}
                                    </span>
                                </div>
                                <div>
                                    <StatusBadge :status="previewData.status_bbtb" type="bbtb" />
                                </div>
                            </div>
                        </div>

                        <!-- Kenaikan BB KMS Badge Status -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-700">Kenaikan Berat Badan KMS:</span>
                            <StatusBadge :status="previewData.kenaikan_bb" type="kenaikan" />
                        </div>
                    </div>
                </div>

                <!-- SECTION 4: Intervensi Tambahan & Catatan Kader -->
                <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-xs space-y-5">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100 text-rose-600 font-bold text-sm">
                        <Pill class="w-4 h-4" />
                        <span>Program Tambahan & Catatan</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <ToggleCheckbox
                            id="vit_a_diberikan"
                            v-model="form.vit_a_diberikan"
                            label="Vitamin A"
                            description="Kapsul Biru (6-11 bln) / Merah (12-59 bln)"
                        />

                        <ToggleCheckbox
                            id="obat_cacing_diberikan"
                            v-model="form.obat_cacing_diberikan"
                            label="Obat Cacing"
                            description="Pemberian rutin usia ≥ 12 bulan"
                        />
                    </div>

                    <!-- Catatan Kader -->
                    <div class="space-y-1.5 pt-2">
                        <label for="catatan_kader" class="text-sm font-bold text-slate-800">
                            Catatan Tambahan Kader
                        </label>
                        <textarea
                            id="catatan_kader"
                            v-model="form.catatan_kader"
                            rows="3"
                            placeholder="Tuliskan catatan konseling, nafsu makan balita, atau keluhan orang tua..."
                            class="w-full p-3.5 text-sm font-medium text-slate-900 bg-white border-2 border-slate-200 rounded-2xl focus:border-rose-500 focus:ring-4 focus:ring-rose-100 focus:outline-hidden transition-all shadow-xs"
                        ></textarea>
                        <p v-if="form.errors.catatan_kader" class="text-xs font-semibold text-red-600 mt-1">
                            {{ form.errors.catatan_kader }}
                        </p>
                    </div>
                </div>

                <!-- Submit Button Bar -->
                <div class="sticky bottom-20 md:bottom-6 z-30 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing || !form.balita_id"
                        class="w-full min-h-[56px] px-6 rounded-2xl bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-700 hover:to-rose-600 active:scale-[0.99] text-white text-base font-bold shadow-xl shadow-rose-500/25 flex items-center justify-center gap-2.5 transition-all disabled:opacity-60 disabled:cursor-not-allowed select-none touch-manipulation"
                    >
                        <Loader2 v-if="form.processing" class="w-5 h-5 animate-spin" />
                        <CheckCircle2 v-else class="w-5 h-5" />
                        <span>{{ form.processing ? 'Menyimpan Pengukuran...' : 'Simpan Pengukuran Balita' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
