<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import KmsChart from '@/Components/KmsChart.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatTanggal, getSeninMingguKedua } from '@/utils';
import {
    Activity,
    AlertCircle,
    AlertTriangle,
    ArrowLeft,
    Baby,
    Calendar,
    ChevronDown,
    ChevronUp,
    Clock,
    FileText,
    HeartPulse,
    MapPin,
    MessageCircle,
    Phone,
    Plus,
    Scale,
    ShieldAlert,
    Sparkles,
    Syringe,
    Trash2,
    User,
    Users,
    X,
    ArrowRightLeft,
    Building2,
    BookOpen,
    CheckCircle2,
    RotateCcw,
    Info
} from 'lucide-vue-next';

const props = defineProps({
    balita: {
        type: Object,
        required: true
    },
    riwayatPengukuran: {
        type: Array,
        default: () => []
    },
    riwayatImunisasi: {
        type: Array,
        default: () => []
    },
    rekomendasiVaksin: {
        type: Array,
        default: () => []
    },
    kurvaKms: {
        type: Object,
        required: true
    },
    posyanduList: {
        type: Array,
        default: () => []
    }
});

const page = usePage();
const permissions = computed(() => page.props.auth?.permissions || {});

// Active Tab: 'pertumbuhan' | 'imunisasi' | 'riwayat'
const activeTab = ref('pertumbuhan');

// Riwayat Pengukuran Sort State
const sortOrder = ref('desc'); // 'desc' | 'asc'

const toggleSort = () => {
    sortOrder.value = sortOrder.value === 'desc' ? 'asc' : 'desc';
};

// Hitung Jadwal Posyandu Rutin (Senin Minggu ke-2 Setiap Bulan) & Deteksi Ketidakhadiran
const namaBulanLengkap = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const daftarKehadiranPosyandu = computed(() => {
    if (!props.balita.tanggal_lahir) {
        return { missed: [], totalJadwal: 0, totalHadir: 0, persen: 100 };
    }

    const birthParts = String(props.balita.tanggal_lahir).split('T')[0].split('-');
    const birthYear = parseInt(birthParts[0], 10);
    const birthMonth = parseInt(birthParts[1], 10) - 1;

    const now = new Date();
    const currentYear = now.getFullYear();
    const currentMonth = now.getMonth();

    const missedList = [];
    let totalJadwalHitung = 0;
    let totalHadirHitung = 0;

    let y = birthYear;
    let m = birthMonth;

    const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate());

    while (y < currentYear || (y === currentYear && m <= currentMonth)) {
        const jadwalSenin = getSeninMingguKedua(y, m);
        const jadwalStr = `${y}-${String(m + 1).padStart(2, '0')}-${String(jadwalSenin.getDate()).padStart(2, '0')}`;

        // Jadwal Posyandu adalah Senin minggu ke-2
        // Hari Selasa setelah Senin minggu ke-2: sesi Posyandu dianggap sudah selesai dilaksanakan
        const selasaSetelahSenin = new Date(jadwalSenin);
        selasaSetelahSenin.setDate(jadwalSenin.getDate() + 1);
        selasaSetelahSenin.setHours(0, 0, 0, 0);

        const isPastMonth = (y < currentYear) || (y === currentYear && m < currentMonth);
        const isCurrentMonthDone = (y === currentYear && m === currentMonth && todayStart >= selasaSetelahSenin);

        // Hanya hitung jadwal yang sudah lewat (bulan lalu atau bulan ini jika sudah lewat Senin ke-2)
        if (isPastMonth || isCurrentMonthDone) {
            totalJadwalHitung++;

            // Cari apakah balita punya pengukuran di bulan tersebut
            const pengukuranDiBulanIni = props.riwayatPengukuran.find(p => {
                if (!p.tanggal_kunjungan) return false;
                const pParts = String(p.tanggal_kunjungan).split('T')[0].split('-');
                return parseInt(pParts[0], 10) === y && (parseInt(pParts[1], 10) - 1) === m;
            });

            if (pengukuranDiBulanIni) {
                totalHadirHitung++;
            } else {
                let usiaPadaJadwal = (y - birthYear) * 12 + (m - birthMonth);
                if (usiaPadaJadwal < 0) usiaPadaJadwal = 0;

                missedList.push({
                    id: `missed-${y}-${m}`,
                    is_missed: true,
                    tanggal_kunjungan: jadwalStr,
                    tanggal_formatted: formatTanggal(jadwalStr),
                    bulan_nama: namaBulanLengkap[m],
                    tahun: y,
                    usia_bulan: usiaPadaJadwal,
                    catatan: 'Tidak hadir pada jadwal rutin Posyandu (Senin Minggu ke-2)'
                });
            }
        }

        m++;
        if (m > 11) {
            m = 0;
            y++;
        }
    }

    const persen = totalJadwalHitung > 0 ? Math.round((totalHadirHitung / totalJadwalHitung) * 100) : 100;

    return {
        missed: missedList,
        totalJadwal: totalJadwalHitung,
        totalHadir: totalHadirHitung,
        persen
    };
});

// Banner Pengingat: HANYA muncul jika balita tidak hadir di periode BULAN BERJALAN INI
// Semisal di bulan ini pada Senin minggu ke-2 tidak hadir, maka di hari Selasa setelahnya akan muncul banner ini
const missedBulanIni = computed(() => {
    const now = new Date();
    const currentYear = now.getFullYear();
    const currentMonth = now.getMonth();

    if (!props.balita || !props.balita.tanggal_lahir) return null;
    const birthParts = String(props.balita.tanggal_lahir).split('T')[0].split('-');
    const birthYear = parseInt(birthParts[0], 10);
    const birthMonth = parseInt(birthParts[1], 10) - 1;
    if (currentYear < birthYear || (currentYear === birthYear && currentMonth < birthMonth)) {
        return null;
    }

    const seninKeduaBulanIni = getSeninMingguKedua(currentYear, currentMonth);

    // Hari Selasa setelah Senin minggu ke-2
    const selasaSetelahSeninKedua = new Date(seninKeduaBulanIni);
    selasaSetelahSeninKedua.setDate(seninKeduaBulanIni.getDate() + 1);
    selasaSetelahSeninKedua.setHours(0, 0, 0, 0);

    const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const sudahLewatSeninKedua = todayStart >= selasaSetelahSeninKedua;

    // Jika belum melewati hari Senin minggu ke-2 (belum masuk hari Selasa), jangan munculkan alert
    if (!sudahLewatSeninKedua) {
        return null;
    }

    // Cek apakah balita sudah ditimbang di bulan berjalan ini
    const sudahDitimbangBulanIni = props.riwayatPengukuran.some(p => {
        if (!p.tanggal_kunjungan) return false;
        const pParts = String(p.tanggal_kunjungan).split('T')[0].split('-');
        return parseInt(pParts[0], 10) === currentYear && (parseInt(pParts[1], 10) - 1) === currentMonth;
    });

    if (sudahDitimbangBulanIni) {
        return null; // Sudah hadir / ditimbang di bulan ini
    }

    const jadwalStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(seninKeduaBulanIni.getDate()).padStart(2, '0')}`;
    return {
        tanggal_kunjungan: jadwalStr,
        tanggal_formatted: formatTanggal(jadwalStr),
        bulan_nama: namaBulanLengkap[currentMonth],
        tahun: currentYear,
    };
});

// Nomor Telepon Ibu
const ibuPhone = computed(() => {
    return props.balita.orangTua?.user?.nomor_telepon || props.balita.orangTua?.nomor_telepon || '';
});

// URL WhatsApp Resmi untuk Menghubungi Ibu Balita
const waUrl = computed(() => {
    if (!ibuPhone.value) return '#';
    let cleanPhone = ibuPhone.value.replace(/[^0-9]/g, '');
    if (cleanPhone.startsWith('0')) {
        cleanPhone = '62' + cleanPhone.slice(1);
    }
    const namaIbu = props.balita.orangTua?.nama_ibu || 'Ibu';
    const namaAnak = props.balita.nama_anak;
    const posyanduNama = props.balita.posyandu?.nama_posyandu || 'Posyandu';
    const tglJadwal = missedBulanIni.value ? missedBulanIni.value.tanggal_formatted : 'Senin minggu ke-2';
    const pesan = encodeURIComponent(
        `Halo Ibu ${namaIbu}, salam sehat dari Kader ${posyanduNama}. Kami menginformasikan bahwa ananda ${namaAnak} belum hadir pada jadwal rutin Posyandu (${tglJadwal}). Mohon konfirmasi kondisi ananda, atau apakah berkenan kami jadwalkan kunjungan rumah (sweeping) untuk pengukuran antropometri berkala? Terima kasih banyak Bu.`
    );
    return `https://wa.me/${cleanPhone}?text=${pesan}`;
});

// Gabungan Riwayat Pengukuran dan Sesi Tidak Hadir
const combinedPengukuran = computed(() => {
    const list = [];

    // Pengukuran fisik riil
    props.riwayatPengukuran.forEach(p => {
        list.push({
            ...p,
            is_missed: false,
            tanggal_formatted: formatTanggal(p.tanggal_kunjungan),
            timestamp: new Date(p.tanggal_kunjungan).getTime()
        });
    });

    // Sesi tidak hadir (jadwal terlewat)
    daftarKehadiranPosyandu.value.missed.forEach(m => {
        list.push({
            ...m,
            timestamp: new Date(m.tanggal_kunjungan).getTime()
        });
    });

    return list.sort((a, b) => {
        return sortOrder.value === 'desc' ? b.timestamp - a.timestamp : a.timestamp - b.timestamp;
    });
});

// Modal Tambah Imunisasi (Bidan / Admin)
const isImunisasiModalOpen = ref(false);
const imunisasiForm = useForm({
    balita_id: props.balita.id,
    jenis_vaksin: '',
    tanggal_imunisasi: new Date().toISOString().split('T')[0],
    petugas_kesehatan: page.props.auth?.user?.nama_lengkap || '',
    batch_vaksin: '',
});

const submitImunisasi = () => {
    imunisasiForm.post(route('imunisasi.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isImunisasiModalOpen.value = false;
            imunisasiForm.reset('jenis_vaksin', 'batch_vaksin');
        }
    });
};

const deleteImunisasi = (id) => {
    if (confirm('Yakin ingin menghapus catatan imunisasi ini?')) {
        useForm({}).delete(route('imunisasi.destroy', id), {
            preserveScroll: true,
        });
    }
};

// Modal Mutasi / Pindah Posyandu
const isPindahPosyanduModalOpen = ref(false);
const pindahForm = useForm({
    tipe_pindah: 'luar_wilayah', // 'luar_wilayah' | 'posyandu_digital' | 'aktifkan_kembali'
    posyandu_id: props.balita.posyandu_id || '',
    alamat_baru: props.balita.orangTua?.alamat || '',
    catatan_pindah: '',
    update_keluarga: true,
});

const openPindahModal = (mode = null) => {
    if (mode) {
        pindahForm.tipe_pindah = mode;
    } else if (props.balita.status_domisili === 'pindah') {
        pindahForm.tipe_pindah = 'aktifkan_kembali';
    } else {
        pindahForm.tipe_pindah = 'luar_wilayah';
    }
    isPindahPosyanduModalOpen.value = true;
};

const submitPindahPosyandu = () => {
    pindahForm.put(route('balita.pindah-posyandu', props.balita.id), {
        preserveScroll: true,
        onSuccess: () => {
            isPindahPosyanduModalOpen.value = false;
        }
    });
};

// Modal Sinkronisasi Riwayat KMS Fisik (Bulan Sebelumnya)
const isSinkronisasiKmsModalOpen = ref(false);
const syncRows = ref([]);
const isSubmittingSync = ref(false);

const siapkanDaftarBulanSinkronisasi = (targetUsiaBulan = null) => {
    if (!props.balita.tanggal_lahir) return;

    const birthParts = String(props.balita.tanggal_lahir).split('T')[0].split('-');
    const birthYear = parseInt(birthParts[0], 10);
    const birthMonth = parseInt(birthParts[1], 10) - 1;
    const birthDay = parseInt(birthParts[2], 10);

    const now = new Date();
    const currentYear = now.getFullYear();
    const currentMonth = now.getMonth();

    const rows = [];
    let y = birthYear;
    let m = birthMonth;

    while (y < currentYear || (y === currentYear && m <= currentMonth)) {
        const usiaBulan = (y - birthYear) * 12 + (m - birthMonth);

        // Cari pengukuran yang sudah ada di bulan ini
        const existing = props.riwayatPengukuran.find(p => {
            if (!p.tanggal_kunjungan) return false;
            const pParts = String(p.tanggal_kunjungan).split('T')[0].split('-');
            return parseInt(pParts[0], 10) === y && (parseInt(pParts[1], 10) - 1) === m;
        });

        let tanggalDefault;
        if (usiaBulan === 0) {
            tanggalDefault = `${y}-${String(m + 1).padStart(2, '0')}-${String(birthDay).padStart(2, '0')}`;
        } else {
            const senin2 = getSeninMingguKedua(y, m);
            tanggalDefault = `${y}-${String(m + 1).padStart(2, '0')}-${String(senin2.getDate()).padStart(2, '0')}`;
        }

        const isTarget = targetUsiaBulan !== null && usiaBulan === targetUsiaBulan;

        rows.push({
            usia_bulan: usiaBulan,
            label_bulan: `${namaBulanLengkap[m]} ${y}`,
            tahun: y,
            bulan_index: m,
            sudah_ada: Boolean(existing),
            existing_data: existing || null,
            // Jika belum ada data dan (targetUsia cocok atau targetUsia null), otomatis aktifkan checkbox
            pilih: !existing && (targetUsiaBulan === null || isTarget),
            tanggal_kunjungan: existing ? String(existing.tanggal_kunjungan).split('T')[0] : tanggalDefault,
            berat_badan_kg: existing ? existing.berat_badan_kg : (usiaBulan === 0 ? props.balita.berat_lahir_kg : ''),
            tinggi_badan_cm: existing ? existing.tinggi_badan_cm : (usiaBulan === 0 ? props.balita.panjang_lahir_cm : ''),
            cara_ukur_tinggi: existing ? (existing.cara_ukur_tinggi || 'telentang') : (usiaBulan < 24 ? 'telentang' : 'berdiri'),
            lingkar_kepala_cm: existing ? (existing.lingkar_kepala_cm || '') : (usiaBulan === 0 ? props.balita.lingkar_kepala_lahir_cm : ''),
            lingkar_lengan_atas_cm: existing ? (existing.lingkar_lengan_atas_cm || '') : '',
            catatan_kader: existing ? (existing.catatan_kader || '') : 'Sinkronisasi Buku KMS Fisik (Posyandu Asal)',
        });

        m++;
        if (m > 11) {
            m = 0;
            y++;
        }
    }

    syncRows.value = rows;
    isSinkronisasiKmsModalOpen.value = true;
};

const submitSinkronisasiKms = () => {
    const rowsToSubmit = syncRows.value.filter(r => r.pilih && r.berat_badan_kg && r.tinggi_badan_cm);

    if (rowsToSubmit.length === 0) {
        alert('Silakan pilih dan isi minimal satu bulan data penimbangan yang ingin disinkronkan.');
        return;
    }

    const payload = {
        riwayat: rowsToSubmit.map(r => ({
            tanggal_kunjungan: r.tanggal_kunjungan,
            berat_badan_kg: parseFloat(r.berat_badan_kg),
            tinggi_badan_cm: parseFloat(r.tinggi_badan_cm),
            cara_ukur_tinggi: r.cara_ukur_tinggi,
            lingkar_kepala_cm: r.lingkar_kepala_cm ? parseFloat(r.lingkar_kepala_cm) : null,
            lingkar_lengan_atas_cm: r.lingkar_lengan_atas_cm ? parseFloat(r.lingkar_lengan_atas_cm) : null,
            catatan_kader: r.catatan_kader || 'Sinkronisasi riwayat buku KMS fisik (Posyandu asal)',
        }))
    };

    isSubmittingSync.value = true;
    router.post(route('balita.sinkronisasi-kms', props.balita.id), payload, {
        preserveScroll: true,
        onSuccess: () => {
            isSinkronisasiKmsModalOpen.value = false;
        },
        onFinish: () => {
            isSubmittingSync.value = false;
        }
    });
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
        return remMonths > 0 ? `${years} tahun ${remMonths} bulan` : `${years} tahun`;
    }
    return `${months} bulan`;
};
</script>

<template>
    <AppLayout :title="`Rekam Medis - ${balita.nama_anak}`">
        <Head :title="`Rekam Medis: ${balita.nama_anak}`" />

        <div class="space-y-6">
            <!-- Back & Top Nav -->
            <div class="flex items-center justify-between">
                <Link
                    :href="route('balita.index')"
                    class="inline-flex items-center gap-2 text-xs sm:text-sm font-bold text-slate-600 hover:text-rose-600 transition-colors"
                >
                    <ArrowLeft class="w-4 h-4" />
                    <span>Kembali ke Daftar Balita</span>
                </Link>

                <div class="flex items-center gap-2.5 flex-wrap">
                    <button
                        v-if="permissions.is_admin_desa || permissions.is_bidan || permissions.is_kader || permissions.is_super_admin"
                        type="button"
                        @click="siapkanDaftarBulanSinkronisasi()"
                        class="min-h-[44px] px-4 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-bold text-xs sm:text-sm shadow-2xs flex items-center gap-2 transition-all cursor-pointer"
                        title="Cocokkan & salin data penimbangan bulan-bulan sebelumnya dari Buku KMS Fisik / Buku KIA"
                    >
                        <BookOpen class="w-4 h-4 text-rose-600" />
                        <span>Sinkronisasi Buku KMS</span>
                    </button>

                    <Link
                        v-if="permissions.can_create_pengukuran && balita.status_domisili !== 'pindah'"
                        :href="route('pengukuran.create', { balita_id: balita.id })"
                        class="min-h-[44px] px-4 rounded-xl bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-700 hover:to-rose-600 text-white font-bold text-xs sm:text-sm shadow-md shadow-rose-500/20 flex items-center gap-2 transition-all active:scale-95 cursor-pointer"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Catat Pengukuran Baru</span>
                    </Link>
                </div>
            </div>

            <!-- Banner Balita Berstatus PINDAH (Keluar Wilayah) -->
            <div
                v-if="balita.status_domisili === 'pindah'"
                class="bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-300 rounded-3xl p-5 sm:p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
                <div class="flex items-start gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-amber-500/20">
                        <MapPin class="w-6 h-6" />
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2.5 py-0.5 rounded-full bg-amber-200 text-amber-900 text-[11px] font-black uppercase tracking-wider">
                                Status: PINDAH (Keluar Wilayah)
                            </span>
                            <span class="text-xs text-amber-800 font-medium">
                                Posyandu Asal: {{ balita.posyandu?.nama_posyandu }}
                            </span>
                        </div>
                        <h3 class="text-base sm:text-lg font-black text-slate-900 tracking-tight">
                            Balita Ini Berstatus Pindah ke Wilayah Luar (Non-Aplikasi)
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-medium">
                            Keluarga telah pindah domisili ke wilayah lain yang tidak menggunakan aplikasi Posyandu Digital ini. Balita tidak lagi dihitung dalam daftar sweeping posyandu, namun seluruh data rekam medis dan kurva pertumbuhan KMS tetap tersimpan aman sebagai arsip.
                            <span v-if="balita.keterangan_pindah" class="block text-slate-800 font-semibold pt-1">
                                Keterangan: "{{ balita.keterangan_pindah }}"
                            </span>
                        </p>
                    </div>
                </div>

                <div v-if="permissions.is_admin_desa || permissions.is_bidan || permissions.is_kader || permissions.is_super_admin" class="shrink-0 flex items-center gap-2 flex-wrap">
                    <button
                        type="button"
                        @click="openPindahModal('aktifkan_kembali')"
                        class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                        title="Aktifkan kembali balita jika kembali berdomisili di posyandu ini"
                    >
                        <RotateCcw class="w-4 h-4" />
                        <span>Aktifkan Kembali</span>
                    </button>
                    <button
                        type="button"
                        @click="openPindahModal('posyandu_digital')"
                        class="px-4 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 font-bold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <ArrowRightLeft class="w-4 h-4" />
                        <span>Pindah Posyandu Digital</span>
                    </button>
                </div>
            </div>

            <!-- Banner Notifikasi Kader: Sweeping Balita Tidak Hadir Posyandu Bulan Ini -->
            <div
                v-if="missedBulanIni && (permissions.is_kader || permissions.is_bidan || permissions.is_admin_desa) && balita.status_domisili !== 'pindah'"
                class="bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-300 rounded-3xl p-5 sm:p-6 shadow-sm space-y-4"
            >
                <div class="flex items-start gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-amber-500/20">
                        <AlertTriangle class="w-6 h-6" />
                    </div>
                    <div class="space-y-1.5 flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2.5 py-0.5 rounded-full bg-amber-200 text-amber-900 text-[11px] font-black uppercase tracking-wider">
                                Tindak Lanjut Kader (Sweeping)
                            </span>
                            <span class="text-xs text-amber-800 font-bold flex items-center gap-1">
                                <Calendar class="w-3.5 h-3.5" />
                                Posyandu Bulan Ini: Senin Minggu ke-2 ({{ missedBulanIni.tanggal_formatted }})
                            </span>
                        </div>
                        <h3 class="text-base sm:text-lg font-black text-slate-900 tracking-tight">
                            Perhatian: {{ balita.nama_anak }} Tidak Hadir di Posyandu Bulan {{ missedBulanIni.bulan_nama }}
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-medium">
                            Balita belum ditimbang pada jadwal rutin Posyandu {{ missedBulanIni.bulan_nama }} {{ missedBulanIni.tahun }}. Silakan segera hubungi Ibu <strong class="text-slate-800">{{ balita.orangTua?.nama_ibu || 'Orang Tua' }}</strong> untuk menanyakan kondisi balita, atau lakukan kunjungan rumah (sweeping) lalu inputkan data antropometrinya.
                        </p>
                    </div>
                </div>

                <!-- Action Buttons Kader -->
                <div class="pt-2 border-t border-amber-200/80 flex flex-wrap items-center gap-2.5">
                    <a
                        v-if="ibuPhone"
                        :href="waUrl"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition cursor-pointer"
                    >
                        <MessageCircle class="w-4 h-4" />
                        <span>Hubungi Ibu via WhatsApp ({{ ibuPhone }})</span>
                    </a>
                    <a
                        v-if="ibuPhone"
                        :href="`tel:${ibuPhone}`"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold shadow-2xs transition"
                    >
                        <Phone class="w-4 h-4 text-slate-500" />
                        <span>Telepon Ibu</span>
                    </a>
                    <Link
                        v-if="permissions.can_create_pengukuran"
                        :href="route('pengukuran.create', { balita_id: balita.id })"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition"
                    >
                        <Activity class="w-4 h-4" />
                        <span>Input Antropometri Sekarang</span>
                    </Link>
                </div>
            </div>

            <!-- Profile Header Card -->
            <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-xs space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <!-- Avatar Gender Icon -->
                        <div
                            :class="[
                                'w-16 h-16 rounded-2xl flex items-center justify-center shrink-0 text-xl font-black shadow-xs',
                                balita.jenis_kelamin === 'L' ? 'bg-sky-100 text-sky-700' : 'bg-rose-100 text-rose-700'
                            ]"
                        >
                            {{ balita.jenis_kelamin === 'L' ? 'L' : 'P' }}
                        </div>

                        <div class="space-y-1">
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-tight">
                                    {{ balita.nama_anak }}
                                </h1>
                                <span v-if="balita.nik_anak" class="font-mono text-xs px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-600 font-semibold">
                                    {{ balita.nik_anak }}
                                </span>
                                <!-- Badge Status Domisili PINDAH -->
                                <span
                                    v-if="balita.status_domisili === 'pindah'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-900 border border-amber-300 shadow-2xs"
                                >
                                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                    <span>STATUS: PINDAH</span>
                                </span>
                            </div>

                            <p class="text-xs sm:text-sm text-slate-600 font-medium flex items-center gap-2 flex-wrap">
                                <span>Usia: <strong>{{ hitungUsiaDeskripsi(balita.tanggal_lahir) }}</strong></span>
                                <span>&bull; Lahir: <strong class="text-slate-800">{{ formatTanggal(balita.tanggal_lahir) }}</strong></span>
                                <span class="inline-flex items-center gap-1.5 font-bold text-slate-800 bg-slate-100 px-2.5 py-0.5 rounded-lg border border-slate-200">
                                    <Building2 class="w-3.5 h-3.5 text-rose-600" />
                                    {{ balita.posyandu?.nama_posyandu }}
                                </span>
                                <button
                                    v-if="permissions.is_admin_desa || permissions.is_bidan || permissions.is_kader || permissions.is_super_admin || permissions.is_orang_tua"
                                    type="button"
                                    @click="openPindahModal()"
                                    class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-2.5 py-1 rounded-lg border border-rose-200 transition cursor-pointer"
                                    title="Pindahkan posyandu atau ubah status domisili jika balita berpindah"
                                >
                                    <ArrowRightLeft class="w-3 h-3" />
                                    <span>{{ balita.status_domisili === 'pindah' ? 'Ubah Status Domisili' : 'Pindah Posyandu' }}</span>
                                </button>
                            </p>

                            <p class="text-xs text-slate-500 flex items-center gap-1.5 pt-0.5">
                                <MapPin class="w-3.5 h-3.5 text-slate-400" />
                                <span>{{ balita.orangTua?.alamat || '-' }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Orang Tua Info Mini Card -->
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 text-xs space-y-1 shrink-0 sm:max-w-xs">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Keluarga</span>
                        <p class="font-bold text-slate-800">Ibu: {{ balita.orangTua?.nama_ibu || '-' }}</p>
                        <p class="text-slate-600" v-if="balita.orangTua?.nama_ayah">Ayah: {{ balita.orangTua.nama_ayah }}</p>
                        <p class="text-slate-500 font-mono text-[11px]" v-if="balita.orangTua?.no_kk">No KK: {{ balita.orangTua.no_kk }}</p>
                    </div>
                </div>

                <!-- Latest Status Badges & Attendance Row -->
                <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-bold text-slate-500 pr-1">Status Terkini:</span>
                        <StatusBadge
                            v-if="balita.status_gizi_terakhir"
                            :status="balita.status_gizi_terakhir"
                            type="gizi"
                        />
                        <StatusBadge
                            v-if="balita.status_stunting_terakhir"
                            :status="balita.status_stunting_terakhir"
                            type="stunting"
                        />
                        <StatusBadge
                            v-if="balita.status_bbtb_terakhir"
                            :status="balita.status_bbtb_terakhir"
                            type="bbtb"
                        />
                    </div>

                    <!-- Badge Persentase Kehadiran Balita -->
                    <div class="flex items-center gap-2">
                        <span
                            :class="[
                                'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border',
                                daftarKehadiranPosyandu.persen >= 80
                                    ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                    : 'bg-amber-50 text-amber-800 border-amber-200'
                            ]"
                        >
                            <Calendar class="w-3.5 h-3.5" />
                            <span>Kehadiran Posyandu: {{ daftarKehadiranPosyandu.persen }}% ({{ daftarKehadiranPosyandu.totalHadir }}/{{ daftarKehadiranPosyandu.totalJadwal }} Hadir)</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Kartu Intervensi PMT & Stunting (Kemenkes RI) -->
            <div
                v-if="balita.kriteria_pmt?.layak_pmt"
                class="bg-gradient-to-r from-amber-50 via-orange-50 to-rose-50 border-2 border-amber-300 rounded-3xl p-5 sm:p-6 shadow-sm space-y-4"
            >
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-amber-500/20 text-2xl font-black">
                        🍲
                    </div>
                    <div class="space-y-1.5 flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-3 py-1 rounded-full bg-amber-500 text-white text-[11px] font-black uppercase tracking-wider shadow-xs">
                                {{ balita.kriteria_pmt.tipe }}
                            </span>
                            <span class="text-xs text-amber-900 font-bold bg-amber-100 px-3 py-1 rounded-full border border-amber-300">
                                Target Durasi: {{ balita.kriteria_pmt.durasi }}
                            </span>
                        </div>
                        <h3 class="text-base sm:text-lg font-black text-slate-900 tracking-tight">
                            Balita Layak Menerima Pemberian Makanan Tambahan (PMT) Pemulihan
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-medium">
                            Berdasarkan hasil analisis antropometri berkala Kemenkes RI, balita memenuhi indikasi prioritas intervensi gizi dan penanganan stunting karena:
                        </p>
                        <ul class="list-disc list-inside text-xs sm:text-sm text-amber-950 font-bold space-y-1 pt-1">
                            <li v-for="(alasan, idx) in balita.kriteria_pmt.alasan" :key="idx">
                                {{ alasan }}
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="pt-3 border-t border-amber-200/80 grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                    <div class="p-3.5 bg-white/90 rounded-2xl border border-amber-200/80 shadow-2xs">
                        <span class="font-black text-amber-900 block mb-1">Rekomendasi Menu PMT Berbahan Pangan Lokal:</span>
                        <p class="text-slate-600 font-medium leading-relaxed">{{ balita.kriteria_pmt.menu_rekomendasi }}</p>
                    </div>
                    <div class="p-3.5 bg-white/90 rounded-2xl border border-amber-200/80 shadow-2xs">
                        <span class="font-black text-amber-900 block mb-1">Tindak Lanjut Kader & Tenaga Kesehatan:</span>
                        <p class="text-slate-600 font-medium leading-relaxed">{{ balita.kriteria_pmt.tindakan }}</p>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs Bar -->
            <div class="flex items-center gap-2 p-1.5 bg-slate-200/60 rounded-2xl max-w-md text-xs sm:text-sm font-bold">
                <button
                    type="button"
                    @click="activeTab = 'pertumbuhan'"
                    :class="[
                        'flex-1 min-h-[44px] rounded-xl transition-all flex items-center justify-center gap-2 select-none cursor-pointer',
                        activeTab === 'pertumbuhan'
                            ? 'bg-white text-rose-600 shadow-md shadow-slate-900/5'
                            : 'text-slate-600 hover:text-slate-900'
                    ]"
                >
                    <Activity class="w-4 h-4" />
                    <span>Pertumbuhan (KMS)</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'imunisasi'"
                    :class="[
                        'flex-1 min-h-[44px] rounded-xl transition-all flex items-center justify-center gap-2 select-none cursor-pointer',
                        activeTab === 'imunisasi'
                            ? 'bg-white text-rose-600 shadow-md shadow-slate-900/5'
                            : 'text-slate-600 hover:text-slate-900'
                    ]"
                >
                    <Syringe class="w-4 h-4" />
                    <span>Imunisasi</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'riwayat'"
                    :class="[
                        'flex-1 min-h-[44px] rounded-xl transition-all flex items-center justify-center gap-2 select-none cursor-pointer',
                        activeTab === 'riwayat'
                            ? 'bg-white text-rose-600 shadow-md shadow-slate-900/5'
                            : 'text-slate-600 hover:text-slate-900'
                    ]"
                >
                    <FileText class="w-4 h-4" />
                    <span>Riwayat Tabel</span>
                </button>
            </div>

            <!-- TAB 1: PERTUMBUHAN & KMS CHART -->
            <div v-if="activeTab === 'pertumbuhan'" class="space-y-6">
                <!-- KMS Chart Component -->
                <KmsChart
                    :kurva-kms="kurvaKms"
                    :pengukuran="riwayatPengukuran"
                    :nama-anak="balita.nama_anak"
                />

                <!-- Ringkasan Pengukuran Terakhir -->
                <div v-if="riwayatPengukuran.length > 0" class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-xs space-y-4">
                    <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">
                        Hasil Penimbangan Terakhir ({{ formatTanggal(riwayatPengukuran[0].tanggal_kunjungan) }})
                    </h3>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Berat Badan</span>
                            <span class="text-lg font-black text-slate-900">{{ riwayatPengukuran[0].berat_badan_kg }} kg</span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Tinggi Badan</span>
                            <span class="text-lg font-black text-slate-900">{{ riwayatPengukuran[0].tinggi_badan_cm }} cm</span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Z-Score BB/U</span>
                            <span class="text-lg font-black text-slate-900">{{ riwayatPengukuran[0].zscore_bbu }} SD</span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Z-Score TB/U</span>
                            <span class="text-lg font-black text-slate-900">{{ riwayatPengukuran[0].zscore_tbu }} SD</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: IMUNISASI -->
            <div v-if="activeTab === 'imunisasi'" class="space-y-6">
                <!-- Action Bar Bidan/Admin -->
                <div class="flex items-center justify-between" v-if="permissions.can_manage_imunisasi">
                    <p class="text-xs sm:text-sm text-slate-500 font-medium">
                        Pencatatan riwayat imunisasi wajib balita
                    </p>
                    <button
                        type="button"
                        @click="isImunisasiModalOpen = true"
                        class="min-h-[44px] px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs sm:text-sm flex items-center gap-2 shadow-md shadow-rose-500/20 cursor-pointer"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Catat Vaksinasi</span>
                    </button>
                </div>

                <!-- Vaksinasi yang Sudah Diberikan Table -->
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">
                            Vaksin yang Telah Diberikan
                        </h3>
                        <span class="text-xs font-bold text-slate-500">
                            Total: {{ riwayatImunisasi.length }} Vaksin
                        </span>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <div
                            v-for="imun in riwayatImunisasi"
                            :key="imun.id"
                            class="p-4 sm:p-5 flex items-center justify-between gap-4 hover:bg-slate-50 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                    <Syringe class="w-5 h-5" />
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900 leading-tight">
                                        {{ imun.jenis_vaksin?.label || imun.jenis_vaksin }}
                                    </p>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        Diberikan: <strong class="text-slate-800">{{ formatTanggal(imun.tanggal_imunisasi) }}</strong> &bull; Oleh: {{ imun.petugas_kesehatan }}
                                        <span v-if="imun.batch_vaksin">&bull; Batch: {{ imun.batch_vaksin }}</span>
                                    </p>
                                </div>
                            </div>

                            <button
                                v-if="permissions.can_manage_imunisasi"
                                type="button"
                                @click="deleteImunisasi(imun.id)"
                                class="p-2 text-slate-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors cursor-pointer"
                                title="Hapus catatan"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>

                        <div v-if="riwayatImunisasi.length === 0" class="py-10 text-center text-xs text-slate-400">
                            Belum ada catatan imunisasi yang disimpan untuk balita ini.
                        </div>
                    </div>
                </div>

                <!-- Jadwal Rekomendasi Vaksin Berikutnya -->
                <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-xs space-y-4">
                    <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                        <Sparkles class="w-4 h-4 text-rose-600" />
                        <span>Jadwal Rekomendasi Imunisasi Nasional</span>
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div
                            v-for="v in rekomendasiVaksin"
                            :key="v.kode"
                            :class="[
                                'p-3.5 rounded-2xl border flex items-center justify-between text-xs',
                                v.sudah_diberikan
                                    ? 'bg-slate-50/70 border-slate-200 text-slate-400'
                                    : v.status_jadwal === 'Perlu Diberikan Sekarang'
                                        ? 'bg-rose-50 border-rose-300 text-rose-900 font-bold'
                                        : 'bg-white border-slate-200 text-slate-700'
                            ]"
                        >
                            <div>
                                <p class="font-bold text-sm">{{ v.nama }}</p>
                                <p class="text-[11px] opacity-80">Usia Target: {{ v.usia_target_bulan }} Bulan</p>
                            </div>

                            <span
                                :class="[
                                    'px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase',
                                    v.sudah_diberikan
                                        ? 'bg-emerald-100 text-emerald-800'
                                        : v.status_jadwal === 'Perlu Diberikan Sekarang'
                                            ? 'bg-rose-600 text-white'
                                            : 'bg-slate-100 text-slate-600'
                                ]"
                            >
                                {{ v.status_jadwal }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: RIWAYAT TABEL PERTUMBUHAN & DETEKSI KEHADIRAN -->
            <div v-if="activeTab === 'riwayat'" class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <p class="text-xs sm:text-sm text-slate-700 font-bold">
                            Rekam Kehadiran & Penimbangan Fisik Balita
                        </p>
                        <p class="text-xs text-slate-400">
                            Jadwal Posyandu dilaksanakan rutin setiap <strong>Senin Minggu ke-2</strong> per bulan
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700">
                            Kehadiran: {{ daftarKehadiranPosyandu.persen }}%
                        </span>
                        <button
                            type="button"
                            @click="toggleSort"
                            class="min-h-[38px] px-3 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 flex items-center gap-1.5 shadow-2xs hover:bg-slate-50 cursor-pointer"
                        >
                            <span>Urutkan: {{ sortOrder === 'desc' ? 'Terbaru' : 'Terlama' }}</span>
                            <ChevronDown v-if="sortOrder === 'desc'" class="w-4 h-4" />
                            <ChevronUp v-else class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden md:block bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4">Tanggal Kunjungan</th>
                                <th class="py-3.5 px-4">Usia</th>
                                <th class="py-3.5 px-4">BB (kg)</th>
                                <th class="py-3.5 px-4">TB (cm)</th>
                                <th class="py-3.5 px-4">Status Gizi</th>
                                <th class="py-3.5 px-4">Status TB/U</th>
                                <th class="py-3.5 px-4">BB/TB</th>
                                <th class="py-3.5 px-4">KMS</th>
                                <th class="py-3.5 px-4">Kader / Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template v-for="p in combinedPengukuran" :key="p.id">
                                <!-- BARIS JIKA TIDAK HADIR -->
                                <tr
                                    v-if="p.is_missed"
                                    class="bg-amber-50/70 border-l-4 border-l-amber-500 hover:bg-amber-100/60 transition"
                                >
                                    <td class="py-3.5 px-4">
                                        <div class="font-black text-amber-950 text-sm">{{ p.tanggal_formatted }}</div>
                                        <div class="text-[10px] text-amber-700 font-bold">Senin Minggu ke-2 (Jadwal Rutin)</div>
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-amber-900">{{ p.usia_bulan }} bln</td>
                                    <td colspan="6" class="py-3.5 px-4">
                                        <div class="flex items-center justify-between gap-3 flex-wrap">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-200/80 text-amber-900 border border-amber-300">
                                                    <AlertTriangle class="w-3.5 h-3.5 text-amber-700 shrink-0" />
                                                    <span>Tidak Hadir (Belum Ditimbang)</span>
                                                </span>
                                                <span class="text-xs text-amber-800 font-medium hidden lg:inline">
                                                    Belum ada data penimbangan pada sesi Posyandu ini
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <a
                                                    v-if="ibuPhone"
                                                    :href="waUrl"
                                                    target="_blank"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-2xs transition"
                                                >
                                                    <MessageCircle class="w-3.5 h-3.5" />
                                                    <span>Hubungi Ibu via WA</span>
                                                </a>
                                                <button
                                                    v-if="permissions.is_admin_desa || permissions.is_bidan || permissions.is_kader || permissions.is_super_admin"
                                                    type="button"
                                                    @click="siapkanDaftarBulanSinkronisasi(p.usia_bulan)"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-2xs transition cursor-pointer"
                                                    title="Cocokkan dan input data dari Buku KIA / KMS fisik balita"
                                                >
                                                    <BookOpen class="w-3.5 h-3.5" />
                                                    <span>Cocokkan Buku KMS</span>
                                                </button>
                                                <Link
                                                    v-if="permissions.can_create_pengukuran && balita.status_domisili !== 'pindah'"
                                                    :href="route('pengukuran.create', { balita_id: balita.id })"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-2xs transition"
                                                >
                                                    <Plus class="w-3.5 h-3.5" />
                                                    <span>Input Antropometri</span>
                                                </Link>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-amber-700 font-bold">Sweeping Kader</td>
                                </tr>

                                <!-- BARIS JIKA HADIR & ADA PENGUKURAN -->
                                <tr
                                    v-else
                                    :class="[
                                        'transition-colors',
                                        p.is_zona_merah ? 'bg-red-50/50 hover:bg-red-50' : 'hover:bg-slate-50/80'
                                    ]"
                                >
                                    <td class="py-3.5 px-4 font-bold text-slate-900">{{ p.tanggal_formatted }}</td>
                                    <td class="py-3.5 px-4">{{ p.usia_bulan }} bln</td>
                                    <td class="py-3.5 px-4 font-extrabold">{{ p.berat_badan_kg }}</td>
                                    <td class="py-3.5 px-4 font-extrabold">{{ p.tinggi_badan_cm }} ({{ p.cara_ukur_tinggi === 'telentang' ? 'PB' : 'TB' }})</td>
                                    <td class="py-3.5 px-4">
                                        <StatusBadge :status="p.status_gizi" type="gizi" size="sm" />
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <StatusBadge :status="p.status_stunting" type="stunting" size="sm" />
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <StatusBadge :status="p.status_bbtb" type="bbtb" size="sm" />
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <StatusBadge :status="p.kenaikan_bb" type="kenaikan" size="sm" />
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-500 truncate max-w-[120px]">{{ p.kader?.nama_lengkap || '-' }}</td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards View -->
                <div class="md:hidden space-y-3">
                    <template v-for="p in combinedPengukuran" :key="p.id">
                        <!-- CARD JIKA TIDAK HADIR -->
                        <div
                            v-if="p.is_missed"
                            class="p-4 rounded-2xl border border-amber-300 bg-amber-50/80 border-l-4 border-l-amber-500 space-y-3 shadow-2xs"
                        >
                            <div class="flex items-center justify-between text-xs font-bold">
                                <div>
                                    <span class="text-amber-950 font-black text-sm">{{ p.tanggal_formatted }}</span>
                                    <span class="text-[11px] text-amber-700 font-semibold block">Senin Minggu ke-2 (Usia: {{ p.usia_bulan }} bln)</span>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-amber-200 text-amber-900 border border-amber-300">
                                    Tidak Hadir
                                </span>
                            </div>

                            <p class="text-xs text-amber-900 font-medium">
                                Balita belum memiliki catatan penimbangan pada sesi Posyandu ini. Segera tanyakan kondisi ke Ibu dan lakukan sweeping.
                            </p>

                            <div class="flex items-center justify-end gap-2 pt-2 border-t border-amber-200 flex-wrap">
                                <button
                                    v-if="permissions.is_admin_desa || permissions.is_bidan || permissions.is_kader || permissions.is_super_admin"
                                    type="button"
                                    @click="siapkanDaftarBulanSinkronisasi(p.usia_bulan)"
                                    class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold inline-flex items-center gap-1.5 shadow-2xs cursor-pointer"
                                >
                                    <BookOpen class="w-3.5 h-3.5" />
                                    <span>Buku KMS</span>
                                </button>
                                <a
                                    v-if="ibuPhone"
                                    :href="waUrl"
                                    target="_blank"
                                    class="px-3 py-1.5 rounded-xl bg-emerald-600 text-white text-xs font-bold inline-flex items-center gap-1.5 shadow-2xs"
                                >
                                    <MessageCircle class="w-3.5 h-3.5" />
                                    <span>WA Ibu</span>
                                </a>
                                <Link
                                    v-if="permissions.can_create_pengukuran && balita.status_domisili !== 'pindah'"
                                    :href="route('pengukuran.create', { balita_id: balita.id })"
                                    class="px-3 py-1.5 rounded-xl bg-rose-600 text-white text-xs font-bold inline-flex items-center gap-1.5 shadow-2xs"
                                >
                                    <Plus class="w-3.5 h-3.5" />
                                    <span>Input Ukur</span>
                                </Link>
                            </div>
                        </div>

                        <!-- CARD JIKA HADIR & ADA PENGUKURAN -->
                        <div
                            v-else
                            :class="[
                                'p-4 rounded-2xl border transition-all space-y-3 shadow-xs',
                                p.is_zona_merah
                                    ? 'bg-red-50/40 border-red-300 border-l-4 border-l-red-600'
                                    : 'bg-white border-slate-200'
                            ]"
                        >
                            <div class="flex items-center justify-between text-xs font-bold text-slate-800">
                                <span>{{ p.tanggal_formatted }} (Usia: {{ p.usia_bulan }} bln)</span>
                                <StatusBadge :status="p.kenaikan_bb" type="kenaikan" size="sm" />
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                                    <span class="text-[10px] text-slate-400 block font-semibold">Berat Badan</span>
                                    <span class="text-base font-black text-slate-900">{{ p.berat_badan_kg }} kg</span>
                                </div>
                                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                                    <span class="text-[10px] text-slate-400 block font-semibold">Tinggi / Panjang</span>
                                    <span class="text-base font-black text-slate-900">{{ p.tinggi_badan_cm }} cm</span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                <StatusBadge :status="p.status_gizi" type="gizi" size="sm" />
                                <StatusBadge :status="p.status_stunting" type="stunting" size="sm" />
                                <StatusBadge :status="p.status_bbtb" type="bbtb" size="sm" />
                            </div>

                            <p v-if="p.catatan_kader" class="text-xs text-slate-600 bg-slate-50 p-2.5 rounded-xl italic">
                                "{{ p.catatan_kader }}"
                            </p>
                        </div>
                    </template>
                </div>

                <div v-if="combinedPengukuran.length === 0" class="py-12 text-center text-xs text-slate-400 bg-white rounded-3xl border border-slate-200">
                    Belum ada riwayat penimbangan untuk balita ini.
                </div>

                <!-- KARTU LEGEND & PANDUAN MEMBACA STATUS KMS (KEMENKES STANDAR) -->
                <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs p-5 sm:p-6 space-y-4 mt-4">
                    <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100">
                        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                            <Info class="w-4 h-4" />
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-slate-900 tracking-tight">
                                Panduan & Keterangan Status Pertumbuhan (KMS Kemenkes)
                            </h4>
                            <p class="text-xs text-slate-500 font-medium">
                                Penjelasan arti kode kenaikan berat badan dan indikator kurva standar WHO
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <!-- Kolom 1: Status Kenaikan KMS -->
                        <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-3">
                            <h5 class="font-extrabold text-slate-900 flex items-center gap-1.5 uppercase text-[11px] tracking-wider text-rose-600">
                                <Activity class="w-3.5 h-3.5" />
                                <span>Arti Status Kenaikan KMS (Timbangan)</span>
                            </h5>
                            <div class="space-y-2.5">
                                <div class="flex items-start gap-2.5">
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200 ring-1 ring-emerald-500/10 shrink-0 mt-0.5">
                                        KENAIKAN: N
                                    </span>
                                    <span class="text-slate-600 font-medium leading-relaxed">
                                        <strong class="text-slate-800">Naik (N):</strong> Berat badan naik mengikuti garis pertumbuhan dan memenuhi target Kenaikan Berat Minimal (KBM) sesuai usianya.
                                    </span>
                                </div>
                                <div class="flex items-start gap-2.5">
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-red-50 text-red-700 border border-red-200 ring-1 ring-red-500/10 shrink-0 mt-0.5">
                                        KENAIKAN: T
                                    </span>
                                    <span class="text-slate-600 font-medium leading-relaxed">
                                        <strong class="text-slate-800">Tidak Naik (T):</strong> Berat badan tidak naik, tetap, turun, atau tidak mencapai KBM. Jika terjadi 2 kali berturut-turut (2T), balita masuk <em class="text-red-700 font-semibold not-italic">Zona Merah</em>.
                                    </span>
                                </div>
                                <div class="flex items-start gap-2.5">
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-sky-50 text-sky-700 border border-sky-200 ring-1 ring-sky-500/10 shrink-0 mt-0.5">
                                        KENAIKAN: B
                                    </span>
                                    <span class="text-slate-600 font-medium leading-relaxed">
                                        <strong class="text-slate-800">Baru (B):</strong> Penimbangan pertama kali atau balita baru terdaftar, sehingga belum ada data pembanding dari bulan sebelumnya.
                                    </span>
                                </div>
                                <div class="flex items-start gap-2.5">
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200 ring-1 ring-amber-500/10 shrink-0 mt-0.5">
                                        TIDAK HADIR
                                    </span>
                                    <span class="text-slate-600 font-medium leading-relaxed">
                                        <strong class="text-slate-800">Tidak Hadir:</strong> Balita terlewat / tidak hadir pada jadwal sesi rutin (Senin Minggu ke-2). Kader perlu sweeping untuk menanyakan data pertumbuhan.
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom 2: Indikator Baku Standar Kemenkes (BB/U, TB/U, BB/TB) -->
                        <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-3">
                            <h5 class="font-extrabold text-slate-900 flex items-center gap-1.5 uppercase text-[11px] tracking-wider text-rose-600">
                                <Scale class="w-3.5 h-3.5" />
                                <span>Arti Indikator Status Gizi (Permenkes No. 2/2020)</span>
                            </h5>
                            <div class="space-y-2.5">
                                <div class="text-slate-600 leading-relaxed font-medium">
                                    <strong class="text-slate-900 block mb-0.5">BB/U (Berat Badan menurut Umur):</strong>
                                    Menilai status berat badan balita terhadap usia. Kategori:
                                    <span class="text-emerald-700 font-bold">BAIK</span> (-2 s/d +1 SD),
                                    <span class="text-amber-700 font-bold">KURANG</span> (-3 s/d &lt;-2 SD),
                                    <span class="text-red-700 font-bold">BURUK</span> (&lt;-3 SD),
                                    <span class="text-amber-700 font-bold">RISIKO LEBIH</span> (&gt;+1 SD).
                                </div>
                                <div class="text-slate-600 leading-relaxed font-medium">
                                    <strong class="text-slate-900 block mb-0.5">TB/U (Tinggi Badan menurut Umur):</strong>
                                    Indikator utama deteksi stunting / pertumbuhan fisik jangka panjang. Kategori:
                                    <span class="text-emerald-700 font-bold">NORMAL</span> (-2 s/d +3 SD),
                                    <span class="text-red-700 font-bold">STUNTED (PENDEK)</span> (-3 s/d &lt;-2 SD),
                                    <span class="text-red-700 font-bold">SEVERELY STUNTED</span> (&lt;-3 SD),
                                    <span class="text-amber-700 font-bold">TINGGI</span> (&gt;+3 SD).
                                </div>
                                <div class="text-slate-600 leading-relaxed font-medium">
                                    <strong class="text-slate-900 block mb-0.5">BB/TB (Berat Badan menurut Tinggi Badan):</strong>
                                    Indikator proporsi massa tubuh akut (wasting & obesitas). Kategori:
                                    <span class="text-emerald-700 font-bold">BAIK</span> (-2 s/d +1 SD),
                                    <span class="text-red-700 font-bold">KURANG (WASTED)</span> (-3 s/d &lt;-2 SD),
                                    <span class="text-red-700 font-bold">BURUK</span> (&lt;-3 SD),
                                    <span class="text-amber-700 font-bold">LEBIH / OBESITAS</span> (&gt;+2 SD).
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Catat Imunisasi (Bidan / Admin) -->
        <div v-if="isImunisasiModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl p-6 w-full max-w-md shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Catat Pemberian Vaksin</h3>
                    <button type="button" @click="isImunisasiModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitImunisasi" class="space-y-4 text-xs font-semibold">
                    <div>
                        <label class="block mb-1 text-slate-700">Jenis Vaksin *</label>
                        <select
                            v-model="imunisasiForm.jenis_vaksin"
                            required
                            class="w-full h-11 rounded-xl border border-slate-200 text-xs font-semibold focus:border-rose-500"
                        >
                            <option value="">-- Pilih Jenis Vaksin --</option>
                            <option v-for="v in rekomendasiVaksin" :key="v.kode" :value="v.kode">
                                {{ v.nama }} (Usia {{ v.usia_target_bulan }} Bln)
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1 text-slate-700">Tanggal Imunisasi *</label>
                        <input
                            type="date"
                            v-model="imunisasiForm.tanggal_imunisasi"
                            required
                            class="w-full h-11 rounded-xl border border-slate-200 text-xs font-semibold focus:border-rose-500"
                        />
                    </div>

                    <div>
                        <label class="block mb-1 text-slate-700">Petugas / Bidan Pelaksana *</label>
                        <input
                            type="text"
                            v-model="imunisasiForm.petugas_kesehatan"
                            required
                            placeholder="Nama Tenaga Kesehatan"
                            class="w-full h-11 rounded-xl border border-slate-200 text-xs font-semibold focus:border-rose-500"
                        />
                    </div>

                    <div>
                        <label class="block mb-1 text-slate-700">Nomor Batch Vaksin</label>
                        <input
                            type="text"
                            v-model="imunisasiForm.batch_vaksin"
                            placeholder="Contoh: BIO-FARMA-2026-X1"
                            class="w-full h-11 rounded-xl border border-slate-200 text-xs font-semibold focus:border-rose-500"
                        />
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            @click="isImunisasiModalOpen = false"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="imunisasiForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold shadow-md shadow-rose-500/20 disabled:opacity-60"
                        >
                            Simpan Imunisasi
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Pindah / Mutasi Posyandu Balita -->
        <div v-if="isPindahPosyanduModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl p-6 w-full max-w-lg shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <ArrowRightLeft class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-black text-slate-900">Status & Domisili Posyandu</h3>
                            <p class="text-xs text-slate-500 font-medium">Atur kepindahan posyandu atau mutasi keluar wilayah</p>
                        </div>
                    </div>
                    <button type="button" @click="isPindahPosyanduModalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Info Balita -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-600 space-y-1">
                    <p class="font-bold text-slate-800">
                        Balita: <span class="text-rose-600">{{ balita.nama_anak }}</span>
                    </p>
                    <p>Posyandu Saat Ini: <strong>{{ balita.posyandu?.nama_posyandu }}</strong> ({{ balita.posyandu?.kelurahan_desa }})</p>
                    <p>Status Domisili Saat Ini: <strong :class="balita.status_domisili === 'pindah' ? 'text-amber-700' : 'text-emerald-700'">{{ (balita.status_domisili || 'aktif').toUpperCase() }}</strong></p>
                </div>

                <!-- Tab Segmented Control -->
                <div class="grid grid-cols-2 gap-1.5 p-1 bg-slate-100 rounded-2xl text-xs font-bold">
                    <button
                        type="button"
                        @click="pindahForm.tipe_pindah = 'luar_wilayah'"
                        :class="[
                            'py-2 px-3 rounded-xl transition cursor-pointer text-center',
                            pindahForm.tipe_pindah === 'luar_wilayah' ? 'bg-white text-amber-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'
                        ]"
                    >
                        Pindah ke Luar Wilayah (Non-Aplikasi)
                    </button>
                    <button
                        type="button"
                        @click="pindahForm.tipe_pindah = 'posyandu_digital'"
                        :class="[
                            'py-2 px-3 rounded-xl transition cursor-pointer text-center',
                            pindahForm.tipe_pindah === 'posyandu_digital' ? 'bg-white text-rose-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'
                        ]"
                    >
                        Pindah ke Posyandu Digital Lain
                    </button>
                </div>

                <form @submit.prevent="submitPindahPosyandu" class="space-y-4 text-xs font-semibold">
                    <!-- CASE 1: PINDAH KE LUAR WILAYAH (NON-APLIKASI) -->
                    <div v-if="pindahForm.tipe_pindah === 'luar_wilayah'" class="space-y-3">
                        <div class="p-3.5 rounded-2xl bg-amber-50/80 border border-amber-200 text-amber-900 text-xs space-y-1">
                            <p class="font-bold flex items-center gap-1.5 text-amber-950">
                                <Info class="w-4 h-4 text-amber-600" />
                                <span>Pindah ke wilayah yang belum menggunakan Posyandu Digital</span>
                            </p>
                            <p class="text-[11px] text-amber-800 leading-relaxed font-medium">
                                Status balita akan diubah menjadi <strong class="text-amber-950 font-black">PINDAH</strong> tanpa perlu menentukan posyandu tujuan. Data rekam medis dan kurva pertumbuhan balita tetap tersimpan aman sebagai arsip historis.
                            </p>
                        </div>

                        <div>
                            <label class="block mb-1.5 text-slate-700">Catatan / Alasan Kepindahan (Opsional)</label>
                            <input
                                type="text"
                                v-model="pindahForm.catatan_pindah"
                                placeholder="cth: Pindah tempat tinggal ke luar daerah / luar kota"
                                class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs font-semibold focus:border-amber-500"
                            />
                        </div>

                        <div>
                            <label class="flex items-start gap-2 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    v-model="pindahForm.update_keluarga"
                                    class="w-4 h-4 rounded text-amber-600 focus:ring-amber-500 mt-0.5"
                                />
                                <span class="text-xs text-slate-600 font-medium">
                                    Terapkan status PINDAH ke seluruh saudara/anak lain dalam keluarga ini
                                </span>
                            </label>
                        </div>

                        <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="isPindahPosyanduModalOpen = false"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="pindahForm.processing"
                                class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold shadow-md shadow-amber-500/20 disabled:opacity-60 cursor-pointer"
                            >
                                {{ pindahForm.processing ? 'Menyimpan...' : 'Tandai Balita Telah PINDAH' }}
                            </button>
                        </div>
                    </div>

                    <!-- CASE 2: PINDAH KE POSYANDU DIGITAL LAIN -->
                    <div v-else-if="pindahForm.tipe_pindah === 'posyandu_digital'" class="space-y-3">
                        <div class="p-3.5 rounded-2xl bg-rose-50/70 border border-rose-200 text-rose-900 text-xs space-y-1">
                            <p class="font-bold flex items-center gap-1.5">
                                <Sparkles class="w-4 h-4 text-rose-600" />
                                <span>Pindah antar-posyandu digital</span>
                            </p>
                            <p class="text-[11px] text-rose-800 leading-relaxed font-medium">
                                Riwayat penimbangan antropometri, grafik KMS, dan imunisasi akan otomatis tersinkronisasi dan tampil di posyandu baru.
                            </p>
                        </div>

                        <div>
                            <label class="block mb-1.5 text-slate-700">Pilih Posyandu Tujuan Baru *</label>
                            <select
                                v-model="pindahForm.posyandu_id"
                                required
                                class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs font-semibold focus:border-rose-500"
                            >
                                <option value="">-- Pilih Posyandu Baru --</option>
                                <option
                                    v-for="p in posyanduList"
                                    :key="p.id"
                                    :value="p.id"
                                    :disabled="p.id === balita.posyandu_id && (balita.status_domisili || 'aktif') === 'aktif'"
                                >
                                    {{ p.nama_posyandu }} ({{ p.kelurahan_desa }}) {{ p.id === balita.posyandu_id ? '- (Posyandu Saat Ini)' : '' }}
                                </option>
                            </select>
                            <p v-if="pindahForm.errors.posyandu_id" class="text-xs text-rose-600 mt-1">
                                {{ pindahForm.errors.posyandu_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block mb-1.5 text-slate-700">Alamat Domisili Baru (Opsional)</label>
                            <textarea
                                v-model="pindahForm.alamat_baru"
                                rows="2"
                                placeholder="Alamat tempat tinggal baru (RT/RW, Dusun, Desa)"
                                class="w-full p-2.5 rounded-xl border border-slate-300 text-xs font-semibold focus:border-rose-500 resize-none"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block mb-1.5 text-slate-700">Alasan / Catatan Kepindahan (Opsional)</label>
                            <input
                                type="text"
                                v-model="pindahForm.catatan_pindah"
                                placeholder="cth: Pindah rumah mengikuti orang tua"
                                class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs font-semibold focus:border-rose-500"
                            />
                        </div>

                        <div>
                            <label class="flex items-start gap-2 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    v-model="pindahForm.update_keluarga"
                                    class="w-4 h-4 rounded text-rose-600 focus:ring-rose-500 mt-0.5"
                                />
                                <span class="text-xs text-slate-600 font-medium">
                                    Sinkronkan seluruh saudara/anak lain dari keluarga ini ke posyandu baru sekaligus
                                </span>
                            </label>
                        </div>

                        <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="isPindahPosyanduModalOpen = false"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="pindahForm.processing || !pindahForm.posyandu_id || (pindahForm.posyandu_id === balita.posyandu_id && (balita.status_domisili || 'aktif') === 'aktif')"
                                class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold shadow-md shadow-rose-500/20 disabled:opacity-60 cursor-pointer"
                            >
                                {{ pindahForm.processing ? 'Memindahkan...' : 'Konfirmasi Pindah Posyandu' }}
                            </button>
                        </div>
                    </div>

                    <!-- CASE 3: REAKTIVASI (KEMBALI KE POSYANDU INI) -->
                    <div v-else-if="pindahForm.tipe_pindah === 'aktifkan_kembali'" class="space-y-3">
                        <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs space-y-1">
                            <p class="font-bold flex items-center gap-1.5">
                                <RotateCcw class="w-4 h-4 text-emerald-600" />
                                <span>Aktifkan kembali balita di Posyandu {{ balita.posyandu?.nama_posyandu }}</span>
                            </p>
                            <p class="text-[11px] text-emerald-800 leading-relaxed font-medium">
                                Balita akan dikembalikan statusnya menjadi <strong class="font-black">AKTIF</strong> dan kembali dimasukkan ke dalam daftar pemantauan penimbangan rutin posyandu.
                            </p>
                        </div>

                        <div>
                            <label class="flex items-start gap-2 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    v-model="pindahForm.update_keluarga"
                                    class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 mt-0.5"
                                />
                                <span class="text-xs text-slate-600 font-medium">
                                    Aktifkan kembali seluruh saudara/anak lain dalam keluarga ini
                                </span>
                            </label>
                        </div>

                        <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="isPindahPosyanduModalOpen = false"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="pindahForm.processing"
                                class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-md shadow-emerald-500/20 disabled:opacity-60 cursor-pointer"
                            >
                                {{ pindahForm.processing ? 'Memproses...' : 'Aktifkan Kembali Balita' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL SINKRONISASI BUKU KMS FISIK (BUKU KIA) -->
        <div v-if="isSinkronisasiKmsModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto">
            <div class="bg-white rounded-3xl p-6 w-full max-w-4xl shadow-2xl border border-slate-100 space-y-4 my-8 max-h-[90vh] flex flex-col">
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <BookOpen class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-black text-slate-900">Sinkronisasi Data KMS Fisik (Buku KIA)</h3>
                            <p class="text-xs text-slate-500 font-medium">
                                Masukkan catatan penimbangan bulan-bulan sebelumnya yang dibawa dari buku KMS fisik
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="isSinkronisasiKmsModalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Info Box -->
                <div class="p-3.5 rounded-2xl bg-indigo-50/70 border border-indigo-200 text-indigo-950 text-xs shrink-0 space-y-1">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <span class="font-bold">Balita: <strong class="text-rose-600">{{ balita.nama_anak }}</strong></span>
                        <span class="text-[11px] font-semibold text-slate-600">
                            Tanggal Lahir: <strong>{{ formatTanggal(balita.tanggal_lahir) }}</strong> (Lahir: BB {{ balita.berat_lahir_kg }} kg, PB {{ balita.panjang_lahir_cm }} cm)
                        </span>
                    </div>
                    <p class="text-[11px] text-indigo-800 leading-relaxed font-medium">
                        📖 Cocokkan dan salin angka berat & tinggi badan dari Buku KIA / KMS fisik untuk bulan-bulan yang belum ada di bawah ini. Sistem akan otomatis mengalkulasi Z-score WHO standar Kemenkes dan menghubungkan titik kurva KMS secara lengkap.
                    </p>
                </div>

                <!-- Table Rows Scrollable -->
                <div class="overflow-y-auto flex-1 border border-slate-200 rounded-2xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200 sticky top-0 z-10">
                            <tr>
                                <th class="py-3 px-3 w-12 text-center">Pilih</th>
                                <th class="py-3 px-3">Usia / Bulan</th>
                                <th class="py-3 px-3">Tanggal Ukur</th>
                                <th class="py-3 px-3">BB (kg) *</th>
                                <th class="py-3 px-3">TB / PB (cm) *</th>
                                <th class="py-3 px-3">Posisi Ukur</th>
                                <th class="py-3 px-3">LK (cm)</th>
                                <th class="py-3 px-3">Status Data</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr
                                v-for="(row, idx) in syncRows"
                                :key="row.usia_bulan"
                                :class="[
                                    'transition',
                                    row.sudah_ada
                                        ? 'bg-slate-50/60 opacity-80'
                                        : row.pilih
                                            ? 'bg-indigo-50/40 hover:bg-indigo-50/60'
                                            : 'hover:bg-slate-50/50'
                                ]"
                            >
                                <!-- Checkbox -->
                                <td class="py-3 px-3 text-center">
                                    <input
                                        type="checkbox"
                                        v-model="row.pilih"
                                        :disabled="row.sudah_ada"
                                        class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 cursor-pointer disabled:cursor-not-allowed"
                                    />
                                </td>

                                <!-- Usia / Periode -->
                                <td class="py-3 px-3">
                                    <div class="font-extrabold text-slate-900">
                                        {{ row.usia_bulan === 0 ? 'Saat Lahir (0 bln)' : `${row.usia_bulan} Bulan` }}
                                    </div>
                                    <div class="text-[11px] text-slate-500">{{ row.label_bulan }}</div>
                                </td>

                                <!-- Tanggal Penimbangan -->
                                <td class="py-3 px-3">
                                    <input
                                        v-if="!row.sudah_ada"
                                        type="date"
                                        v-model="row.tanggal_kunjungan"
                                        :disabled="!row.pilih"
                                        class="w-36 h-9 px-2 rounded-lg border border-slate-300 text-xs font-semibold focus:border-indigo-500 disabled:bg-slate-100 disabled:opacity-60"
                                    />
                                    <span v-else class="text-slate-700 font-semibold">
                                        {{ formatTanggal(row.tanggal_kunjungan) }}
                                    </span>
                                </td>

                                <!-- Berat Badan (kg) -->
                                <td class="py-3 px-3">
                                    <div v-if="!row.sudah_ada" class="flex items-center gap-1">
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="1"
                                            max="40"
                                            v-model="row.berat_badan_kg"
                                            :disabled="!row.pilih"
                                            placeholder="cth: 4.20"
                                            class="w-24 h-9 px-2 rounded-lg border border-slate-300 text-xs font-bold focus:border-indigo-500 disabled:bg-slate-100 disabled:opacity-60"
                                        />
                                        <span class="text-slate-400 text-[11px]">kg</span>
                                    </div>
                                    <span v-else class="font-black text-slate-800">
                                        {{ row.berat_badan_kg }} kg
                                    </span>
                                </td>

                                <!-- Tinggi / Panjang (cm) -->
                                <td class="py-3 px-3">
                                    <div v-if="!row.sudah_ada" class="flex items-center gap-1">
                                        <input
                                            type="number"
                                            step="0.1"
                                            min="30"
                                            max="140"
                                            v-model="row.tinggi_badan_cm"
                                            :disabled="!row.pilih"
                                            placeholder="cth: 53.5"
                                            class="w-24 h-9 px-2 rounded-lg border border-slate-300 text-xs font-bold focus:border-indigo-500 disabled:bg-slate-100 disabled:opacity-60"
                                        />
                                        <span class="text-slate-400 text-[11px]">cm</span>
                                    </div>
                                    <span v-else class="font-black text-slate-800">
                                        {{ row.tinggi_badan_cm }} cm
                                    </span>
                                </td>

                                <!-- Posisi Ukur -->
                                <td class="py-3 px-3">
                                    <select
                                        v-if="!row.sudah_ada"
                                        v-model="row.cara_ukur_tinggi"
                                        :disabled="!row.pilih"
                                        class="h-9 px-2 rounded-lg border border-slate-300 text-xs font-semibold focus:border-indigo-500 disabled:bg-slate-100 disabled:opacity-60"
                                    >
                                        <option value="telentang">Telentang (PB)</option>
                                        <option value="berdiri">Berdiri (TB)</option>
                                    </select>
                                    <span v-else class="text-slate-600 text-[11px]">
                                        {{ row.cara_ukur_tinggi === 'telentang' ? 'Telentang' : 'Berdiri' }}
                                    </span>
                                </td>

                                <!-- Lingkar Kepala (cm) -->
                                <td class="py-3 px-3">
                                    <input
                                        v-if="!row.sudah_ada"
                                        type="number"
                                        step="0.1"
                                        min="20"
                                        max="60"
                                        v-model="row.lingkar_kepala_cm"
                                        :disabled="!row.pilih"
                                        placeholder="opsional"
                                        class="w-20 h-9 px-2 rounded-lg border border-slate-300 text-xs font-semibold focus:border-indigo-500 disabled:bg-slate-100 disabled:opacity-60"
                                    />
                                    <span v-else class="text-slate-600 text-[11px]">
                                        {{ row.lingkar_kepala_cm ? `${row.lingkar_kepala_cm} cm` : '-' }}
                                    </span>
                                </td>

                                <!-- Status Data -->
                                <td class="py-3 px-3">
                                    <span
                                        v-if="row.sudah_ada"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-200"
                                    >
                                        <CheckCircle2 class="w-3 h-3 text-emerald-600" />
                                        <span>Sudah Tercatat</span>
                                    </span>
                                    <span
                                        v-else-if="row.pilih"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-indigo-100 text-indigo-800 border border-indigo-200"
                                    >
                                        <span>Siap Disinkronkan</span>
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-800 border border-amber-200"
                                    >
                                        <span>Belum Ada</span>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Summary & Actions -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between shrink-0 flex-wrap gap-3">
                    <div class="text-xs text-slate-600">
                        Dipilih untuk disinkronkan:
                        <strong class="text-indigo-600 font-extrabold">
                            {{ syncRows.filter(r => r.pilih && r.berat_badan_kg && r.tinggi_badan_cm).length }} bulan
                        </strong>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="isSinkronisasiKmsModalOpen = false"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold cursor-pointer text-xs"
                        >
                            Tutup
                        </button>
                        <button
                            type="button"
                            @click="submitSinkronisasiKms"
                            :disabled="isSubmittingSync || syncRows.filter(r => r.pilih && r.berat_badan_kg && r.tinggi_badan_cm).length === 0"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-md shadow-indigo-500/20 disabled:opacity-50 cursor-pointer text-xs flex items-center gap-2"
                        >
                            <Sparkles class="w-4 h-4" />
                            <span>{{ isSubmittingSync ? 'Menyimpan & Menghubungkan...' : 'Simpan & Sinkronkan ke KMS Digital' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
