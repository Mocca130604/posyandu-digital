// Utility Format Tanggal & Jadwal Posyandu

/**
 * Format Tanggal Bahasa Indonesia: "10 Jan 2026"
 * Menghindari bug pergeseran zona waktu dengan parsing YYYY-MM-DD langsung.
 */
export function formatTanggal(dateStr) {
    if (!dateStr) return '-';
    const cleanStr = String(dateStr).split('T')[0].split(' ')[0];
    const parts = cleanStr.split('-');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    if (parts.length === 3) {
        const y = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10) - 1;
        const d = parseInt(parts[2], 10);
        if (!isNaN(y) && !isNaN(m) && !isNaN(d) && m >= 0 && m < 12) {
            return `${String(d).padStart(2, '0')} ${months[m]} ${y}`;
        }
    }

    const dt = new Date(dateStr);
    if (isNaN(dt.getTime())) return String(dateStr);
    return `${String(dt.getDate()).padStart(2, '0')} ${months[dt.getMonth()]} ${dt.getFullYear()}`;
}

/**
 * Menghitung tanggal Senin Minggu ke-2 pada bulan & tahun tertentu.
 * Kegiatan Posyandu dilaksanakan rutin pada hari Senin minggu ke-2 setiap bulannya.
 * @param {number} year - Tahun (contoh: 2026)
 * @param {number} month - Bulan 0-indexed (0 = Jan, 11 = Des)
 * @returns {Date}
 */
export function getSeninMingguKedua(year, month) {
    const firstDay = new Date(year, month, 1);
    const dayOfWeek = firstDay.getDay(); // 0: Minggu, 1: Senin, 2: Selasa, ...
    const firstMonday = (dayOfWeek <= 1) ? (1 + (1 - dayOfWeek)) : (1 + (8 - dayOfWeek));
    const secondMonday = firstMonday + 7;
    return new Date(year, month, secondMonday);
}
