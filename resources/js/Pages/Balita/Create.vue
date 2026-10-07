<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BigInput from '@/Components/BigInput.vue';
import FeedbackModal from '@/Components/FeedbackModal.vue';
import {
    Baby,
    ArrowLeft,
    CheckCircle2,
    Calendar,
    Users,
    Scale,
    Ruler,
    Heart,
    Loader2,
    UserPlus,
    Building2,
    AlertCircle,
    Info
} from 'lucide-vue-next';

const props = defineProps({
    posyanduList: {
        type: Array,
        default: () => []
    },
    orangTuaList: {
        type: Array,
        default: () => []
    },
    defaultPosyanduId: {
        type: [Number, String],
        default: null
    }
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

const isAdmin = computed(() => {
    const peran = user.value?.peran?.value || user.value?.peran;
    return peran === 'admin_desa' || peran === 'super_admin';
});

// Pilihan mode orang tua: 'pilih' (sudah ada di database) atau 'baru' (isi data orang tua sekarang)
const modeOrangTua = ref('pilih');

const form = useForm({
    orang_tua_id: '',
    // Data Orang Tua Baru (jika mode = 'baru')
    no_kk: '',
    nik_ibu: '',
    nama_ibu: '',
    nik_ayah: '',
    nama_ayah: '',
    alamat: '',
    golongan_darah: '',

    // Data Balita Baru
    posyandu_id: props.defaultPosyanduId || (props.posyanduList.length > 0 ? props.posyanduList[0].id : ''),
    nik_anak: '',
    nama_anak: '',
    jenis_kelamin: 'L',
    tanggal_lahir: '',
    berat_lahir_kg: '3.20',
    panjang_lahir_cm: '50.0',
    lingkar_kepala_lahir_cm: '34.0',
});

// Local Feedback Modal for Client-side Validation Guidance
const showLocalModal = ref(false);
const localModalType = ref('error');
const localModalTitle = ref('');
const localModalMessage = ref('');
const localModalErrors = ref([]);

const submit = () => {
    const errors = [];

    if (isAdmin.value && !form.posyandu_id) {
        errors.push('Posyandu penugasan domisili wajib dipilih');
    }

    if (modeOrangTua.value === 'pilih') {
        if (!form.orang_tua_id) {
            errors.push('Orang tua / ibu terdaftar belum dipilih. Silakan pilih dari daftar atau beralih ke tab "Baru"');
        }
        form.no_kk = '';
        form.nik_ibu = '';
        form.nama_ibu = '';
        form.nik_ayah = '';
        form.nama_ayah = '';
        form.alamat = '';
    } else {
        form.orang_tua_id = '';
        if (!form.nama_ibu || !form.nama_ibu.trim()) {
            errors.push('Nama lengkap ibu kandung wajib diisi');
        }
        if (!form.nik_ibu || form.nik_ibu.replace(/\D/g, '').length !== 16) {
            errors.push('NIK Ibu kandung wajib tepat 16 digit angka');
        }
        if (!form.no_kk || form.no_kk.replace(/\D/g, '').length !== 16) {
            errors.push('Nomor KK wajib tepat 16 digit angka');
        }
        if (!form.alamat || !form.alamat.trim()) {
            errors.push('Alamat domisili wajib diisi');
        }
    }

    if (!form.nama_anak || !form.nama_anak.trim()) {
        errors.push('Nama lengkap balita wajib diisi');
    }

    if (!form.tanggal_lahir) {
        errors.push('Tanggal lahir balita wajib diisi');
    }

    if (!form.berat_lahir_kg || parseFloat(form.berat_lahir_kg) <= 0) {
        errors.push('Berat lahir balita wajib diisi (contoh: 3.20 kg)');
    }

    if (!form.panjang_lahir_cm || parseFloat(form.panjang_lahir_cm) <= 0) {
        errors.push('Panjang lahir balita wajib diisi (contoh: 50.0 cm)');
    }

    if (!form.lingkar_kepala_lahir_cm || parseFloat(form.lingkar_kepala_lahir_cm) <= 0) {
        errors.push('Lingkar kepala lahir balita wajib diisi (contoh: 34.0 cm)');
    }

    if (errors.length > 0) {
        localModalType.value = 'error';
        localModalTitle.value = 'Data Pendaftaran Belum Lengkap';
        localModalMessage.value = 'Mohon lengkapi dan periksa data formulir berikut sebelum menyimpan:';
        localModalErrors.value = errors;
        showLocalModal.value = true;
        return;
    }

    form.post(route('balita.store'), {
        preserveScroll: true,
        onError: (errs) => {
            localModalType.value = 'error';
            localModalTitle.value = 'Pendaftaran Balita Gagal';
            localModalMessage.value = 'Terdapat data yang ditolak oleh sistem server:';
            localModalErrors.value = Object.values(errs).flat();
            showLocalModal.value = true;
        }
    });
};
</script>

<template>
    <AppLayout title="Pendaftaran Balita Baru">
        <Head title="Daftarkan Balita Baru" />

        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Header Row -->
            <div class="flex items-center gap-3">
                <Link
                    :href="route('balita.index')"
                    class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-100 transition-colors shadow-2xs"
                >
                    <ArrowLeft class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                        Pendaftaran Balita Baru
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 font-medium">
                        Input data kelahiran dan identitas orang tua balita
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- SECTION 1: Data Posyandu Wilayah (Khusus Admin Desa & Super Admin) -->
                <div v-if="isAdmin" class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-xs space-y-4">
                    <label class="block text-sm font-bold text-slate-800 flex items-center gap-2">
                        <Building2 class="w-4 h-4 text-rose-600" />
                        <span>Pilih Posyandu Domisili Balita *</span>
                    </label>
                    <select
                        v-model="form.posyandu_id"
                        required
                        class="w-full min-h-[50px] px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 bg-white focus:border-rose-500"
                    >
                        <option value="">-- Pilih Posyandu Penugasan --</option>
                        <option v-for="p in posyanduList" :key="p.id" :value="p.id">
                            {{ p.nama_posyandu }} &bull; {{ p.kelurahan_desa }}
                        </option>
                    </select>
                    <p v-if="form.errors.posyandu_id" class="text-xs font-semibold text-red-600 mt-1">
                        {{ form.errors.posyandu_id }}
                    </p>
                </div>

                <!-- SECTION 2: Identitas Orang Tua / Wali -->
                <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-xs space-y-5">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2 text-rose-600 font-bold text-sm">
                            <Users class="w-4 h-4" />
                            <span>Data Orang Tua / Wali</span>
                        </div>

                        <!-- Toggle Mode Orang Tua -->
                        <div class="flex p-1 bg-slate-100 rounded-xl text-xs font-bold">
                            <button
                                type="button"
                                @click="modeOrangTua = 'pilih'"
                                :class="[
                                    'px-3 py-1.5 rounded-lg transition-all',
                                    modeOrangTua === 'pilih' ? 'bg-white text-rose-600 shadow-xs' : 'text-slate-600'
                                ]"
                            >
                                Sudah Ada
                            </button>
                            <button
                                type="button"
                                @click="modeOrangTua = 'baru'"
                                :class="[
                                    'px-3 py-1.5 rounded-lg transition-all',
                                    modeOrangTua === 'baru' ? 'bg-white text-rose-600 shadow-xs' : 'text-slate-600'
                                ]"
                            >
                                Baru
                            </button>
                        </div>
                    </div>

                    <!-- Mode 1: Pilih Orang Tua yang Sudah Ada -->
                    <div v-if="modeOrangTua === 'pilih'" class="space-y-2">
                        <label class="block text-sm font-bold text-slate-800">
                            Pilih Ibu / Keluarga Terdaftar *
                        </label>
                        <select
                            v-model="form.orang_tua_id"
                            class="w-full min-h-[52px] px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 bg-white focus:border-rose-500 focus:ring-4 focus:ring-rose-100"
                        >
                            <option value="">-- Pilih Orang Tua Berdasarkan Nama / NIK Ibu --</option>
                            <option v-for="ot in orangTuaList" :key="ot.id" :value="ot.id">
                                Ibu: {{ ot.nama_ibu }} (NIK: {{ ot.nik_ibu }}) - KK: {{ ot.no_kk }}
                            </option>
                        </select>
                        <p v-if="form.errors.orang_tua_id" class="text-xs font-semibold text-red-600 mt-1">
                            {{ form.errors.orang_tua_id }}
                        </p>
                    </div>

                    <!-- Mode 2: Input Orang Tua Baru Secara Langsung -->
                    <div v-else class="space-y-4 animate-in fade-in duration-200">
                        <!-- Guidance Notice: Orang Tua Belum Punya Akun -->
                        <div class="p-3.5 rounded-2xl bg-sky-50 border border-sky-200 text-sky-950 text-xs flex items-start gap-2.5">
                            <Info class="w-4 h-4 text-sky-600 shrink-0 mt-0.5" />
                            <div class="space-y-0.5 leading-relaxed">
                                <p class="font-bold text-sky-900">Orang tua belum memiliki akun aplikasi? Tidak masalah!</p>
                                <p class="text-[11px] text-sky-800">
                                    Kader cukup melengkapi identitas dasar di bawah ini. Balita langsung terdaftar dan dapat langsung ditimbang hari ini.
                                    Orang tua nantinya dapat mendaftar akun sendiri kapan saja melalui halaman <strong>Daftar Akun (Register)</strong> menggunakan <strong>NIK Ibu</strong> yang sama, dan sistem akan <strong>otomatis menghubungkan (auto-link)</strong> seluruh data balita dan riwayat KMS ke HP mereka.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Kartu Keluarga (KK) *</label>
                                <input
                                    type="text"
                                    v-model="form.no_kk"
                                    maxlength="16"
                                    placeholder="16 digit nomor KK"
                                    class="w-full min-h-[48px] px-3.5 text-sm font-semibold rounded-xl border border-slate-200 focus:border-rose-500"
                                />
                                <p v-if="form.errors.no_kk" class="text-xs font-semibold text-red-600 mt-1">{{ form.errors.no_kk }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">NIK Ibu Kandung *</label>
                                <input
                                    type="text"
                                    v-model="form.nik_ibu"
                                    maxlength="16"
                                    placeholder="16 digit NIK Ibu"
                                    class="w-full min-h-[48px] px-3.5 text-sm font-semibold rounded-xl border border-slate-200 focus:border-rose-500"
                                />
                                <p v-if="form.errors.nik_ibu" class="text-xs font-semibold text-red-600 mt-1">{{ form.errors.nik_ibu }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap Ibu *</label>
                                <input
                                    type="text"
                                    v-model="form.nama_ibu"
                                    placeholder="Nama Ibu Kandung"
                                    class="w-full min-h-[48px] px-3.5 text-sm font-semibold rounded-xl border border-slate-200 focus:border-rose-500"
                                />
                                <p v-if="form.errors.nama_ibu" class="text-xs font-semibold text-red-600 mt-1">{{ form.errors.nama_ibu }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap Ayah</label>
                                <input
                                    type="text"
                                    v-model="form.nama_ayah"
                                    placeholder="Nama Ayah (Opsional)"
                                    class="w-full min-h-[48px] px-3.5 text-sm font-semibold rounded-xl border border-slate-200 focus:border-rose-500"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Domisili *</label>
                            <textarea
                                v-model="form.alamat"
                                rows="2"
                                placeholder="Jalan, RT/RW, Dusun/Lingkungan"
                                class="w-full p-3 text-sm font-semibold rounded-xl border border-slate-200 focus:border-rose-500"
                            ></textarea>
                            <p v-if="form.errors.alamat" class="text-xs font-semibold text-red-600 mt-1">{{ form.errors.alamat }}</p>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: Identitas Balita -->
                <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-xs space-y-5">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100 text-rose-600 font-bold text-sm">
                        <Baby class="w-4 h-4" />
                        <span>Identitas Balita Baru</span>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-800 mb-1">
                            Nama Lengkap Balita *
                        </label>
                        <input
                            type="text"
                            v-model="form.nama_anak"
                            placeholder="Contoh: Muhammad Rayyan"
                            class="w-full min-h-[52px] px-4 text-base font-bold text-slate-900 rounded-2xl border-2 border-slate-200 focus:border-rose-500 focus:ring-4 focus:ring-rose-100"
                        />
                        <p v-if="form.errors.nama_anak" class="text-xs font-semibold text-red-600 mt-1">
                            {{ form.errors.nama_anak }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-800 mb-1">
                                NIK Balita (Jika Sudah Ada)
                            </label>
                            <input
                                type="text"
                                v-model="form.nik_anak"
                                maxlength="16"
                                placeholder="16 digit NIK Balita"
                                class="w-full min-h-[52px] px-4 text-base font-semibold font-mono text-slate-900 rounded-2xl border-2 border-slate-200 focus:border-rose-500"
                            />
                            <p v-if="form.errors.nik_anak" class="text-xs font-semibold text-red-600 mt-1">
                                {{ form.errors.nik_anak }}
                            </p>
                        </div>

                        <!-- Jenis Kelamin Radio Switch -->
                        <div>
                            <label class="block text-sm font-bold text-slate-800 mb-1">
                                Jenis Kelamin *
                            </label>
                            <div class="grid grid-cols-2 gap-2 min-h-[52px]">
                                <button
                                    type="button"
                                    @click="form.jenis_kelamin = 'L'"
                                    :class="[
                                        'rounded-2xl border-2 font-bold text-xs flex items-center justify-center gap-2 transition-all',
                                        form.jenis_kelamin === 'L' ? 'bg-sky-50 border-sky-500 text-sky-700 shadow-xs' : 'border-slate-200 text-slate-600'
                                    ]"
                                >
                                    <span class="w-2.5 h-2.5 rounded-full" :class="form.jenis_kelamin === 'L' ? 'bg-sky-600' : 'bg-transparent'"></span>
                                    Laki-laki
                                </button>
                                <button
                                    type="button"
                                    @click="form.jenis_kelamin = 'P'"
                                    :class="[
                                        'rounded-2xl border-2 font-bold text-xs flex items-center justify-center gap-2 transition-all',
                                        form.jenis_kelamin === 'P' ? 'bg-rose-50 border-rose-500 text-rose-700 shadow-xs' : 'border-slate-200 text-slate-600'
                                    ]"
                                >
                                    <span class="w-2.5 h-2.5 rounded-full" :class="form.jenis_kelamin === 'P' ? 'bg-rose-600' : 'bg-transparent'"></span>
                                    Perempuan
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tanggal Lahir -->
                    <div>
                        <label class="block text-sm font-bold text-slate-800 mb-1">
                            Tanggal Lahir *
                        </label>
                        <div class="relative">
                            <Calendar class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                            <input
                                type="date"
                                v-model="form.tanggal_lahir"
                                :max="new Date().toISOString().split('T')[0]"
                                class="w-full min-h-[52px] pl-12 pr-4 text-base font-semibold text-slate-900 bg-white border-2 border-slate-200 rounded-2xl focus:border-rose-500"
                            />
                        </div>
                        <p v-if="form.errors.tanggal_lahir" class="text-xs font-semibold text-red-600 mt-1">
                            {{ form.errors.tanggal_lahir }}
                        </p>
                    </div>
                </div>

                <!-- SECTION 4: Data Antropometri Kelahiran -->
                <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-xs space-y-5">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100 text-rose-600 font-bold text-sm">
                        <Scale class="w-4 h-4" />
                        <span>Data Fisik Saat Lahir</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <BigInput
                            id="berat_lahir_kg"
                            v-model="form.berat_lahir_kg"
                            label="Berat Lahir *"
                            unit="kg"
                            placeholder="3.20"
                            :step="0.05"
                            :min="0.5"
                            :max="30"
                            :error="form.errors.berat_lahir_kg"
                        />

                        <BigInput
                            id="panjang_lahir_cm"
                            v-model="form.panjang_lahir_cm"
                            label="Panjang Lahir *"
                            unit="cm"
                            placeholder="50.0"
                            :step="0.1"
                            :min="35"
                            :max="130"
                            :error="form.errors.panjang_lahir_cm"
                        />

                        <BigInput
                            id="lingkar_kepala_lahir_cm"
                            v-model="form.lingkar_kepala_lahir_cm"
                            label="Lingkar Kepala Lahir *"
                            unit="cm"
                            placeholder="34.0"
                            :step="0.1"
                            :min="25"
                            :max="65"
                            :error="form.errors.lingkar_kepala_lahir_cm"
                        />
                    </div>
                </div>

                <!-- Submit Button Bar -->
                <div class="sticky bottom-20 md:bottom-6 z-30 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full min-h-[56px] px-6 rounded-2xl bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-700 hover:to-rose-600 active:scale-[0.99] text-white text-base font-bold shadow-xl shadow-rose-500/25 flex items-center justify-center gap-2.5 transition-all disabled:opacity-60 disabled:cursor-not-allowed select-none touch-manipulation"
                    >
                        <Loader2 v-if="form.processing" class="w-5 h-5 animate-spin" />
                        <CheckCircle2 v-else class="w-5 h-5" />
                        <span>{{ form.processing ? 'Menyimpan Data...' : 'Simpan Pendaftaran Balita' }}</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Local Validation Feedback Modal -->
        <FeedbackModal
            :show="showLocalModal"
            :type="localModalType"
            :title="localModalTitle"
            :message="localModalMessage"
            :errors="localModalErrors"
            @close="showLocalModal = false"
        />
    </AppLayout>
</template>
