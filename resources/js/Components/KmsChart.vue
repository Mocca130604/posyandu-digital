<script setup>
import { ref, computed } from 'vue';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler
} from 'chart.js';
import { Line } from 'vue-chartjs';
import { Activity, Scale, Ruler, Layers } from 'lucide-vue-next';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler
);

const props = defineProps({
    kurvaKms: {
        type: Object,
        required: true,
        default: () => ({ bbu: {}, tbu: {}, bbtb: {} })
    },
    pengukuran: {
        type: Array,
        default: () => []
    },
    namaAnak: {
        type: String,
        default: 'Balita'
    }
});

// Mode grafik aktif: 'bbu' | 'tbu' | 'bbtb'
const activeChart = ref('bbu');

// Urutkan riwayat pengukuran dari kunjungan terlama ke terbaru untuk grafik linier
const sortedPengukuran = computed(() => {
    return [...props.pengukuran].sort((a, b) => {
        return new Date(a.tanggal_kunjungan).getTime() - new Date(b.tanggal_kunjungan).getTime();
    });
});

const currentCurve = computed(() => {
    return props.kurvaKms[activeChart.value] || props.kurvaKms.bbu || {};
});

const chartData = computed(() => {
    const curve = currentCurve.value;
    const labels = curve.labels || [];

    // Petakan data pengukuran riil balita ke sumbu X yang sesuai
    const childPoints = labels.map(labelVal => {
        let match = null;

        if (activeChart.value === 'bbu' || activeChart.value === 'tbu') {
            // Sumbu X adalah usia_bulan
            match = sortedPengukuran.value.find(p => Number(p.usia_bulan) === Number(labelVal));
        } else {
            // Sumbu X adalah tinggi_badan_cm (dibulatkan ke rentang terdekat)
            match = sortedPengukuran.value.find(p => Math.abs(parseFloat(p.tinggi_badan_cm) - parseFloat(labelVal)) < 0.3);
        }

        if (!match) return null;

        if (activeChart.value === 'bbu') {
            return {
                x: labelVal,
                y: parseFloat(match.berat_badan_kg),
                tanggal: match.tanggal_kunjungan,
                kenaikan: match.kenaikan_bb,
                zscore: match.zscore_bbu,
                status: match.status_gizi
            };
        } else if (activeChart.value === 'tbu') {
            return {
                x: labelVal,
                y: parseFloat(match.tinggi_badan_cm),
                tanggal: match.tanggal_kunjungan,
                kenaikan: match.kenaikan_bb,
                zscore: match.zscore_tbu,
                status: match.status_stunting
            };
        } else {
            return {
                x: labelVal,
                y: parseFloat(match.berat_badan_kg),
                tanggal: match.tanggal_kunjungan,
                kenaikan: match.kenaikan_bb,
                zscore: match.zscore_bbtb,
                status: match.status_bbtb
            };
        }
    });

    return {
        labels: labels,
        datasets: [
            // Pita 1: Zona Merah Bawah (< -3 SD)
            {
                label: '-3 SD (Batas Gizi Buruk/Stunted Berat)',
                data: curve.sdMin3 || [],
                borderColor: 'rgba(239, 68, 68, 0.8)',
                borderWidth: 1.5,
                borderDash: [4, 4],
                pointRadius: 0,
                fill: 'origin',
                backgroundColor: 'rgba(239, 68, 68, 0.12)',
                tension: 0.25,
            },
            // Pita 2: Zona Waspada (-3 SD s.d. -2 SD)
            {
                label: '-2 SD (Batas Normal Bawah)',
                data: curve.sdMin2 || [],
                borderColor: 'rgba(249, 115, 22, 0.8)',
                borderWidth: 1.5,
                pointRadius: 0,
                fill: '-1',
                backgroundColor: 'rgba(249, 115, 22, 0.12)',
                tension: 0.25,
            },
            // Pita 3: Zona Gizi Baik / Normal (-2 SD s.d. +1 SD)
            {
                label: '+1 SD (Batas Risiko Lebih)',
                data: curve.sdPlus1 || [],
                borderColor: 'rgba(34, 197, 94, 0.8)',
                borderWidth: 1.5,
                pointRadius: 0,
                fill: '-1',
                backgroundColor: 'rgba(34, 197, 94, 0.14)',
                tension: 0.25,
            },
            // Garis Median (0 SD)
            {
                label: 'Median (Rata-rata Baku WHO)',
                data: curve.median || [],
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 2,
                pointRadius: 0,
                fill: false,
                tension: 0.25,
            },
            // Pita 4: Zona Gizi Lebih (+1 SD s.d. +3 SD)
            {
                label: '+3 SD (Batas Atas)',
                data: curve.sdPlus3 || [],
                borderColor: 'rgba(234, 179, 8, 0.8)',
                borderWidth: 1.5,
                borderDash: [4, 4],
                pointRadius: 0,
                fill: '-2',
                backgroundColor: 'rgba(234, 179, 8, 0.12)',
                tension: 0.25,
            },
            // Data Riil Pertumbuhan Balita
            {
                label: `Pertumbuhan ${props.namaAnak}`,
                data: childPoints,
                borderColor: '#e11d48', // rose-600
                backgroundColor: '#ffffff',
                borderWidth: 3.5,
                pointRadius: 5.5,
                pointHoverRadius: 8,
                pointBorderColor: '#e11d48',
                pointBorderWidth: 2.5,
                spanGaps: true,
                fill: false,
                tension: 0.15,
                zIndex: 10,
            }
        ]
    };
});

const chartOptions = computed(() => {
    let yTitle = 'Berat Badan (kg)';
    let xTitle = 'Usia Balita (Bulan)';

    if (activeChart.value === 'tbu') {
        yTitle = 'Panjang / Tinggi Badan (cm)';
    } else if (activeChart.value === 'bbtb') {
        yTitle = 'Berat Badan (kg)';
        xTitle = 'Tinggi / Panjang Badan (cm)';
    }

    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                titleFont: { size: 12, weight: 'bold' },
                bodyFont: { size: 11 },
                padding: 10,
                cornerRadius: 12,
                callbacks: {
                    title: (tooltipItems) => {
                        const item = tooltipItems[0];
                        return activeChart.value === 'bbtb'
                            ? `Tinggi Badan: ${item.label} cm`
                            : `Usia: ${item.label} Bulan`;
                    },
                    afterBody: (tooltipItems) => {
                        const childDataset = tooltipItems.find(t => t.datasetIndex === 5);
                        if (childDataset && childDataset.raw && typeof childDataset.raw === 'object') {
                            const raw = childDataset.raw;
                            return [
                                `Tanggal: ${raw.tanggal || '-'}`,
                                `Kenaikan: ${raw.kenaikan || '-'}`,
                                `Z-Score: ${raw.zscore !== undefined ? raw.zscore + ' SD' : '-'}`,
                                `Status: ${raw.status || '-'}`
                            ];
                        }
                        return [];
                    }
                }
            }
        },
        scales: {
            x: {
                title: {
                    display: true,
                    text: xTitle,
                    font: { size: 11, weight: 'bold' },
                    color: '#64748b'
                },
                grid: {
                    color: '#f1f5f9',
                },
                ticks: {
                    font: { size: 10 },
                    maxRotation: 0,
                    autoSkip: true,
                    maxTicksLimit: 12,
                }
            },
            y: {
                title: {
                    display: true,
                    text: yTitle,
                    font: { size: 11, weight: 'bold' },
                    color: '#64748b'
                },
                grid: {
                    color: '#f1f5f9',
                },
                ticks: {
                    font: { size: 10 },
                }
            }
        }
    };
});
</script>

<template>
    <div class="w-full bg-white rounded-3xl p-4 sm:p-6 border border-slate-200/80 shadow-xs space-y-4">
        <!-- Chart Header & Mode Toggles -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                    <Activity class="w-5 h-5 text-rose-600" />
                    <span>Kurva Pertumbuhan KMS Standar WHO</span>
                </h3>
                <p class="text-xs text-slate-500 font-medium">
                    Grafik baku antropometri Permenkes No. 2 Tahun 2020
                </p>
            </div>

            <!-- Segmented Control Button Tabs -->
            <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-2xl border border-slate-200 text-xs font-bold self-start sm:self-auto">
                <button
                    type="button"
                    @click="activeChart = 'bbu'"
                    :class="[
                        'px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5',
                        activeChart === 'bbu' ? 'bg-white text-rose-600 shadow-xs' : 'text-slate-600 hover:text-slate-900'
                    ]"
                >
                    <Scale class="w-3.5 h-3.5" />
                    BB / U
                </button>

                <button
                    type="button"
                    @click="activeChart = 'tbu'"
                    :class="[
                        'px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5',
                        activeChart === 'tbu' ? 'bg-white text-rose-600 shadow-xs' : 'text-slate-600 hover:text-slate-900'
                    ]"
                >
                    <Ruler class="w-3.5 h-3.5" />
                    TB / U (Stunting)
                </button>

                <button
                    type="button"
                    @click="activeChart = 'bbtb'"
                    :class="[
                        'px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5',
                        activeChart === 'bbtb' ? 'bg-white text-rose-600 shadow-xs' : 'text-slate-600 hover:text-slate-900'
                    ]"
                >
                    <Layers class="w-3.5 h-3.5" />
                    BB / TB
                </button>
            </div>
        </div>

        <!-- Color Legend Info -->
        <div class="flex flex-wrap items-center gap-3 text-[11px] font-semibold text-slate-600 pt-0.5">
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-md bg-red-400/30 border border-red-500 inline-block"></span>
                Gizi Buruk / Sangat Pendek (&lt; -3 SD)
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-md bg-orange-400/30 border border-orange-500 inline-block"></span>
                Gizi Kurang / Pendek (-3 s.d. -2 SD)
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-md bg-emerald-400/30 border border-emerald-500 inline-block"></span>
                Gizi Baik / Normal (-2 s.d. +1 SD)
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-md bg-yellow-400/30 border border-yellow-500 inline-block"></span>
                Risiko Lebih (&gt; +1 SD)
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-1.5 bg-rose-600 rounded-full inline-block"></span>
                Titik Balita
            </span>
        </div>

        <!-- Chart Container with 360px small viewport horizontal scrolling support -->
        <div class="relative w-full overflow-x-auto pb-2">
            <div class="min-w-[340px] sm:min-w-full h-80 sm:h-96">
                <Line
                    :data="chartData"
                    :options="chartOptions"
                />
            </div>
        </div>
    </div>
</template>
