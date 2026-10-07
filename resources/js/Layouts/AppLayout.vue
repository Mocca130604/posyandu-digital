<script setup>
import { ref, computed, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import FeedbackModal from '@/Components/FeedbackModal.vue';
import {
    Home,
    Users,
    Activity,
    LogOut,
    Menu,
    X,
    Bell,
    ChevronDown,
    HeartPulse,
    Shield,
    Baby,
    Syringe,
    AlertCircle,
    CheckCircle2,
    Info,
    AlertTriangle
} from 'lucide-vue-next';

defineProps({
    title: {
        type: String,
        default: 'Posyandu Digital'
    }
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const permissions = computed(() => page.props.auth?.permissions || {});
const flash = computed(() => page.props.flash || {});

const isMobileMenuOpen = ref(false);
const isUserMenuOpen = ref(false);

// Global CRUD Feedback Modal State
const showFeedbackModal = ref(false);
const feedbackType = ref('success');
const feedbackTitle = ref('');
const feedbackMessage = ref('');
const feedbackErrors = ref([]);

watch(
    () => page.props.flash,
    (newFlash) => {
        if (!newFlash) return;
        if (newFlash.success) {
            feedbackType.value = 'success';
            feedbackTitle.value = 'Aksi Berhasil!';
            feedbackMessage.value = newFlash.success;
            feedbackErrors.value = [];
            showFeedbackModal.value = true;
        } else if (newFlash.error) {
            feedbackType.value = 'error';
            feedbackTitle.value = 'Aksi Gagal Dilakukan';
            feedbackMessage.value = newFlash.error;
            feedbackErrors.value = [];
            showFeedbackModal.value = true;
        } else if (newFlash.warning) {
            feedbackType.value = 'warning';
            feedbackTitle.value = 'Perhatian';
            feedbackMessage.value = newFlash.warning;
            feedbackErrors.value = [];
            showFeedbackModal.value = true;
        } else if (newFlash.info) {
            feedbackType.value = 'info';
            feedbackTitle.value = 'Informasi Sistem';
            feedbackMessage.value = newFlash.info;
            feedbackErrors.value = [];
            showFeedbackModal.value = true;
        }
    },
    { immediate: true, deep: true }
);

watch(
    () => page.props.errors,
    (errors) => {
        if (errors && Object.keys(errors).length > 0) {
            feedbackType.value = 'error';
            feedbackTitle.value = 'Peringatan Validasi Data';
            feedbackMessage.value = 'Terdapat beberapa isian yang belum sesuai. Mohon periksa poin-poin berikut:';
            feedbackErrors.value = Object.values(errors).flat();
            showFeedbackModal.value = true;
        }
    },
    { deep: true }
);

const logout = () => {
    router.post(route('logout'));
};

const navItems = computed(() => {
    if (!user.value) return [];

    if (user.value.peran?.value === 'orang_tua') {
        return [
            { name: 'Beranda Anak', route: 'orang-tua.dashboard', icon: Home, active: route().current('orang-tua.dashboard') },
            { name: 'KMS & Tumbuh Kembang', route: 'balita.index', icon: Activity, active: route().current('balita.*') },
        ];
    }

    return [
        { name: 'Beranda', route: 'dashboard', icon: Home, active: route().current('dashboard') },
        { name: 'Data Balita', route: 'balita.index', icon: Users, active: route().current('balita.*') },
        { name: 'Ukur Antropometri', route: 'pengukuran.create', icon: Activity, active: route().current('pengukuran.*') },
        { name: 'Imunisasi', route: 'imunisasi.index', icon: Syringe, active: route().current('imunisasi.*') },
    ];
});
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900 flex flex-col font-sans antialiased selection:bg-rose-500 selection:text-white">
        <!-- Top Sticky Header -->
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200 shadow-sm transition-all">
            <div class="max-w-[1720px] mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <!-- Brand / Posyandu Name -->
                <div class="flex items-center gap-3">
                    <Link :href="route('dashboard')" class="flex items-center gap-2.5 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-rose-600 to-rose-500 text-white flex items-center justify-center shadow-md shadow-rose-500/20 group-hover:scale-105 transition-transform">
                            <HeartPulse class="w-6 h-6" />
                        </div>
                        <div>
                            <span class="block text-base font-bold text-slate-900 tracking-tight leading-none group-hover:text-rose-600 transition-colors">
                                Posyandu Digital
                            </span>
                            <span v-if="user?.posyandu" class="text-xs font-medium text-slate-500 leading-tight">
                                {{ user.posyandu.nama_posyandu }} &bull; {{ user.posyandu.kelurahan_desa }}
                            </span>
                            <span v-else class="text-xs font-medium text-slate-500 leading-tight">
                                Sistem Informasi Kesehatan Balita
                            </span>
                        </div>
                    </Link>
                </div>

                <!-- Desktop Navigation Links -->
                <nav class="hidden md:flex items-center gap-1">
                    <Link
                        v-for="item in navItems"
                        :key="item.name"
                        :href="route(item.route)"
                        :class="[
                            'flex items-center gap-2 px-3.5 py-2 rounded-lg text-sm font-semibold transition-all',
                            item.active
                                ? 'bg-rose-50 text-rose-600 shadow-xs'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
                        ]"
                    >
                        <component :is="item.icon" class="w-4 h-4" />
                        {{ item.name }}
                    </Link>

                    <Link
                        v-if="permissions.can_manage_users"
                        :href="route('admin.pengguna.index')"
                        :class="[
                            'flex items-center gap-2 px-3.5 py-2 rounded-lg text-sm font-semibold transition-all',
                            route().current('admin.pengguna.*') ? 'bg-purple-50 text-purple-700 font-bold' : 'text-slate-600 hover:bg-slate-100'
                        ]"
                    >
                        <Users class="w-4 h-4 text-purple-600" />
                        Kelola Pengguna
                    </Link>

                    <Link
                        v-if="permissions.can_manage_posyandu"
                        :href="route('admin.posyandu.index')"
                        :class="[
                            'flex items-center gap-2 px-3.5 py-2 rounded-lg text-sm font-semibold transition-all',
                            route().current('admin.posyandu.*') ? 'bg-rose-50 text-rose-600' : 'text-slate-600 hover:bg-slate-100'
                        ]"
                    >
                        <Shield class="w-4 h-4" />
                        Admin Wilayah
                    </Link>
                </nav>

                <!-- User Profile & Action Menu -->
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <button
                            type="button"
                            @click="isUserMenuOpen = !isUserMenuOpen"
                            class="flex items-center gap-2.5 p-1.5 rounded-full hover:bg-slate-100 transition-colors focus:outline-hidden focus:ring-2 focus:ring-rose-500"
                        >
                            <div class="w-8 h-8 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-xs ring-2 ring-white">
                                {{ user?.nama_lengkap?.charAt(0) || 'U' }}
                            </div>
                            <div class="hidden lg:block text-left pr-1">
                                <p class="text-xs font-semibold text-slate-800 leading-none truncate max-w-[120px]">
                                    {{ user?.nama_lengkap }}
                                </p>
                                <p class="text-[10px] text-rose-600 font-medium leading-tight">
                                    {{ user?.peran?.label }}
                                </p>
                            </div>
                            <ChevronDown class="w-4 h-4 text-slate-400 hidden lg:block" />
                        </button>

                        <!-- Dropdown Menu -->
                        <div
                            v-if="isUserMenuOpen"
                            @click.outside="isUserMenuOpen = false"
                            class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-slate-100 py-1.5 z-50 animate-in fade-in zoom-in-95 duration-100"
                        >
                            <div class="px-4 py-2 border-b border-slate-100">
                                <p class="text-xs text-slate-500">Masuk sebagai</p>
                                <p class="text-sm font-semibold text-slate-800 truncate">{{ user?.email || user?.nomor_telepon }}</p>
                                <span class="inline-block mt-1 px-2 py-0.5 text-[10px] font-semibold bg-rose-50 text-rose-600 rounded-md">
                                    {{ user?.peran?.label }}
                                </span>
                            </div>

                            <div v-if="permissions.can_manage_users" class="py-1 border-b border-slate-100">
                                <Link
                                    :href="route('admin.pengguna.index')"
                                    class="w-full flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 hover:bg-purple-50 hover:text-purple-700 font-bold transition"
                                >
                                    <Users class="w-4 h-4 text-purple-600" />
                                    Manajemen Pengguna
                                </Link>
                            </div>

                            <button
                                @click="logout"
                                class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors font-medium"
                            >
                                <LogOut class="w-4 h-4" />
                                Keluar Aplikasi
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Flash Message Banner -->
        <div class="max-w-[1720px] mx-auto px-4 sm:px-6 lg:px-8 w-full pt-4" v-if="flash.success || flash.error || flash.warning || flash.info">
            <div
                v-if="flash.success"
                class="mb-3 flex items-start gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 shadow-xs"
            >
                <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
                <div class="text-sm font-medium leading-relaxed">{{ flash.success }}</div>
            </div>

            <div
                v-if="flash.warning"
                class="mb-3 flex items-start gap-3 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 shadow-xs"
            >
                <AlertTriangle class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                <div class="text-sm font-medium leading-relaxed">{{ flash.warning }}</div>
            </div>

            <div
                v-if="flash.error"
                class="mb-3 flex items-start gap-3 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 shadow-xs"
            >
                <AlertCircle class="w-5 h-5 text-red-600 shrink-0 mt-0.5" />
                <div class="text-sm font-medium leading-relaxed">{{ flash.error }}</div>
            </div>

            <div
                v-if="flash.info"
                class="mb-3 flex items-start gap-3 p-4 rounded-xl bg-sky-50 border border-sky-200 text-sky-800 shadow-xs"
            >
                <Info class="w-5 h-5 text-sky-600 shrink-0 mt-0.5" />
                <div class="text-sm font-medium leading-relaxed">{{ flash.info }}</div>
            </div>
        </div>

        <!-- Main Content Area with Safe Area Bottom Padding -->
        <main class="flex-1 w-full max-w-[1720px] mx-auto px-4 sm:px-6 lg:px-8 py-5 pb-24 md:pb-10">
            <slot />
        </main>

        <!-- Mobile Bottom Navigation Bar (Optimized for Android Mid-Range Viewport & Thumb Touch) -->
        <nav
            class="md:hidden fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur border-t border-slate-200 z-50 pb-[env(safe-area-inset-bottom,0px)] shadow-[0_-4px_16px_rgba(0,0,0,0.05)]"
            aria-label="Navigasi Bawah Seluler"
        >
            <div class="grid grid-flow-col auto-cols-fr h-16 items-center px-1">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="route(item.route)"
                    :class="[
                        'flex flex-col items-center justify-center h-full min-h-[48px] px-1 text-center transition-all active:scale-95 select-none',
                        item.active
                            ? 'text-rose-600 font-bold'
                            : 'text-slate-500 hover:text-slate-800 font-medium'
                    ]"
                >
                    <div
                        :class="[
                            'w-10 h-7 rounded-full flex items-center justify-center transition-colors',
                            item.active ? 'bg-rose-100/70 text-rose-600' : 'text-slate-500'
                        ]"
                    >
                        <component :is="item.icon" class="w-5 h-5" />
                    </div>
                    <span class="text-[11px] leading-tight mt-0.5 tracking-tight truncate max-w-[80px]">
                        {{ item.name }}
                    </span>
                </Link>
            </div>
        </nav>

        <!-- Global CRUD Action Feedback Modal -->
        <FeedbackModal
            :show="showFeedbackModal"
            :type="feedbackType"
            :title="feedbackTitle"
            :message="feedbackMessage"
            :errors="feedbackErrors"
            @close="showFeedbackModal = false"
        />
    </div>
</template>
