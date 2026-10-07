<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatTanggal } from '@/utils';
import {
    AlertCircle,
    Calendar,
    CheckCircle2,
    Clock,
    Filter,
    HeartPulse,
    Plus,
    RotateCcw,
    Search,
    ShieldAlert,
    Syringe,
    Trash2,
    User,
    Users,
    X
} from 'lucide-vue-next';

const props = defineProps({
    imunisasi: {
        type: Object,
        required: true
    },
    daftarVaksin: {
        type: Array,
        required: true
    },
    daftarPosyandu: {
        type: Array,
        default: () => []
    },
    vaksinTertunda: {
        type: Array,
        default: () => []
    },
    totalTertunda: {
        type: Number,
        default: 0
    },
    totalBulanIni: {
        type: Number,
        default: 0
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

const page = usePage();
const permissions = computed(() => page.props.auth?.permissions || {});
const user = computed(() => page.props.auth?.user);

// Tab: 'riwayat' | 'tertunda'
const activeTab = ref('riwayat');

// Filters State
const filterForm = ref({
    q: props.filters.q || '',
    jenis_vaksin: props.filters.jenis_vaksin || '',
    posyandu_id: props.filters.posyandu_id || ''
});

const applyFilters = () => {
    router.get(route('imunisasi.index'), {
        q: filterForm.value.q,
        jenis_vaksin: filterForm.value.jenis_vaksin,
        posyandu_id: filterForm.value.posyandu_id
    }, {
        preserveState: true,
        replace: true
    });
};

const resetFilters = () => {
    filterForm.value.q = '';
    filterForm.value.jenis_vaksin = '';
    filterForm.value.posyandu_id = '';
    applyFilters();
};

// Modal Pencatatan Imunisasi Baru (Bidan / Admin)
const isCreateModalOpen = ref(false);
const storeForm = useForm({
    balita_id: '',
    jenis_vaksin: '',
    tanggal_imunisasi: new Date().toISOString().split('T')[0],
    petugas_kesehatan: user.value?.nama_lengkap || '',
    batch_vaksin: '',
});

const submitStoreImunisasi = () => {
    storeForm.post(route('imunisasi.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isCreateModalOpen.value = false;
            storeForm.reset('balita_id', 'jenis_vaksin', 'batch_vaksin');
        }
    });
};

const openModalForBalita = (balitaId, defaultVaksin = '') => {
    storeForm.balita_id = balitaId;
    storeForm.jenis_vaksin = defaultVaksin;
    isCreateModalOpen.value = true;
};

const deleteImunisasi = (id, namaAnak, namaVaksin) => {
    if (confirm(`Yakin ingin menghapus catatan imunisasi ${namaVaksin} untuk ${namaAnak}?`)) {
        useForm({}).delete(route('imunisasi.destroy', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <AppLayout title="Pencatatan & Pelacakan Imunisasi Balita">
        <Head title="Manajemen Imunisasi Balita" />

        <div class="space-y-6">
            <!-- Header Halaman -->
            <div class="bg-white rounded-3xl p-5 sm:p-7 border border-slate-200/80 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-50 text-rose-700 text-xs font-bold">
                            <Syringe class="w-3.5 h-3.5" />
                            <span>Program Imunisasi Nasional (Kemenkes)</span>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            Pencatatan & Pelacakan Imunisasi Balita
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 font-medium">
                            Monitoring kelengkapan imunisasi dasar dan lanjutan 0–60 bulan
                        </p>
                    </div>

                    <!-- Tombol Aksi Cepat (Bidan & Admin) -->
                    <div v-if="permissions.can_manage_imunisasi" class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="isCreateModalOpen = true"
                            class="min-h-[44px] px-4 rounded-xl bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-700 hover:to-rose-600 text-white font-bold text-xs sm:text-sm flex items-center gap-2 shadow-md shadow-rose-500/20 transition-all active:scale-95"
                        >
                            <Plus class="w-4 h-4" />
                            <span>Catat Imunisasi Baru</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Metrik Singkat -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
                <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Diberikan Bulan Ini</span>
                    <div class="text-2xl font-black text-slate-900">{{ totalBulanIni }}</div>
                    <p class="text-[11px] text-slate-500 font-medium">Dosis vaksin tercatat</p>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Rekaman Terdata</span>
                    <div class="text-2xl font-black text-slate-900">{{ imunisasi.total || 0 }}</div>
                    <p class="text-[11px] text-slate-500 font-medium">Dalam cakupan wilayah</p>
                </div>

                <div class="col-span-2 sm:col-span-1 bg-white rounded-2xl p-4 border border-rose-200 bg-rose-50/20 shadow-2xs space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-rose-700 block">Vaksin Tertunda</span>
                    <div class="text-2xl font-black text-rose-600">{{ totalTertunda }}</div>
                    <p class="text-[11px] text-rose-700 font-medium">Perlu sweeping vaksinasi</p>
                </div>
            </div>

            <!-- Segmented Control Tabs -->
            <div class="flex items-center gap-2 p-1.5 bg-slate-200/60 rounded-2xl max-w-md text-xs sm:text-sm font-bold">
                <button
                    type="button"
                    @click="activeTab = 'riwayat'"
                    :class="[
                        'flex-1 min-h-[44px] rounded-xl transition-all flex items-center justify-center gap-2 select-none',
                        activeTab === 'riwayat'
                            ? 'bg-white text-rose-600 shadow-md shadow-slate-900/5'
                            : 'text-slate-600 hover:text-slate-900'
                    ]"
                >
                    <Syringe class="w-4 h-4" />
                    <span>Riwayat Imunisasi</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'tertunda'"
                    :class="[
                        'flex-1 min-h-[44px] rounded-xl transition-all flex items-center justify-center gap-2 select-none',
                        activeTab === 'tertunda'
                            ? 'bg-white text-rose-600 shadow-md shadow-slate-900/5'
                            : 'text-slate-600 hover:text-slate-900'
                    ]"
                >
                    <Clock class="w-4 h-4" />
                    <span>Vaksin Tertunda ({{ totalTertunda }})</span>
                </button>
            </div>

            <!-- TAB 1: RIWAYAT IMUNISASI TERCATAT -->
            <div v-if="activeTab === 'riwayat'" class="space-y-4">
                <!-- Filter & Search Bar -->
                <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-xs space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5">
                        <!-- Pencarian Nama / NIK -->
                        <div class="relative sm:col-span-5">
                            <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                type="text"
                                v-model="filterForm.q"
                                @keyup.enter="applyFilters"
                                placeholder="Cari nama balita / NIK..."
                                class="w-full h-11 pl-10 pr-4 rounded-xl border border-slate-200 text-xs font-semibold focus:border-rose-500"
                            />
                        </div>

                        <!-- Filter Jenis Vaksin -->
                        <div class="sm:col-span-4">
                            <select
                                v-model="filterForm.jenis_vaksin"
                                @change="applyFilters"
                                class="w-full h-11 rounded-xl border border-slate-200 text-xs font-semibold focus:border-rose-500"
                            >
                                <option value="">Semua Jenis Vaksin</option>
                                <option v-for="v in daftarVaksin" :key="v.kode" :value="v.kode">
                                    {{ v.nama }}
                                </option>
                            </select>
                        </div>

                        <!-- Filter Posyandu (Admin Desa) -->
                        <div v-if="daftarPosyandu.length > 0" class="sm:col-span-3">
                            <select
                                v-model="filterForm.posyandu_id"
                                @change="applyFilters"
                                class="w-full h-11 rounded-xl border border-slate-200 text-xs font-semibold focus:border-rose-500"
                            >
                                <option value="">Semua Posyandu</option>
                                <option v-for="p in daftarPosyandu" :key="p.id" :value="p.id">
                                    {{ p.nama_posyandu }}
                                </option>
                            </select>
                        </div>

                        <!-- Tombol Cari & Reset -->
                        <div class="flex items-center gap-2 sm:col-span-12 justify-end pt-1">
                            <button
                                type="button"
                                @click="resetFilters"
                                class="min-h-[38px] px-3 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-bold flex items-center gap-1.5"
                            >
                                <RotateCcw class="w-3.5 h-3.5" />
                                <span>Reset Filter</span>
                            </button>

                            <button
                                type="button"
                                @click="applyFilters"
                                class="min-h-[38px] px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold flex items-center gap-1.5"
                            >
                                <Filter class="w-3.5 h-3.5" />
                                <span>Terapkan</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden md:block bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-5">Tanggal</th>
                                <th class="py-3.5 px-4">Nama Balita</th>
                                <th class="py-3.5 px-4">Jenis Vaksin</th>
                                <th class="py-3.5 px-4">Petugas / Bidan</th>
                                <th class="py-3.5 px-4">No Batch</th>
                                <th class="py-3.5 px-4">Posyandu</th>
                                <th class="py-3.5 px-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="imun in imunisasi.data"
                                :key="imun.id"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <td class="py-3.5 px-5 font-bold text-slate-900">{{ formatTanggal(imun.tanggal_imunisasi) }}</td>
                                <td class="py-3.5 px-4">
                                    <Link :href="route('balita.show', imun.balita_id)" class="font-bold text-slate-900 hover:text-rose-600">
                                        {{ imun.nama_anak }}
                                    </Link>
                                    <div class="text-[11px] text-slate-500">Ibu: {{ imun.nama_ibu }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 font-bold text-xs">
                                        <Syringe class="w-3.5 h-3.5 text-emerald-600" />
                                        {{ imun.jenis_vaksin_label }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-700 font-medium">{{ imun.petugas_kesehatan }}</td>
                                <td class="py-3.5 px-4 font-mono text-slate-500">{{ imun.batch_vaksin || '-' }}</td>
                                <td class="py-3.5 px-4 text-slate-600">{{ imun.posyandu }}</td>
                                <td class="py-3.5 px-5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Link
                                            :href="route('balita.show', imun.balita_id)"
                                            class="p-1.5 text-slate-500 hover:text-rose-600 rounded-lg hover:bg-slate-100 transition-colors"
                                            title="Buka Rekam Medis"
                                        >
                                            <User class="w-4 h-4" />
                                        </Link>
                                        <button
                                            v-if="permissions.can_manage_imunisasi"
                                            type="button"
                                            @click="deleteImunisasi(imun.id, imun.nama_anak, imun.jenis_vaksin_label)"
                                            class="p-1.5 text-slate-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors"
                                            title="Hapus Catatan"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards View for Android -->
                <div class="md:hidden space-y-3">
                    <div
                        v-for="imun in imunisasi.data"
                        :key="imun.id"
                        class="p-4 rounded-2xl bg-white border border-slate-200 space-y-3 shadow-xs"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <Link :href="route('balita.show', imun.balita_id)" class="font-extrabold text-sm text-slate-900 hover:text-rose-600">
                                    {{ imun.nama_anak }}
                                </Link>
                                <p class="text-xs text-slate-500">Ibu: {{ imun.nama_ibu }} &bull; {{ imun.posyandu }}</p>
                            </div>
                            <span class="text-[11px] font-bold text-slate-500 font-mono">
                                {{ formatTanggal(imun.tanggal_imunisasi) }}
                            </span>
                        </div>

                        <div class="p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-200 flex items-center justify-between text-xs">
                            <span class="font-bold text-emerald-900">{{ imun.jenis_vaksin_label }}</span>
                            <span class="font-mono text-[11px] text-emerald-700" v-if="imun.batch_vaksin">#{{ imun.batch_vaksin }}</span>
                        </div>

                        <div class="flex items-center justify-between pt-1 text-xs text-slate-500">
                            <span>Petugas: {{ imun.petugas_kesehatan }}</span>

                            <div class="flex items-center gap-2">
                                <Link
                                    :href="route('balita.show', imun.balita_id)"
                                    class="font-bold text-rose-600 hover:text-rose-700"
                                >
                                    KMS &rarr;
                                </Link>

                                <button
                                    v-if="permissions.can_manage_imunisasi"
                                    type="button"
                                    @click="deleteImunisasi(imun.id, imun.nama_anak, imun.jenis_vaksin_label)"
                                    class="p-1 text-slate-400 hover:text-red-600"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="imunisasi.data.length === 0" class="py-12 text-center text-xs text-slate-400 bg-white rounded-3xl border border-slate-200">
                        Tidak ada riwayat imunisasi yang sesuai kriteria filter.
                    </div>
                </div>

                <!-- Pagination Links -->
                <div v-if="imunisasi.links && imunisasi.links.length > 3" class="flex items-center justify-center gap-1 pt-2">
                    <template v-for="(link, i) in imunisasi.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                'min-h-[38px] px-3.5 rounded-xl text-xs font-bold flex items-center justify-center transition-all',
                                link.active
                                    ? 'bg-rose-600 text-white shadow-xs'
                                    : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'
                            ]"
                        />
                        <span
                            v-else
                            v-html="link.label"
                            class="min-h-[38px] px-3.5 rounded-xl text-xs font-medium text-slate-400 flex items-center justify-center"
                        />
                    </template>
                </div>
            </div>

            <!-- TAB 2: DAFTAR VAKSIN TERTUNDA (SWEEPING) -->
            <div v-if="activeTab === 'tertunda'" class="space-y-4">
                <div class="bg-rose-50/70 border border-rose-200 rounded-2xl p-4 flex items-start gap-3 text-xs text-rose-900">
                    <Clock class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
                    <div class="leading-relaxed">
                        <strong>Daftar Sasaran Sweeping Vaksinasi:</strong> Balita-balita berikut telah mencapai usia pemberian vaksin namun belum tercatat menerima dosis tersebut. Gunakan daftar ini untuk menghubungi orang tua atau menjadwalkan suntikan saat kunjungan posyandu.
                    </div>
                </div>

                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="divide-y divide-slate-100">
                        <div
                            v-for="(item, idx) in vaksinTertunda"
                            :key="`${item.balita_id}-${item.vaksin_kode}`"
                            class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50 transition-colors"
                        >
                            <div class="flex items-center gap-3.5">
                                <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                                    <Syringe class="w-5 h-5" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="font-extrabold text-sm text-slate-900">{{ item.nama_anak }}</h4>
                                        <span class="px-2 py-0.5 rounded-md bg-rose-100 text-rose-800 text-[10px] font-black">
                                            Terlambat {{ item.keterlambatan_bulan }} Bulan
                                        </span>
                                    </div>
                                    <p class="text-xs font-bold text-rose-600 mt-0.5">
                                        {{ item.vaksin_nama }} (Target: Usia {{ item.usia_target }} Bulan)
                                    </p>
                                    <p class="text-[11px] text-slate-400">
                                        Usia saat ini: {{ item.usia_bulan }} bulan &bull; Ibu: {{ item.nama_ibu }} &bull; {{ item.posyandu }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 self-end sm:self-center">
                                <Link
                                    :href="route('balita.show', item.balita_id)"
                                    class="px-3.5 py-2 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold"
                                >
                                    Buka KMS
                                </Link>

                                <button
                                    v-if="permissions.can_manage_imunisasi"
                                    type="button"
                                    @click="openModalForBalita(item.balita_id, item.vaksin_kode)"
                                    class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs flex items-center gap-1.5"
                                >
                                    <Plus class="w-3.5 h-3.5" />
                                    <span>Catat Vaksinasi</span>
                                </button>
                            </div>
                        </div>

                        <div v-if="vaksinTertunda.length === 0" class="py-12 text-center text-xs text-slate-400">
                            <CheckCircle2 class="w-8 h-8 text-emerald-500 mx-auto mb-2" />
                            <p class="font-bold text-slate-700 text-sm">Semua Vaksin Terpenuhi</p>
                            <p class="text-slate-400 mt-1">Tidak ditemukan balita yang terlambat menerima imunisasi wajib.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Catat Imunisasi Baru (Bidan / Admin) -->
        <div v-if="isCreateModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl p-6 w-full max-w-md shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Catat Pemberian Vaksinasi</h3>
                    <button type="button" @click="isCreateModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitStoreImunisasi" class="space-y-4 text-xs font-semibold">
                    <div>
                        <label class="block mb-1 text-slate-700">ID / Balita Sasaran *</label>
                        <input
                            type="number"
                            v-model="storeForm.balita_id"
                            required
                            placeholder="Masukkan ID Balita (contoh: 1)"
                            class="w-full h-11 rounded-xl border border-slate-200 text-xs font-semibold focus:border-rose-500"
                        />
                        <p class="text-[10px] text-slate-400 mt-1">Catatan: Anda juga dapat mencatat vaksinasi langsung dari halaman profil KMS Balita.</p>
                    </div>

                    <div>
                        <label class="block mb-1 text-slate-700">Jenis Vaksin *</label>
                        <select
                            v-model="storeForm.jenis_vaksin"
                            required
                            class="w-full h-11 rounded-xl border border-slate-200 text-xs font-semibold focus:border-rose-500"
                        >
                            <option value="">-- Pilih Jenis Vaksin --</option>
                            <option v-for="v in daftarVaksin" :key="v.kode" :value="v.kode">
                                {{ v.nama }} (Usia Target {{ v.usia_target_bulan }} Bln)
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1 text-slate-700">Tanggal Imunisasi *</label>
                        <input
                            type="date"
                            v-model="storeForm.tanggal_imunisasi"
                            required
                            class="w-full h-11 rounded-xl border border-slate-200 text-xs font-semibold focus:border-rose-500"
                        />
                    </div>

                    <div>
                        <label class="block mb-1 text-slate-700">Petugas / Bidan Pelaksana *</label>
                        <input
                            type="text"
                            v-model="storeForm.petugas_kesehatan"
                            required
                            placeholder="Nama Tenaga Kesehatan"
                            class="w-full h-11 rounded-xl border border-slate-200 text-xs font-semibold focus:border-rose-500"
                        />
                    </div>

                    <div>
                        <label class="block mb-1 text-slate-700">Nomor Batch Vaksin</label>
                        <input
                            type="text"
                            v-model="storeForm.batch_vaksin"
                            placeholder="Contoh: BIO-FARMA-2026-X1"
                            class="w-full h-11 rounded-xl border border-slate-200 text-xs font-semibold focus:border-rose-500"
                        />
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            @click="isCreateModalOpen = false"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="storeForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold shadow-md shadow-rose-500/20 disabled:opacity-60"
                        >
                            Simpan Imunisasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
