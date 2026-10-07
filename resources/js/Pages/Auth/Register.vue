<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    HeartPulse,
    UserPlus,
    Mail,
    Phone,
    Lock,
    Building2,
    CheckCircle2,
    User,
    ArrowRight,
    ShieldCheck,
    CreditCard,
    Home,
    Users,
    Baby,
    Sparkles,
    ShieldAlert,
    Info
} from 'lucide-vue-next';

const props = defineProps({
    posyandus: {
        type: Array,
        default: () => []
    }
});

const showPassword = ref(false);

const form = useForm({
    nama_lengkap: '',
    email: '',
    nomor_telepon: '',
    posyandu_id: props.posyandus.length > 0 ? props.posyandus[0].id : '',
    nik_ibu: '',
    no_kk: '',
    nama_ayah: '',
    nik_ayah: '',
    alamat: '',
    password: '',
    password_confirmation: '',
});

const handleNumericInput = (field, maxLength) => {
    let val = form[field].replace(/[^0-9]/g, '');
    if (maxLength && val.length > maxLength) {
        val = val.slice(0, maxLength);
    }
    form[field] = val;
};

const submit = () => {
    form.post(route('register.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex flex-col justify-center py-8 sm:py-12 px-4 sm:px-6 lg:px-8 font-sans antialiased selection:bg-rose-500 selection:text-white">
        <Head title="Pendaftaran Akun Orang Tua Balita - Posyandu Digital" />

        <div class="sm:mx-auto sm:w-full sm:max-w-2xl">
            <!-- Brand / Header -->
            <div class="flex items-center justify-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-rose-600 to-rose-500 text-white flex items-center justify-center shadow-lg shadow-rose-500/25">
                    <HeartPulse class="w-7 h-7" />
                </div>
                <div class="text-left">
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight leading-tight">
                        Posyandu Digital
                    </h2>
                    <p class="text-xs font-semibold text-slate-500">
                        Sistem Pemantauan Tumbuh Kembang & Rekam Medis Balita
                    </p>
                </div>
            </div>

            <!-- Single Main Registration Card -->
            <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xl shadow-slate-200/50 p-6 sm:p-8">
                <!-- Card Header -->
                <div class="mb-6 pb-5 border-b border-slate-100">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-50 text-rose-700 text-xs font-bold border border-rose-200/70 mb-2">
                                <Baby class="w-3.5 h-3.5 text-rose-600" />
                                <span>Khusus Orang Tua / Wali Balita</span>
                            </div>
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                                Pendaftaran Akun Orang Tua
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-500 font-medium mt-1">
                                Akses KMS digital, pantau grafik pertumbuhan WHO, dan cek jadwal imunisasi balita Anda.
                            </p>
                        </div>
                    </div>

                    <!-- Auto-Sync Info Banner -->
                    <div class="mt-4 p-3.5 rounded-2xl bg-rose-50/70 border border-rose-200/80 flex items-start gap-3">
                        <Sparkles class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
                        <div class="text-xs text-rose-950 leading-relaxed">
                            <span class="font-bold">Sinkronisasi Otomatis KMS:</span>
                            Masukkan <span class="font-bold underline">NIK Ibu</span> dan <span class="font-bold underline">No KK</span> dengan benar. Jika data anak Anda telah didaftarkan oleh Kader di Posyandu, seluruh riwayat penimbangan dan imunisasinya akan <span class="font-bold text-rose-700">otomatis terhubung</span> ke akun ini!
                        </div>
                    </div>

                    <!-- Notice Khusus Kader -->
                    <div class="mt-3 p-3 rounded-2xl bg-slate-50 border border-slate-200/90 flex items-start gap-2.5">
                        <ShieldAlert class="w-4 h-4 text-slate-500 shrink-0 mt-0.5" />
                        <div class="text-[11px] text-slate-600 leading-relaxed">
                            <span class="font-bold text-slate-700">Untuk Kader Posyandu & Bidan:</span>
                            Akun petugas tidak mendaftar di halaman ini, melainkan <span class="font-semibold text-slate-800">didaftarkan langsung oleh Administrator Desa / Super Admin</span> melalui Portal Admin.
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- SECTION 1: Identitas Ibu & Kontak -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 text-xs font-black tracking-wider uppercase text-slate-400">
                            <User class="w-3.5 h-3.5 text-rose-500" />
                            <span>1. Identitas Ibu & Kontak Utama</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Nama Lengkap Ibu -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                    Nama Lengkap Ibu <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <User class="w-4 h-4" />
                                    </div>
                                    <input
                                        v-model="form.nama_lengkap"
                                        type="text"
                                        required
                                        autofocus
                                        placeholder="cth: Siti Fatimah"
                                        class="block w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 placeholder:text-slate-400 placeholder:font-normal focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all shadow-xs"
                                    />
                                </div>
                                <p v-if="form.errors.nama_lengkap" class="text-xs text-rose-600 font-semibold mt-1">
                                    {{ form.errors.nama_lengkap }}
                                </p>
                            </div>

                            <!-- Nomor WhatsApp / HP Aktif -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-slate-700">
                                        Nomor WhatsApp / HP <span class="text-rose-500">*</span>
                                    </label>
                                    <span class="text-[10px] text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                        Untuk Notifikasi
                                    </span>
                                </div>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <Phone class="w-4 h-4 text-emerald-600" />
                                    </div>
                                    <input
                                        v-model="form.nomor_telepon"
                                        type="tel"
                                        required
                                        @input="handleNumericInput('nomor_telepon', 16)"
                                        placeholder="cth: 081234567890"
                                        class="block w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 placeholder:text-slate-400 placeholder:font-normal focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all shadow-xs"
                                    />
                                </div>
                                <p v-if="form.errors.nomor_telepon" class="text-xs text-rose-600 font-semibold mt-1">
                                    {{ form.errors.nomor_telepon }}
                                </p>
                            </div>
                        </div>

                        <!-- NIK Ibu & No KK -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-slate-700">
                                        NIK KTP Ibu (16 Digit) <span class="text-rose-500">*</span>
                                    </label>
                                    <span class="text-[10px] text-slate-400 font-mono">
                                        {{ form.nik_ibu.length }}/16
                                    </span>
                                </div>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <CreditCard class="w-4 h-4" />
                                    </div>
                                    <input
                                        v-model="form.nik_ibu"
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="16"
                                        required
                                        @input="handleNumericInput('nik_ibu', 16)"
                                        placeholder="327601XXXXXXXXXX"
                                        class="block w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 placeholder:text-slate-400 placeholder:font-normal focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 font-mono tracking-wider transition-all shadow-xs"
                                    />
                                </div>
                                <p v-if="form.errors.nik_ibu" class="text-xs text-rose-600 font-semibold mt-1">
                                    {{ form.errors.nik_ibu }}
                                </p>
                            </div>

                            <!-- Nomor KK -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-slate-700">
                                        Nomor Kartu Keluarga (KK) <span class="text-rose-500">*</span>
                                    </label>
                                    <span class="text-[10px] text-slate-400 font-mono">
                                        {{ form.no_kk.length }}/16
                                    </span>
                                </div>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <Users class="w-4 h-4" />
                                    </div>
                                    <input
                                        v-model="form.no_kk"
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="16"
                                        required
                                        @input="handleNumericInput('no_kk', 16)"
                                        placeholder="327601XXXXXXXXXX"
                                        class="block w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 placeholder:text-slate-400 placeholder:font-normal focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 font-mono tracking-wider transition-all shadow-xs"
                                    />
                                </div>
                                <p v-if="form.errors.no_kk" class="text-xs text-rose-600 font-semibold mt-1">
                                    {{ form.errors.no_kk }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: Data Keluarga & Domisili -->
                    <div class="space-y-4 pt-2 border-t border-slate-100">
                        <div class="flex items-center gap-2 text-xs font-black tracking-wider uppercase text-slate-400">
                            <Home class="w-3.5 h-3.5 text-rose-500" />
                            <span>2. Data Keluarga & Wilayah Domisili</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Nama Ayah (Opsional) -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                    Nama Ayah / Suami <span class="text-slate-400 font-normal">(Opsional)</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <User class="w-4 h-4" />
                                    </div>
                                    <input
                                        v-model="form.nama_ayah"
                                        type="text"
                                        placeholder="cth: Ahmad Syafii"
                                        class="block w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 placeholder:text-slate-400 placeholder:font-normal focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all shadow-xs"
                                    />
                                </div>
                                <p v-if="form.errors.nama_ayah" class="text-xs text-rose-600 font-semibold mt-1">
                                    {{ form.errors.nama_ayah }}
                                </p>
                            </div>

                            <!-- NIK Ayah (Opsional) -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-slate-700">
                                        NIK Ayah <span class="text-slate-400 font-normal">(Opsional)</span>
                                    </label>
                                    <span v-if="form.nik_ayah" class="text-[10px] text-slate-400 font-mono">
                                        {{ form.nik_ayah.length }}/16
                                    </span>
                                </div>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <CreditCard class="w-4 h-4" />
                                    </div>
                                    <input
                                        v-model="form.nik_ayah"
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="16"
                                        @input="handleNumericInput('nik_ayah', 16)"
                                        placeholder="327601XXXXXXXXXX"
                                        class="block w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 placeholder:text-slate-400 placeholder:font-normal focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 font-mono tracking-wider transition-all shadow-xs"
                                    />
                                </div>
                                <p v-if="form.errors.nik_ayah" class="text-xs text-rose-600 font-semibold mt-1">
                                    {{ form.errors.nik_ayah }}
                                </p>
                            </div>
                        </div>

                        <!-- Pilihan Posyandu Domisili -->
                        <div v-if="posyandus.length > 0">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Posyandu Domisili Tempat Tinggal <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <Building2 class="w-4 h-4" />
                                </div>
                                <select
                                    v-model="form.posyandu_id"
                                    class="block w-full pl-10 pr-8 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all shadow-xs cursor-pointer"
                                >
                                    <option v-for="pos in posyandus" :key="pos.id" :value="pos.id">
                                        {{ pos.nama_posyandu }} - {{ pos.kelurahan_desa }}
                                    </option>
                                </select>
                            </div>
                            <p v-if="form.errors.posyandu_id" class="text-xs text-rose-600 font-semibold mt-1">
                                {{ form.errors.posyandu_id }}
                            </p>
                        </div>

                        <!-- Alamat Lengkap -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Alamat Domisili Lengkap <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <textarea
                                    v-model="form.alamat"
                                    required
                                    rows="2"
                                    placeholder="cth: Jl. Kamboja No. 12, RT 002/RW 005 Dusun Sukamaju"
                                    class="block w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 placeholder:text-slate-400 placeholder:font-normal focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all shadow-xs resize-none"
                                ></textarea>
                            </div>
                            <p v-if="form.errors.alamat" class="text-xs text-rose-600 font-semibold mt-1">
                                {{ form.errors.alamat }}
                            </p>
                        </div>
                    </div>

                    <!-- SECTION 3: Akun Masuk & Keamanan Password -->
                    <div class="space-y-4 pt-2 border-t border-slate-100">
                        <div class="flex items-center gap-2 text-xs font-black tracking-wider uppercase text-slate-400">
                            <Lock class="w-3.5 h-3.5 text-rose-500" />
                            <span>3. Akses Masuk & Kata Sandi</span>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Alamat Email (Untuk Masuk Akun) <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <Mail class="w-4 h-4" />
                                </div>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    required
                                    placeholder="nama@email.com"
                                    class="block w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 placeholder:text-slate-400 placeholder:font-normal focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all shadow-xs"
                                />
                            </div>
                            <p v-if="form.errors.email" class="text-xs text-rose-600 font-semibold mt-1">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Password -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-slate-700">
                                        Kata Sandi <span class="text-rose-500">*</span>
                                    </label>
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="text-[11px] font-bold text-rose-600 hover:text-rose-700 transition cursor-pointer"
                                        tabindex="-1"
                                    >
                                        {{ showPassword ? 'Sembunyikan' : 'Lihat Sandi' }}
                                    </button>
                                </div>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <Lock class="w-4 h-4" />
                                    </div>
                                    <input
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        required
                                        autocomplete="new-password"
                                        placeholder="Minimal 8 karakter"
                                        class="block w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 placeholder:text-slate-400 placeholder:font-normal focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all shadow-xs"
                                    />
                                </div>
                                <p v-if="form.errors.password" class="text-xs text-rose-600 font-semibold mt-1">
                                    {{ form.errors.password }}
                                </p>
                            </div>

                            <!-- Konfirmasi Password -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                    Konfirmasi Kata Sandi <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <ShieldCheck class="w-4 h-4" />
                                    </div>
                                    <input
                                        v-model="form.password_confirmation"
                                        :type="showPassword ? 'text' : 'password'"
                                        required
                                        autocomplete="new-password"
                                        placeholder="Ulangi kata sandi"
                                        class="block w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 placeholder:text-slate-400 placeholder:font-normal focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all shadow-xs"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Submit Pendaftaran -->
                    <div class="pt-4">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full flex items-center justify-center gap-2 py-3.5 px-6 rounded-2xl bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-700 hover:to-rose-600 text-white font-bold text-sm shadow-lg shadow-rose-600/30 transition-all disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer"
                        >
                            <UserPlus v-if="!form.processing" class="w-4 h-4" />
                            <span v-if="form.processing">Mendaftarkan Akun...</span>
                            <span v-else>Daftar Akun Orang Tua Sekarang</span>
                        </button>
                    </div>
                </form>

                <!-- Link Kembali ke Halaman Login -->
                <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                    <p class="text-xs text-slate-500 font-medium">
                        Sudah memiliki akun terdaftar?
                        <Link
                            :href="route('login')"
                            class="font-bold text-rose-600 hover:text-rose-700 hover:underline transition ml-1 inline-flex items-center gap-1"
                        >
                            <span>Masuk ke Akun Anda</span>
                            <ArrowRight class="w-3 h-3" />
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
