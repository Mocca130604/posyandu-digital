<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Users,
    Mail,
    Phone,
    Shield,
    CheckCircle2,
    XCircle,
    Search,
    ArrowLeft,
    Building2,
    Plus,
    Edit3,
    Trash2,
    X,
    MessageCircle,
    Lock,
    Key,
    ShieldAlert,
    UserCheck
} from 'lucide-vue-next';

const props = defineProps({
    users: {
        type: Array,
        default: () => []
    },
    posyandus: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({})
    },
    filters: {
        type: Object,
        default: () => ({ search: '', peran: 'all', posyandu_id: 'all' })
    }
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const searchQuery = ref(props.filters.search || '');
const selectedRole = ref(props.filters.peran || 'all');
const selectedPosyandu = ref(props.filters.posyandu_id || 'all');

// Modals State
const isEditModalOpen = ref(false);
const isCreateModalOpen = ref(false);
const editingUser = ref(null);

const editForm = useForm({
    nama_lengkap: '',
    email: '',
    nomor_telepon: '',
    peran: 'kader',
    posyandu_id: '',
    is_active: true,
    password: '',
});

const createForm = useForm({
    nama_lengkap: '',
    email: '',
    nomor_telepon: '',
    peran: 'kader',
    posyandu_id: props.posyandus.length > 0 ? props.posyandus[0].id : '',
    is_active: true,
    password: '',
});

const openEditModal = (u) => {
    editingUser.value = u;
    editForm.nama_lengkap = u.nama_lengkap || '';
    editForm.email = u.email || '';
    editForm.nomor_telepon = u.nomor_telepon || '';
    editForm.peran = typeof u.peran === 'object' ? u.peran.value : u.peran;
    editForm.posyandu_id = u.posyandu_id || '';
    editForm.is_active = Boolean(u.is_active);
    editForm.password = '';
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    editingUser.value = null;
    editForm.reset();
};

const submitEdit = () => {
    if (!editingUser.value) return;
    editForm.put(route('admin.pengguna.update', editingUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
        }
    });
};

const openCreateModal = () => {
    createForm.reset();
    createForm.peran = 'kader';
    createForm.posyandu_id = props.posyandus.length > 0 ? props.posyandus[0].id : '';
    createForm.is_active = true;
    isCreateModalOpen.value = true;
};

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
    createForm.reset();
};

const submitCreate = () => {
    createForm.post(route('admin.pengguna.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
        }
    });
};

const deleteUser = (u) => {
    if (confirm(`Apakah Anda yakin ingin menghapus akun ${u.nama_lengkap}? Tindakan ini tidak dapat dibatalkan.`)) {
        useForm({}).delete(route('admin.pengguna.destroy', u.id), {
            preserveScroll: true,
        });
    }
};

const filteredUsers = computed(() => {
    return props.users.filter(u => {
        const uRole = typeof u.peran === 'object' ? u.peran.value : u.peran;

        // Role Filter
        if (selectedRole.value !== 'all' && uRole !== selectedRole.value) {
            return false;
        }

        // Posyandu Filter
        if (selectedPosyandu.value !== 'all' && String(u.posyandu_id) !== String(selectedPosyandu.value)) {
            return false;
        }

        // Search Query
        if (searchQuery.value) {
            const q = searchQuery.value.toLowerCase();
            const matchName = u.nama_lengkap && u.nama_lengkap.toLowerCase().includes(q);
            const matchEmail = u.email && u.email.toLowerCase().includes(q);
            const matchPhone = u.nomor_telepon && u.nomor_telepon.toLowerCase().includes(q);
            const matchRole = uRole && uRole.toLowerCase().includes(q);
            if (!matchName && !matchEmail && !matchPhone && !matchRole) {
                return false;
            }
        }

        return true;
    });
});

const getRoleBadge = (peran) => {
    const p = typeof peran === 'object' ? peran.value : peran;
    switch (p) {
        case 'super_admin':
            return { label: 'Super Admin', class: 'bg-purple-100 text-purple-800 border-purple-200' };
        case 'admin_desa':
            return { label: 'Admin Desa', class: 'bg-indigo-50 text-indigo-700 border-indigo-200' };
        case 'bidan':
            return { label: 'Bidan Desa', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
        case 'kader':
            return { label: 'Kader Posyandu', class: 'bg-rose-50 text-rose-700 border-rose-200' };
        case 'orang_tua':
            return { label: 'Orang Tua / Wali', class: 'bg-amber-50 text-amber-700 border-amber-200' };
        default:
            return { label: p, class: 'bg-slate-100 text-slate-700 border-slate-200' };
    }
};

const getWaUrl = (phone, name) => {
    if (!phone) return '#';
    let clean = phone.replace(/[^0-9]/g, '');
    if (clean.startsWith('0')) clean = '62' + clean.slice(1);
    const msg = encodeURIComponent(`Halo ${name}, kami menghubungi Anda dari Manajemen Posyandu Digital.`);
    return `https://wa.me/${clean}?text=${msg}`;
};
</script>

<template>
    <AppLayout title="Manajemen Pengguna & Akun">
        <Head title="Manajemen Pengguna - Super Admin & Desa" />

        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('dashboard')"
                        class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-100 transition-colors shadow-2xs"
                    >
                        <ArrowLeft class="w-5 h-5" />
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                                Manajemen Pengguna & Akun
                            </h1>
                            <span class="px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-800 text-[11px] font-black uppercase tracking-wider">
                                Super Admin Mode
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500 font-medium">
                            Kelola data akun, ubah nomor kontak Kader & Orang Tua, peran pengguna, dan akses sistem
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('admin.posyandu.index')"
                        class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold transition shadow-xs"
                    >
                        <Building2 class="w-4 h-4 text-slate-500" />
                        <span>Wilayah Posyandu</span>
                    </Link>

                    <button
                        type="button"
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-md shadow-rose-600/20 transition cursor-pointer"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Tambah Pengguna Baru</span>
                    </button>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <button
                    type="button"
                    @click="selectedRole = 'all'"
                    :class="[
                        'p-3.5 rounded-2xl border text-left transition',
                        selectedRole === 'all'
                            ? 'bg-slate-900 text-white border-slate-900 shadow-sm'
                            : 'bg-white text-slate-800 border-slate-200/80 hover:bg-slate-50'
                    ]"
                >
                    <p class="text-[11px] font-bold opacity-80 uppercase tracking-wider">Semua Akun</p>
                    <p class="text-xl font-black mt-1">{{ stats.total || users.length }}</p>
                </button>

                <button
                    type="button"
                    @click="selectedRole = 'super_admin'"
                    :class="[
                        'p-3.5 rounded-2xl border text-left transition',
                        selectedRole === 'super_admin'
                            ? 'bg-purple-700 text-white border-purple-700 shadow-sm'
                            : 'bg-white text-slate-800 border-slate-200/80 hover:bg-purple-50/50'
                    ]"
                >
                    <p class="text-[11px] font-bold text-purple-600 uppercase tracking-wider" :class="{ 'text-purple-200': selectedRole === 'super_admin' }">Super Admin</p>
                    <p class="text-xl font-black mt-1">{{ stats.super_admin || 0 }}</p>
                </button>

                <button
                    type="button"
                    @click="selectedRole = 'admin_desa'"
                    :class="[
                        'p-3.5 rounded-2xl border text-left transition',
                        selectedRole === 'admin_desa'
                            ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
                            : 'bg-white text-slate-800 border-slate-200/80 hover:bg-indigo-50/50'
                    ]"
                >
                    <p class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider" :class="{ 'text-indigo-200': selectedRole === 'admin_desa' }">Admin Desa</p>
                    <p class="text-xl font-black mt-1">{{ stats.admin_desa || 0 }}</p>
                </button>

                <button
                    type="button"
                    @click="selectedRole = 'bidan'"
                    :class="[
                        'p-3.5 rounded-2xl border text-left transition',
                        selectedRole === 'bidan'
                            ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm'
                            : 'bg-white text-slate-800 border-slate-200/80 hover:bg-emerald-50/50'
                    ]"
                >
                    <p class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider" :class="{ 'text-emerald-200': selectedRole === 'bidan' }">Bidan Desa</p>
                    <p class="text-xl font-black mt-1">{{ stats.bidan || 0 }}</p>
                </button>

                <button
                    type="button"
                    @click="selectedRole = 'kader'"
                    :class="[
                        'p-3.5 rounded-2xl border text-left transition',
                        selectedRole === 'kader'
                            ? 'bg-rose-600 text-white border-rose-600 shadow-sm'
                            : 'bg-white text-slate-800 border-slate-200/80 hover:bg-rose-50/50'
                    ]"
                >
                    <p class="text-[11px] font-bold text-rose-600 uppercase tracking-wider" :class="{ 'text-rose-200': selectedRole === 'kader' }">Kader Posyandu</p>
                    <p class="text-xl font-black mt-1">{{ stats.kader || 0 }}</p>
                </button>

                <button
                    type="button"
                    @click="selectedRole = 'orang_tua'"
                    :class="[
                        'p-3.5 rounded-2xl border text-left transition',
                        selectedRole === 'orang_tua'
                            ? 'bg-amber-600 text-white border-amber-600 shadow-sm'
                            : 'bg-white text-slate-800 border-slate-200/80 hover:bg-amber-50/50'
                    ]"
                >
                    <p class="text-[11px] font-bold text-amber-600 uppercase tracking-wider" :class="{ 'text-amber-200': selectedRole === 'orang_tua' }">Orang Tua</p>
                    <p class="text-xl font-black mt-1">{{ stats.orang_tua || 0 }}</p>
                </button>
            </div>

            <!-- Search & Posyandu Filter Bar -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex flex-col sm:flex-row items-center gap-3">
                <div class="flex items-center gap-2 flex-1 w-full">
                    <Search class="w-5 h-5 text-slate-400 shrink-0" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama pengguna, email, nomor HP/WA, atau peran..."
                        class="w-full bg-transparent border-none text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                    />
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto shrink-0 border-t sm:border-t-0 pt-2 sm:pt-0">
                    <span class="text-xs font-bold text-slate-500 whitespace-nowrap">Posyandu:</span>
                    <select
                        v-model="selectedPosyandu"
                        class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-rose-500"
                    >
                        <option value="all">Semua Wilayah</option>
                        <option v-for="pos in posyandus" :key="pos.id" :value="pos.id">
                            {{ pos.nama_posyandu }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Table List -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/80 text-xs uppercase font-bold text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3.5">Nama & Kontak Telepon</th>
                                <th class="px-5 py-3.5">Peran Akun</th>
                                <th class="px-5 py-3.5">Posyandu Penugasan</th>
                                <th class="px-5 py-3.5 text-center">Status</th>
                                <th class="px-5 py-3.5 text-right">Aksi Manajemen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="u in filteredUsers"
                                :key="u.id"
                                class="hover:bg-slate-50/60 transition"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-rose-50 text-rose-700 font-black text-sm flex items-center justify-center shrink-0 border border-rose-200">
                                            {{ u.nama_lengkap?.charAt(0) || 'U' }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 leading-tight flex items-center gap-2">
                                                <span>{{ u.nama_lengkap }}</span>
                                                <span v-if="u.id === currentUser?.id" class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">
                                                    Anda
                                                </span>
                                            </p>
                                            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-400 font-medium mt-1">
                                                <span class="flex items-center gap-1 text-slate-500">
                                                    <Mail class="w-3.5 h-3.5 text-slate-400" />
                                                    {{ u.email }}
                                                </span>
                                                <span v-if="u.nomor_telepon" class="flex items-center gap-1 font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                                    <Phone class="w-3 h-3 text-emerald-600" />
                                                    {{ u.nomor_telepon }}
                                                </span>
                                                <span v-else class="text-rose-500 text-[11px] font-semibold">
                                                    Belum ada nomor HP
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold border',
                                            getRoleBadge(u.peran).class
                                        ]"
                                    >
                                        {{ getRoleBadge(u.peran).label }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-xs font-semibold text-slate-700">
                                    {{ u.posyandu?.nama_posyandu || 'Seluruh Wilayah (Global / Bebas)' }}
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span
                                        v-if="u.is_active"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                    >
                                        <CheckCircle2 class="w-3.5 h-3.5" />
                                        Aktif
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200"
                                    >
                                        <XCircle class="w-3.5 h-3.5" />
                                        Nonaktif
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Hubungi WhatsApp -->
                                        <a
                                            v-if="u.nomor_telepon"
                                            :href="getWaUrl(u.nomor_telepon, u.nama_lengkap)"
                                            target="_blank"
                                            title="Chat WhatsApp"
                                            class="w-8 h-8 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 flex items-center justify-center transition"
                                        >
                                            <MessageCircle class="w-4 h-4" />
                                        </a>

                                        <!-- Edit Akun -->
                                        <button
                                            type="button"
                                            @click="openEditModal(u)"
                                            title="Edit Data / Ganti Nomor Telepon"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition cursor-pointer"
                                        >
                                            <Edit3 class="w-3.5 h-3.5 text-slate-600" />
                                            <span>Edit</span>
                                        </button>

                                        <!-- Hapus Akun -->
                                        <button
                                            v-if="u.id !== currentUser?.id"
                                            type="button"
                                            @click="deleteUser(u)"
                                            title="Hapus Akun Pengguna"
                                            class="w-8 h-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 flex items-center justify-center transition cursor-pointer"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div
                    v-if="filteredUsers.length === 0"
                    class="p-10 text-center space-y-2"
                >
                    <Users class="w-10 h-10 text-slate-300 mx-auto" />
                    <h3 class="font-bold text-slate-800">Tidak ada pengguna yang cocok</h3>
                    <p class="text-xs text-slate-500">Coba ubah kata kunci pencarian atau filter peran yang dipilih.</p>
                </div>
            </div>
        </div>

        <!-- MODAL EDIT PENGGUNA (Mengganti Nomor Kader, Ibu, Role, dll.) -->
        <div
            v-if="isEditModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs animate-in fade-in duration-150"
        >
            <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 space-y-5 animate-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center font-bold">
                            <Edit3 class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-black text-slate-900">
                                Edit Akun Pengguna
                            </h3>
                            <p class="text-xs text-slate-500 font-medium">
                                Perbarui nomor telepon, data profil, atau ubah peran
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="closeEditModal"
                        class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4">
                    <!-- Nama Lengkap -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap</label>
                        <input
                            v-model="editForm.nama_lengkap"
                            type="text"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500"
                        />
                        <p v-if="editForm.errors.nama_lengkap" class="text-xs text-rose-600 mt-1">{{ editForm.errors.nama_lengkap }}</p>
                    </div>

                    <!-- Nomor HP / WhatsApp (Paling Utama Diminta User) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center justify-between">
                            <span>Nomor Kontak (WhatsApp / HP)</span>
                            <span class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                Pengingat Jadwal & Sweeping
                            </span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-emerald-600">
                                <Phone class="w-4 h-4" />
                            </div>
                            <input
                                v-model="editForm.nomor_telepon"
                                type="tel"
                                required
                                placeholder="081234567890"
                                class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-rose-500"
                            />
                        </div>
                        <p v-if="editForm.errors.nomor_telepon" class="text-xs text-rose-600 mt-1">{{ editForm.errors.nomor_telepon }}</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Email</label>
                        <input
                            v-model="editForm.email"
                            type="email"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500"
                        />
                        <p v-if="editForm.errors.email" class="text-xs text-rose-600 mt-1">{{ editForm.errors.email }}</p>
                    </div>

                    <!-- Peran & Posyandu Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Peran Akun</label>
                            <select
                                v-model="editForm.peran"
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500"
                            >
                                <option value="super_admin">Super Admin</option>
                                <option value="admin_desa">Admin Desa</option>
                                <option value="bidan">Bidan Desa</option>
                                <option value="kader">Kader Posyandu</option>
                                <option value="orang_tua">Orang Tua / Wali</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Posyandu Penugasan</label>
                            <select
                                v-model="editForm.posyandu_id"
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500"
                            >
                                <option value="">Semua Wilayah (Global)</option>
                                <option v-for="pos in posyandus" :key="pos.id" :value="pos.id">
                                    {{ pos.nama_posyandu }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Status Aktif -->
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <div>
                            <p class="text-xs font-bold text-slate-800">Status Akun Aktif</p>
                            <p class="text-[11px] text-slate-500">Izinkan pengguna masuk ke aplikasi Posyandu Digital</p>
                        </div>
                        <input
                            v-model="editForm.is_active"
                            type="checkbox"
                            class="w-5 h-5 rounded text-rose-600 focus:ring-rose-500 cursor-pointer"
                        />
                    </div>

                    <!-- Ganti Password Opsional -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center justify-between">
                            <span>Ganti Kata Sandi (Opsional)</span>
                            <span class="text-[10px] text-slate-400">Kosongkan jika tidak diganti</span>
                        </label>
                        <input
                            v-model="editForm.password"
                            type="password"
                            placeholder="Ketik password baru jika ingin mereset"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 placeholder:text-slate-400 placeholder:font-normal focus:outline-none focus:ring-2 focus:ring-rose-500"
                        />
                        <p v-if="editForm.errors.password" class="text-xs text-rose-600 mt-1">{{ editForm.errors.password }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="closeEditModal"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-md shadow-rose-600/20 transition disabled:opacity-50"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL TAMBAH PENGGUNA BARU -->
        <div
            v-if="isCreateModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs animate-in fade-in duration-150"
        >
            <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 space-y-5 animate-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center font-bold">
                            <Plus class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-black text-slate-900">
                                Tambah Akun Pengguna Baru
                            </h3>
                            <p class="text-xs text-slate-500 font-medium">
                                Buat akun baru untuk Super Admin, Admin Desa, Bidan, Kader, atau Orang Tua
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="closeCreateModal"
                        class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap *</label>
                        <input
                            v-model="createForm.nama_lengkap"
                            type="text"
                            required
                            placeholder="Nama petugas atau orang tua"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500"
                        />
                        <p v-if="createForm.errors.nama_lengkap" class="text-xs text-rose-600 mt-1">{{ createForm.errors.nama_lengkap }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nomor WhatsApp / HP *</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-emerald-600">
                                <Phone class="w-4 h-4" />
                            </div>
                            <input
                                v-model="createForm.nomor_telepon"
                                type="tel"
                                required
                                placeholder="081234567890"
                                class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-rose-500"
                            />
                        </div>
                        <p v-if="createForm.errors.nomor_telepon" class="text-xs text-rose-600 mt-1">{{ createForm.errors.nomor_telepon }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Email *</label>
                        <input
                            v-model="createForm.email"
                            type="email"
                            required
                            placeholder="nama@posyandu.id"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500"
                        />
                        <p v-if="createForm.errors.email" class="text-xs text-rose-600 mt-1">{{ createForm.errors.email }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Peran Akun *</label>
                            <select
                                v-model="createForm.peran"
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500"
                            >
                                <option value="super_admin">Super Admin</option>
                                <option value="admin_desa">Admin Desa</option>
                                <option value="bidan">Bidan Desa</option>
                                <option value="kader">Kader Posyandu</option>
                                <option value="orang_tua">Orang Tua / Wali</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Posyandu Penugasan</label>
                            <select
                                v-model="createForm.posyandu_id"
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500"
                            >
                                <option value="">Semua Wilayah</option>
                                <option v-for="pos in posyandus" :key="pos.id" :value="pos.id">
                                    {{ pos.nama_posyandu }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Kata Sandi Awal *</label>
                        <input
                            v-model="createForm.password"
                            type="password"
                            required
                            placeholder="Minimal 8 karakter"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500"
                        />
                        <p v-if="createForm.errors.password" class="text-xs text-rose-600 mt-1">{{ createForm.errors.password }}</p>
                    </div>

                    <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="closeCreateModal"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-md shadow-rose-600/20 transition disabled:opacity-50"
                        >
                            Buat Akun Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
