<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        default: ''
    },
    type: {
        type: String,
        default: 'generic', // 'stunting' | 'gizi' | 'bbtb' | 'kenaikan' | 'generic'
    },
    size: {
        type: String,
        default: 'md' // 'sm' | 'md' | 'lg'
    }
});

const simplifyGizi = (val) => {
    if (!val || val === '-') return '-';
    const map = {
        'Gizi Kurang': 'Kurang',
        'Gizi Baik': 'Baik',
        'Gizi Buruk': 'Buruk',
        'Gizi Lebih': 'Lebih',
        'Risiko Gizi Lebih': 'Risiko Lebih',
        'Berisiko Gizi Lebih': 'Risiko Lebih',
    };
    if (map[val]) return map[val];
    return val.replace(/Gizi\s*/i, '').trim();
};

const badgeStyle = computed(() => {
    const val = (props.status || '').trim();

    // Red zone indicators (Gizi Buruk, Gizi Kurang, Severely Stunted, Stunted, T, Buruk, Kurang)
    if ([
        'Gizi Buruk',
        'Buruk',
        'Severely Stunted',
        'Stunted',
        'Gizi Kurang',
        'Kurang',
        'T',
        'Tidak Naik / Turun / Tetap (T)'
    ].includes(val)) {
        return 'bg-red-50 text-red-700 border-red-200 ring-red-500/10';
    }

    // Warning indicators (Risiko Gizi Lebih, Berisiko Gizi Lebih, Gizi Lebih, Obesitas, Risiko Lebih, Lebih)
    if ([
        'Risiko Gizi Lebih',
        'Berisiko Gizi Lebih',
        'Risiko Lebih',
        'Gizi Lebih',
        'Lebih',
        'Obesitas',
        'Tinggi'
    ].includes(val)) {
        return 'bg-amber-50 text-amber-700 border-amber-200 ring-amber-500/10';
    }

    // Normal indicators (Normal, Gizi Baik, Baik, N)
    if ([
        'Normal',
        'Gizi Baik',
        'Baik',
        'N',
        'Naik (N)'
    ].includes(val)) {
        return 'bg-emerald-50 text-emerald-700 border-emerald-200 ring-emerald-500/10';
    }

    // Info / Neutral (BARU, Penimbangan Baru)
    if ([
        'BARU',
        'Penimbangan Baru (B)',
        'B'
    ].includes(val)) {
        return 'bg-sky-50 text-sky-700 border-sky-200 ring-sky-500/10';
    }

    return 'bg-slate-50 text-slate-700 border-slate-200 ring-slate-500/10';
});

const sizeClass = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'px-2 py-0.5 text-[11px] font-semibold';
        case 'lg':
            return 'px-3.5 py-1.5 text-sm font-bold';
        default:
            return 'px-2.5 py-1 text-xs font-bold';
    }
});

const displayText = computed(() => {
    const raw = props.status || '-';
    switch (props.type) {
        case 'gizi':
            return `BB/U: ${simplifyGizi(raw)}`;
        case 'stunting':
            return `TB/U: ${raw}`;
        case 'bbtb':
            return `BB/TB: ${simplifyGizi(raw)}`;
        case 'kenaikan': {
            if (raw.includes('(N)') || raw === 'N') return 'Kenaikan: N';
            if (raw.includes('(T)') || raw === 'T') return 'Kenaikan: T';
            if (raw.includes('(B)') || raw === 'BARU') return 'Kenaikan: B';
            return `Kenaikan: ${raw}`;
        }
        default:
            return simplifyGizi(raw);
    }
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center justify-center rounded-lg border ring-1 shadow-2xs leading-none uppercase tracking-wide select-none',
            badgeStyle,
            sizeClass
        ]"
    >
        {{ displayText }}
    </span>
</template>
