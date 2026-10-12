const MONTH_NAMES_ID = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

export function formatVisitDateIndonesian(dateStr) {
    if (!dateStr) return '-';

    const parts = String(dateStr).split('T')[0].split('-');
    if (parts.length !== 3) return dateStr;

    const monthNum = parseInt(parts[1], 10);
    const day = parseInt(parts[2], 10);
    return `${day} ${MONTH_NAMES_ID[monthNum - 1] || parts[1]} ${parts[0]}`;
}

export function localDateString(date) {
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

export function todayString() {
    return localDateString(new Date());
}