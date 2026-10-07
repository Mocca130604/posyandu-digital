<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Building2,
    MapPin,
    Users,
    Search,
    Shield,
    ChevronRight,
    ArrowLeft,
    Plus,
    Edit3,
    Trash2,
    X,
    HeartPulse,
    Baby,
    Hospital
} from 'lucide-vue-next';

const props = defineProps({
    posyandus: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({ total_posyandu: 0, total_balita: 0, total_kader: 0 })
    },
    filters: {
        type: Object,
        default: () => ({ search: '' })
    }
});

const searchQuery = ref(props.filters.search || '');

// Modal States
const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const editingPosyandu = ref(null);

const createForm = useForm({
    nama_posyandu: '',
    rt: '',
    rw: '',
    kelurahan_desa: 'Sukamaju',
    kecamatan: 'Cimanggis',
    puskesmas_pembina: 'Puskesmas Pembina Cimanggis',
});

const editForm = useForm({
    nama_posyandu: '',
    rt: '',
    rw: '',
    kelurahan_desa: '',
    kecamatan: '',
    puskesmas_pembina: '',
});

const openCreateModal = () => {
    createForm.reset();
    createForm.kelurahan_desa = 'Sukamaju';
    createForm.kecamatan = 'Cimanggis';
    createForm.puskesmas_pembina = 'Puskesmas Pembina Cimanggis';
    isCreateModalOpen.value = true;
};

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
    createForm.reset();
};

const submitCreate = () => {
    createForm.post(route('admin.posyandu.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
        }
    });
};

const openEditModal = (pos) => {
    editingPosyandu.value = pos;
    editForm.nama_posyandu = pos.nama_posyandu || '';
    editForm.rt = pos.rt || '';
    editForm.rw = pos.rw || '';
    editForm.kelurahan_desa = pos.kelurahan_desa || '';
    editForm.kecamatan = pos.kecamatan || '';
    editForm.puskesmas_pembina = pos.puskesmas_pembina || '';
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    editingPosyandu.value = null;
    editForm.reset();
};

const submitEdit = () => {
    if (!editingPosyandu.value) return;
    editForm.put(route('admin.posyandu.update', editingPosyandu.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
        }
    });
};

const deletePosyandu = (pos) => {
    const balitaMsg = pos.balita_count > 0 ? ` Perhatian: Terdapat ${pos.balita_count} data balita di posyandu ini.` : '';
    if (confirm(`Apakah Anda yakin ingin menghapus data "${pos.nama_posyandu}"?${balitaMsg}`)) {
        useForm({}).delete(route('admin.posyandu.destroy', pos.id), {
            preserveScroll: true,
        });
    }
};

const filteredPosyandus = computed(() => {
    if (!searchQuery.value) return props.posyandus;
    const q = searchQuery.value.toLowerCase();
    return props.posyandus.filter(p =>
        (p.nama_posyandu && p.nama_posyandu.toLowerCase().includes(q)) ||
        (p.rw && p.rw.toLowerCase().includes(q)) ||
        (p.rt && p.rt.toLowerCase().includes(q)) ||
        (p.kelurahan_desa && p.kelurahan_desa.toLowerCase().includes(q)) ||
        (p.kecamatan && p.kecamatan.toLowerCase().includes(q)) ||
        (p.puskesmas_pembina && p.puskesmas_pembina.toLowerCase().includes(q))
    );
});
</script>

<template>
    <AppLayout title="Manajemen Wilayah Posyandu">
        <Head title="Manajemen Posyandu - Administrator & Super Admin" />

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
                                Manajemen Posyandu Wilayah Desa
                            </h1>
                            <span class="px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 text-[11px] font-black uppercase tracking-wider">
                                Master Data Wilayah
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500 font-medium">
                            Kelola unit posyandu, cakupan RT/RW, dan puskesmas pembina se-kelurahan
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('admin.pengguna.index')"
                        class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold transition shadow-xs"
                    >
                        <Users class="w-4 h-4 text-slate-500" />
                        <span>Kelola Pengguna</span>
                    </Link>

                    <button
                        type="button"
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-md shadow-rose-600/20 transition cursor-pointer"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Tambah Posyandu Baru</span>
                    </button>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold shrink-0">
                        <Building2 class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Posyandu Aktif</p>
                        <p class="text-2xl font-black text-slate-900 leading-tight">{{ stats.total_posyandu || posyandus.length }}</p>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center font-bold shrink-0">
                        <Baby class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Balita Binaan</p>
                        <p class="text-2xl font-black text-slate-900 leading-tight">{{ stats.total_balita || 0 }} Balita</p>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold shrink-0">
                        <Users class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kader Bertugas</p>
                        <p class="text-2xl font-black text-slate-900 leading-tight">{{ stats.total_kader || 0 }} Kader</p>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center gap-3">
                <Search class="w-5 h-5 text-slate-400 shrink-0" />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari nama posyandu, RT, RW, kelurahan, atau puskesmas pembina..."
                    class="w-full bg-transparent border-none text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                />
            </div>

            <!-- Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="posyandu in filteredPosyandus"
                    :key="posyandu.id"
                    class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-xs hover:border-rose-300 hover:shadow-md transition-all flex flex-col justify-between space-y-4 group"
                >
                    <div class="space-y-3">
                        <div class="flex items-start justify-between">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-rose-50 to-orange-50 border border-rose-200 text-rose-600 flex items-center justify-center font-bold shadow-2xs">
                                <Building2 class="w-6 h-6" />
                            </div>
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-black">
                                RT {{ posyandu.rt }} / RW {{ posyandu.rw }}
                            </span>
                        </div>

                        <div>
                            <h3 class="font-black text-lg text-slate-900 tracking-tight group-hover:text-rose-600 transition-colors">
                                {{ posyandu.nama_posyandu }}
                            </h3>
                            <p class="text-xs font-semibold text-slate-500 flex items-center gap-1.5 mt-1">
                                <MapPin class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                <span>Kelurahan {{ posyandu.kelurahan_desa }}, Kec. {{ posyandu.kecamatan }}</span>
                            </p>
                            <p class="text-xs text-slate-500 flex items-center gap-1.5 mt-0.5">
                                <Hospital class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                <span>{{ posyandu.puskesmas_pembina }}</span>
                            </p>
                        </div>

                        <!-- Mini Stats Row -->
                        <div class="pt-2 border-t border-slate-100 grid grid-cols-2 gap-2 text-xs">
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-center">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Balita Binaan</span>
                                <span class="text-base font-black text-slate-800">{{ posyandu.balita_count || 0 }}</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-center">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Kader Posyandu</span>
                                <span class="text-base font-black text-slate-800">{{ posyandu.kader_count || 0 }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        <Link
                            :href="route('balita.index', { posyandu_id: posyandu.id })"
                            class="inline-flex items-center gap-1 text-xs font-bold text-rose-600 hover:text-rose-700 hover:underline"
                        >
                            <span>Lihat {{ posyandu.balita_count || 0 }} Balita</span>
                            <ChevronRight class="w-3.5 h-3.5" />
                        </Link>

                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                @click="openEditModal(posyandu)"
                                title="Edit Data Posyandu"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition cursor-pointer"
                            >
                                <Edit3 class="w-3.5 h-3.5 text-slate-600" />
                                <span>Edit</span>
                            </button>

                            <button
                                type="button"
                                @click="deletePosyandu(posyandu)"
                                title="Hapus Posyandu"
                                class="w-8 h-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 flex items-center justify-center transition cursor-pointer"
                            >
                                <Trash2 class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="filteredPosyandus.length === 0"
                class="bg-white rounded-3xl p-12 border border-slate-200/80 text-center space-y-3"
            >
                <Building2 class="w-12 h-12 text-slate-300 mx-auto" />
                <h3 class="font-bold text-base text-slate-800">Tidak ada posyandu ditemukan</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    Tidak ditemukan data posyandu yang cocok dengan pencarian Anda. Silakan periksa kembali kata kunci pencarian.
                </p>
                <button
                    type="button"
                    @click="openCreateModal"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-rose-600 text-white text-xs font-bold hover:bg-rose-700 transition"
                >
                    <Plus class="w-4 h-4" />
                    Tambah Posyandu Baru
                </button>
            </div>
        </div>

        <!-- MODAL TAMBAH POSYANDU -->
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
                                Tambah Posyandu Baru
                            </h3>
                            <p class="text-xs text-slate-500 font-medium">
                                Daftarkan unit Posyandu dan wilayah binaannya
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
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nama Posyandu *</label>
                        <input
                            v-model="createForm.nama_posyandu"
                            type="text"
                            required
                            placeholder="cth: Posyandu Dahlia 04"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500"
                        />
                        <p v-if="createForm.errors.nama_posyandu" class="text-xs text-rose-600 mt-1">{{ createForm.errors.nama_posyandu }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nomor RT *</label>
                            <input
                                v-model="createForm.rt"
                                type="text"
                                required
                                placeholder="cth: 003"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500"
                            />
                            <p v-if="createForm.errors.rt" class="text-xs text-rose-600 mt-1">{{ createForm.errors.rt }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nomor RW *</label>
                            <input
                                v-model="createForm.rw"
                                type="text"
                                required
                                placeholder="cth: 005"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500"
                            />
                            <p v-if="createForm.errors.rw" class="text-xs text-rose-600 mt-1">{{ createForm.errors.rw }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Kelurahan / Desa *</label>
                            <input
                                v-model="createForm.kelurahan_desa"
                                type="text"
                                required
                                placeholder="cth: Sukamaju"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500"
                            />
                            <p v-if="createForm.errors.kelurahan_desa" class="text-xs text-rose-600 mt-1">{{ createForm.errors.kelurahan_desa }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Kecamatan *</label>
                            <input
                                v-model="createForm.kecamatan"
                                type="text"
                                required
                                placeholder="cth: Cimanggis"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500"
                            />
                            <p v-if="createForm.errors.kecamatan" class="text-xs text-rose-600 mt-1">{{ createForm.errors.kecamatan }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Puskesmas Pembina *</label>
                        <input
                            v-model="createForm.puskesmas_pembina"
                            type="text"
                            required
                            placeholder="cth: Puskesmas Pembina Cimanggis"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500"
                        />
                        <p v-if="createForm.errors.puskesmas_pembina" class="text-xs text-rose-600 mt-1">{{ createForm.errors.puskesmas_pembina }}</p>
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
                            Simpan Posyandu
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL EDIT POSYANDU -->
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
                                Edit Data Posyandu
                            </h3>
                            <p class="text-xs text-slate-500 font-medium">
                                Perbarui informasi nama, wilayah RT/RW, atau puskesmas
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
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nama Posyandu *</label>
                        <input
                            v-model="editForm.nama_posyandu"
                            type="text"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500"
                        />
                        <p v-if="editForm.errors.nama_posyandu" class="text-xs text-rose-600 mt-1">{{ editForm.errors.nama_posyandu }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nomor RT *</label>
                            <input
                                v-model="editForm.rt"
                                type="text"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500"
                            />
                            <p v-if="editForm.errors.rt" class="text-xs text-rose-600 mt-1">{{ editForm.errors.rt }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nomor RW *</label>
                            <input
                                v-model="editForm.rw"
                                type="text"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500"
                            />
                            <p v-if="editForm.errors.rw" class="text-xs text-rose-600 mt-1">{{ editForm.errors.rw }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Kelurahan / Desa *</label>
                            <input
                                v-model="editForm.kelurahan_desa"
                                type="text"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500"
                            />
                            <p v-if="editForm.errors.kelurahan_desa" class="text-xs text-rose-600 mt-1">{{ editForm.errors.kelurahan_desa }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Kecamatan *</label>
                            <input
                                v-model="editForm.kecamatan"
                                type="text"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500"
                            />
                            <p v-if="editForm.errors.kecamatan" class="text-xs text-rose-600 mt-1">{{ editForm.errors.kecamatan }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Puskesmas Pembina *</label>
                        <input
                            v-model="editForm.puskesmas_pembina"
                            type="text"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500"
                        />
                        <p v-if="editForm.errors.puskesmas_pembina" class="text-xs text-rose-600 mt-1">{{ editForm.errors.puskesmas_pembina }}</p>
                    </div>

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
    </AppLayout>
</template>
