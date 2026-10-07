<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatTanggal } from '@/utils';
import {
    Activity,
    AlertCircle,
    Baby,
    BookOpen,
    Calendar,
    ChevronRight,
    HeartHandshake,
    HeartPulse,
    HelpCircle,
    Info,
    MapPin,
    ShieldCheck,
    Sparkles,
    Syringe,
    Utensils
} from 'lucide-vue-next';

const props = defineProps({
    profilOrangTua: {
        type: Object,
        default: null
    },
    daftarAnak: {
        type: Array,
        default: () => []
    }
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
        return remMonths > 0 ? `${years} tahun ${remMonths} bulan` : `${years} tahun`;
    }
    return `${months} bulan`;
};
</script>

<template>
    <AppLayout title="Buku KMS Digital Orang Tua">
        <Head title="Buku KMS Digital Anak Saya" />

        <div class="space-y-6">
            <!-- Header Sambutan Orang Tua -->
            <div class="bg-gradient-to-br from-rose-500 to-rose-600 rounded-3xl p-6 sm:p-8 text-white shadow-lg shadow-rose-500/20 relative overflow-hidden">
                <div class="relative z-10 space-y-2">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur text-white text-xs font-bold">
                        <BookOpen class="w-3.5 h-3.5" />
                        <span>Buku KIA / KMS Digital</span>
                    </div>

                    <h1 class="text-xl sm:text-2xl font-black tracking-tight leading-tight">
                        Selamat Datang, {{ profilOrangTua?.nama_ibu ? `Ibu ${profilOrangTua.nama_ibu}` : 'Bunda' }}!
                    </h1>

                    <p class="text-xs sm:text-sm text-rose-100 max-w-xl font-medium leading-relaxed">
                        Pantau grafik pertumbuhan berat dan tinggi badan serta jadwal imunisasi buah hati secara mandiri langsung dari ponsel Anda.
                    </p>

                    <div v-if="profilOrangTua" class="pt-2 flex items-center gap-3 text-xs text-rose-100 flex-wrap">
                        <span v-if="profilOrangTua.no_kk" class="font-mono bg-white/15 px-2.5 py-0.5 rounded-md">
                            No KK: {{ profilOrangTua.no_kk }}
                        </span>
                        <span v-if="profilOrangTua.alamat" class="flex items-center gap-1">
                            <MapPin class="w-3.5 h-3.5" />
                            {{ profilOrangTua.alamat }}
                        </span>
                    </div>
                </div>

                <!-- Background Subtle Graphic -->
                <div class="absolute -right-10 -bottom-10 opacity-10 pointer-events-none">
                    <Baby class="w-64 h-64" />
                </div>
            </div>

            <!-- Daftar Anak (Read-Only Child View Cards) -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Buah Hati Anda</h2>
                        <p class="text-xs text-slate-500">Rekam medis dan grafik pertumbuhan balita terdaftar</p>
                    </div>
                    <span class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-1 rounded-full">
                        {{ daftarAnak.length }} Anak Terdaftar
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="anak in daftarAnak"
                        :key="anak.id"
                        class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-xs hover:border-rose-200 transition-all space-y-4 flex flex-col justify-between"
                    >
                        <div class="space-y-4">
                            <!-- Identitas Anak -->
                            <div class="flex items-start gap-3.5">
                                <div
                                    :class="[
                                        'w-14 h-14 rounded-2xl flex items-center justify-center font-black text-lg shrink-0 shadow-2xs',
                                        anak.jenis_kelamin === 'L' ? 'bg-sky-100 text-sky-700' : 'bg-rose-100 text-rose-700'
                                    ]"
                                >
                                    {{ anak.jenis_kelamin === 'L' ? 'L' : 'P' }}
                                </div>

                                <div class="space-y-0.5">
                                    <h3 class="text-base font-extrabold text-slate-900 tracking-tight leading-snug">
                                        {{ anak.nama_anak }}
                                    </h3>
                                    <p class="text-xs text-slate-600 font-medium">
                                        Usia: <strong>{{ hitungUsiaDeskripsi(anak.tanggal_lahir) }}</strong>
                                        <span class="text-slate-400">&bull;</span>
                                        Lahir: <strong class="text-slate-800">{{ formatTanggal(anak.tanggal_lahir) }}</strong>
                                    </p>
                                    <p class="text-[11px] text-slate-400">
                                        Posyandu: {{ anak.posyandu || '-' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Status Pertumbuhan Terkini -->
                            <div class="space-y-1.5 pt-1 border-t border-slate-100">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Status Gizi & Tumbuh Kembang</span>
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
                            </div>

                            <!-- Peringatan Ramah jika Memerlukan Perhatian Posyandu -->
                            <div v-if="anak.is_zona_merah" class="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-900 space-y-1">
                                <div class="flex items-center gap-1.5 font-bold text-rose-700">
                                    <AlertCircle class="w-4 h-4 shrink-0" />
                                    <span>Perlu Perhatian Tumbuh Kembang</span>
                                </div>
                                <p class="text-[11px] text-rose-800 leading-relaxed">
                                    Hasil penimbangan menunjukkan perlunya konsultasi asupan nutrisi tambahan. Silakan berkonsultasi dengan Bidan atau Kader di Posyandu terdekat.
                                </p>
                            </div>

                            <!-- Hasil Penimbangan Terakhir -->
                            <div v-if="anak.pengukuran_terakhir" class="p-3 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                                <div class="flex items-center justify-between text-slate-500 text-[11px]">
                                    <span>Kunjungan Terakhir: <strong class="text-slate-700">{{ formatTanggal(anak.pengukuran_terakhir.tanggal_kunjungan) }}</strong></span>
                                    <StatusBadge :status="anak.pengukuran_terakhir.kenaikan_bb" type="kenaikan" size="sm" />
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-center">
                                    <div class="bg-white p-2 rounded-xl border border-slate-200">
                                        <span class="text-[10px] text-slate-400 block font-semibold">Berat Badan</span>
                                        <span class="font-black text-slate-900 text-sm">{{ anak.pengukuran_terakhir.berat_badan_kg }} kg</span>
                                    </div>
                                    <div class="bg-white p-2 rounded-xl border border-slate-200">
                                        <span class="text-[10px] text-slate-400 block font-semibold">Tinggi Badan</span>
                                        <span class="font-black text-slate-900 text-sm">{{ anak.pengukuran_terakhir.tinggi_badan_cm }} cm</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Jadwal Vaksin Berikutnya -->
                            <div v-if="anak.vaksin_berikutnya" class="p-3 rounded-2xl bg-emerald-50/70 border border-emerald-200 text-xs space-y-1">
                                <div class="flex items-center justify-between text-emerald-900 font-bold">
                                    <span class="flex items-center gap-1.5">
                                        <Syringe class="w-3.5 h-3.5 text-emerald-600" />
                                        <span>Jadwal Vaksin Berikutnya</span>
                                    </span>
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-md text-[10px] font-black',
                                            anak.vaksin_berikutnya.status === 'Perlu Segera Diberikan'
                                                ? 'bg-rose-100 text-rose-700'
                                                : 'bg-emerald-100 text-emerald-700'
                                        ]"
                                    >
                                        {{ anak.vaksin_berikutnya.status }}
                                    </span>
                                </div>
                                <p class="text-xs font-extrabold text-slate-800">
                                    {{ anak.vaksin_berikutnya.nama }}
                                </p>
                                <p class="text-[11px] text-slate-500">
                                    Target usia: {{ anak.vaksin_berikutnya.usia_target }} Bulan
                                </p>
                            </div>
                        </div>

                        <!-- Tombol Buka KMS Lengkap -->
                        <div class="pt-3 border-t border-slate-100">
                            <Link
                                :href="route('balita.show', anak.id)"
                                class="w-full min-h-[44px] rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-sm transition-all active:scale-95"
                            >
                                <Activity class="w-4 h-4" />
                                <span>Buka Grafik KMS & Riwayat Lengkap</span>
                                <ChevronRight class="w-4 h-4 opacity-70" />
                            </Link>
                        </div>
                    </div>

                    <!-- Kosong: Jika Belum Terdata -->
                    <div v-if="daftarAnak.length === 0" class="col-span-full bg-white rounded-3xl p-8 border border-slate-200 text-center space-y-3">
                        <Baby class="w-12 h-12 text-slate-300 mx-auto" />
                        <h3 class="font-extrabold text-slate-800 text-base">Belum Ada Balita Terhubung</h3>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">
                            Data anak Anda belum tercatat di sistem atau belum ditautkan dengan akun ini. Silakan sampaikan NIK Ibu / Balita kepada Kader Posyandu setempat saat hari penimbangan.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Kartu Edukasi & Tips Kesehatan Balita -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex items-center gap-2 text-slate-900 font-extrabold text-base">
                    <Sparkles class="w-5 h-5 text-rose-500" />
                    <h2>Panduan Tumbuh Kembang Buah Hati (Kemenkes RI)</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <!-- Tips 1: Protein Hewani -->
                    <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200 space-y-2">
                        <div class="flex items-center gap-2 text-amber-900 font-black text-sm">
                            <Utensils class="w-4 h-4 text-amber-600" />
                            <span>Protein Hewani Setiap Hari</span>
                        </div>
                        <p class="text-amber-900/90 leading-relaxed text-[11px]">
                            Cegah stunting dengan rutin memberikan sumber protein hewani pada setiap menu MPASI: telur (1 butir/hari), ikan kembung, ayam, atau hati ayam.
                        </p>
                    </div>

                    <!-- Tips 2: Timbang Rutin -->
                    <div class="p-4 rounded-2xl bg-sky-50/60 border border-sky-200 space-y-2">
                        <div class="flex items-center gap-2 text-sky-900 font-black text-sm">
                            <Activity class="w-4 h-4 text-sky-600" />
                            <span>Timbang Setiap Bulan</span>
                        </div>
                        <p class="text-sky-900/90 leading-relaxed text-[11px]">
                            Bawa anak ke Posyandu setiap bulan. Deteksi dini penurunan berat badan (T) mencegah terjadinya gizi kurang dan gagal tumbuh sebelum terlambat.
                        </p>
                    </div>

                    <!-- Tips 3: Imunisasi Lengkap -->
                    <div class="p-4 rounded-2xl bg-emerald-50/60 border border-emerald-200 space-y-2">
                        <div class="flex items-center gap-2 text-emerald-900 font-black text-sm">
                            <ShieldCheck class="w-4 h-4 text-emerald-600" />
                            <span>Imunisasi Tepat Waktu</span>
                        </div>
                        <p class="text-emerald-900/90 leading-relaxed text-[11px]">
                            Imunisasi dasar lengkap dan imunisasi lanjutan melindungi buah hati dari penyakit berbahaya seperti polio, campak, difteri, dan pneumonia.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
