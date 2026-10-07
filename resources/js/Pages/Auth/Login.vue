<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Activity,
    LogIn,
    ShieldCheck,
    AlertCircle,
    UserCheck,
    Sparkles,
    UserPlus
} from 'lucide-vue-next';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: null,
    },
});

const showPassword = ref(false);
const activeAccountRole = ref(null);

const form = useForm({
    login: '',
    password: '',
    remember: true,
});

const quickAccounts = [
    {
        role: 'Super Admin',
        badge: 'bg-purple-50 text-purple-700 border-purple-200',
        login: 'superadmin@desa.id',
        password: 'password123',
        name: 'Super Administrator',
    },
    {
        role: 'Admin Desa',
        badge: 'bg-indigo-50 text-indigo-700 border-indigo-200',
        login: 'admin@desa.id',
        password: 'password123',
        name: 'Bambang Sudarmono',
    },
    {
        role: 'Bidan Desa',
        badge: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        login: 'bidan@desa.id',
        password: 'password123',
        name: 'Bidan Siti Rohmah',
    },
    {
        role: 'Kader Posyandu',
        badge: 'bg-rose-50 text-rose-700 border-rose-200',
        login: 'kader1@desa.id',
        password: 'password123',
        name: 'Kader Sri Wahyuni',
    },
    {
        role: 'Orang Tua Balita',
        badge: 'bg-amber-50 text-amber-700 border-amber-200',
        login: 'ortu1@desa.id',
        password: 'password123',
        name: 'Siti Fatimah (Wali)',
    },
];

const pickAccount = (acc) => {
    form.login = acc.login;
    form.password = acc.password;
    activeAccountRole.value = acc.role;
};

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Masuk - Posyandu Digital" />

    <div class="min-h-screen bg-slate-100 flex flex-col justify-center items-center px-4 py-8 sm:py-12">
        <div class="w-full max-w-md">
            
            <!-- Header Logo & Judul -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-rose-600 text-white shadow-lg shadow-rose-600/30 mb-3">
                    <Activity class="w-7 h-7" />
                </div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                    Posyandu Digital
                </h1>
                <p class="text-xs text-slate-500 font-medium mt-1">
                    Sistem Pemantauan Tumbuh Kembang Balita Terpadu
                </p>
                <div class="inline-flex items-center gap-1.5 mt-2 px-3 py-1 rounded-full bg-rose-50 border border-rose-200 text-[11px] font-bold text-rose-700">
                    <ShieldCheck class="w-3.5 h-3.5 text-rose-600 shrink-0" />
                    <span>Permenkes RI No. 2 Tahun 2020</span>
                </div>
            </div>

            <!-- Kartu Form Utama -->
            <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/80 border border-slate-200 p-6 sm:p-8">
                
                <!-- Status Flash Message -->
                <div v-if="status" class="mb-5 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2">
                    <ShieldCheck class="w-4 h-4 text-emerald-600 shrink-0" />
                    <span>{{ status }}</span>
                </div>

                <!-- Notifikasi Error Login -->
                <div v-if="form.errors.login" class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-start gap-2">
                    <AlertCircle class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
                    <span>{{ form.errors.login }}</span>
                </div>

                <!-- Form Masuk -->
                <form @submit.prevent="submit" class="space-y-4">
                    
                    <!-- Input Email / Nomor HP -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Email atau Nomor Telepon
                        </label>
                        <input
                            v-model="form.login"
                            type="text"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="nama@desa.id atau 081234567890"
                            class="block w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 placeholder:text-slate-400 placeholder:font-normal focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all shadow-xs"
                        />
                    </div>

                    <!-- Input Kata Sandi -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-slate-700">
                                Kata Sandi (Password)
                            </label>
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="text-xs font-bold text-rose-600 hover:text-rose-700 transition cursor-pointer"
                                tabindex="-1"
                            >
                                {{ showPassword ? 'Sembunyikan' : 'Lihat Sandi' }}
                            </button>
                        </div>
                        <input
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            autocomplete="current-password"
                            placeholder="Masukkan kata sandi"
                            class="block w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 placeholder:text-slate-400 placeholder:font-normal focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all shadow-xs"
                        />
                        <p v-if="form.errors.password" class="text-xs text-rose-600 font-semibold mt-1">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Checkbox Ingat Saya -->
                    <div class="flex items-center pt-1">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="w-4 h-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500 cursor-pointer transition"
                            />
                            <span class="text-xs font-semibold text-slate-600">Tetap masuk di perangkat ini</span>
                        </label>
                    </div>

                    <!-- Tombol Masuk -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white font-bold text-sm shadow-md shadow-rose-600/30 transition-all disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer"
                        >
                            <LogIn v-if="!form.processing" class="w-4 h-4" />
                            <span v-if="form.processing">Memeriksa Kredensial...</span>
                            <span v-else>Masuk ke Aplikasi</span>
                        </button>
                    </div>

                    <!-- Pendaftaran Akun Baru Orang Tua -->
                    <div class="pt-2 text-center space-y-2">
                        <div class="relative flex py-1 items-center">
                            <div class="grow border-t border-slate-200"></div>
                            <span class="shrink mx-3 text-[11px] text-slate-400 font-bold uppercase tracking-wider">atau</span>
                            <div class="grow border-t border-slate-200"></div>
                        </div>

                        <Link
                            :href="route('register')"
                            class="w-full min-h-[46px] rounded-xl border-2 border-rose-200 hover:border-rose-500 bg-rose-50/70 hover:bg-rose-100 text-rose-700 font-extrabold text-xs sm:text-sm flex items-center justify-center gap-2 transition-all shadow-2xs group cursor-pointer"
                        >
                            <UserPlus class="w-4 h-4 text-rose-600 group-hover:scale-110 transition-transform" />
                            <span>Daftar Akun Baru (Orang Tua Balita)</span>
                        </Link>
                        <p class="text-[11px] text-slate-500 font-medium">
                            Belum punya akun? Buat akun untuk pantau grafik KMS & jadwal posyandu balita Anda.
                        </p>
                    </div>
                </form>

                <!-- Pilihan Akun Demo (1-Klik) -->
                <div class="mt-6 pt-5 border-t border-slate-200">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700">
                            <Sparkles class="w-3.5 h-3.5 text-amber-500" />
                            <span>Pilih Akun Demo (1-Klik)</span>
                        </div>
                        <span class="text-[11px] text-slate-400 font-medium">Klik untuk isi</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <button
                            v-for="acc in quickAccounts"
                            :key="acc.role"
                            type="button"
                            @click="pickAccount(acc)"
                            :class="[
                                'p-2.5 rounded-xl border text-left transition-all cursor-pointer group',
                                activeAccountRole === acc.role
                                    ? 'border-rose-500 bg-rose-50/70 ring-1 ring-rose-400'
                                    : 'border-slate-200 hover:border-rose-300 hover:bg-slate-50'
                            ]"
                        >
                            <div class="flex items-center justify-between">
                                <span :class="['text-[10px] font-bold px-1.5 py-0.5 rounded border', acc.badge]">
                                    {{ acc.role }}
                                </span>
                                <UserCheck
                                    :class="[
                                        'w-3.5 h-3.5 transition-colors',
                                        activeAccountRole === acc.role ? 'text-rose-600' : 'text-slate-300 group-hover:text-slate-500'
                                    ]"
                                />
                            </div>
                            <p class="text-xs font-bold text-slate-800 mt-1.5 truncate">
                                {{ acc.name }}
                            </p>
                            <p class="text-[10px] text-slate-500 truncate mt-0.5">
                                {{ acc.login }}
                            </p>
                        </button>
                    </div>
                </div>

            </div>

            <!-- Footer Hak Cipta -->
            <p class="mt-6 text-center text-xs text-slate-400 font-medium">
                Posyandu Digital &copy; {{ new Date().getFullYear() }} • Standar Kemenkes RI
            </p>

        </div>
    </div>
</template>
