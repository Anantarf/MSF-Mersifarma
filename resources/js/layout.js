const MSF = window.MSF || {};

const MONTH_NAMES_ID = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char]));
}

async function apiFetch(url, options = {}) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    return fetch(url, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            ...(options.headers || {}),
        },
    });
}

function showToast(message, type = 'success', duration = 3500) {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    const safeType = ['success', 'danger', 'error', 'warning', 'info'].includes(type) ? type : 'info';
    toast.className = `toast toast-${safeType}`;

    let icon = 'bi-check-circle-fill';
    if (safeType === 'danger' || safeType === 'error') icon = 'bi-x-circle-fill';
    else if (safeType === 'warning') icon = 'bi-exclamation-triangle-fill';
    else if (safeType === 'info') icon = 'bi-info-circle-fill';

    toast.innerHTML = `
        <i class="bi ${icon} toast-icon"></i>
        <div class="toast-message">${escapeHtml(message)}</div>
        <button type="button" class="toast-close" aria-label="Tutup">&times;</button>
    `;

    container.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('toast-show'));

    const closeBtn = toast.querySelector('.toast-close');
    let timeoutId;

    const removeToast = () => {
        clearTimeout(timeoutId);
        toast.classList.remove('toast-show');
        toast.classList.add('toast-hide');
        setTimeout(() => toast.remove(), 250);
    };

    closeBtn.addEventListener('click', removeToast);
    timeoutId = setTimeout(removeToast, duration);
}

function renderTableSkeleton(rows = 3) {
    let html = '<div class="skeleton-wrap">';
    for (let i = 0; i < rows; i++) {
        html += `
            <div class="skeleton-row">
                <div class="skeleton-box skeleton-w-25"></div>
                <div class="skeleton-box skeleton-w-35"></div>
                <div class="skeleton-box skeleton-w-20"></div>
                <div class="skeleton-box skeleton-w-20"></div>
            </div>
        `;
    }
    html += '</div>';
    return html;
}

function formatWaktuIndonesia(timeOrDate) {
    if (!timeOrDate) return '-';

    if (timeOrDate instanceof Date) {
        const h = String(timeOrDate.getHours()).padStart(2, '0');
        const m = String(timeOrDate.getMinutes()).padStart(2, '0');
        return `${h}.${m} WIB`;
    }

    if (typeof timeOrDate === 'string') {
        const trimmed = timeOrDate.trim();
        const timeMatch = trimmed.match(/^(\d{1,2}):(\d{2})/);
        if (timeMatch) {
            const h = timeMatch[1].padStart(2, '0');
            return `${h}.${timeMatch[2]} WIB`;
        }

        const date = new Date(trimmed);
        if (!Number.isNaN(date.getTime())) {
            const h = String(date.getHours()).padStart(2, '0');
            const m = String(date.getMinutes()).padStart(2, '0');
            return `${h}.${m} WIB`;
        }
    }

    return String(timeOrDate);
}

function formatPeriodIndonesian(periodStr) {
    if (!periodStr) return '-';

    const parts = String(periodStr).split('-');
    if (parts.length < 2) return periodStr;

    const monthNum = parseInt(parts[1], 10);
    return `${MONTH_NAMES_ID[monthNum - 1] || parts[1]} ${parts[0]}`;
}

function formatTanggalIndonesia(dateStr) {
    if (!dateStr) return '-';

    const parts = String(dateStr).split('T')[0].split(' ')[0].split('-');
    if (parts.length !== 3) return dateStr;

    const monthNum = parseInt(parts[1], 10);
    const day = parseInt(parts[2], 10);
    return `${day} ${MONTH_NAMES_ID[monthNum - 1] || parts[1]} ${parts[0]}`;
}

function formatDateTimeIndonesia(dateStr) {
    if (!dateStr) return '-';

    const date = new Date(dateStr);
    if (Number.isNaN(date.getTime())) return dateStr;

    const day = date.getDate();
    const month = MONTH_NAMES_ID[date.getMonth()];
    const year = date.getFullYear();
    const h = String(date.getHours()).padStart(2, '0');
    const m = String(date.getMinutes()).padStart(2, '0');

    return `${day} ${month} ${year}, ${h}.${m} WIB`;
}

function normalizeTime24(el) {
    if (!el || !el.value) return;

    let value = el.value.trim().replace(':', '.');
    if (/^\d{1,2}$/.test(value)) {
        value = value.padStart(2, '0') + '.00';
    } else if (/^\d{3}$/.test(value)) {
        value = '0' + value[0] + '.' + value.substring(1);
    } else if (/^\d{4}$/.test(value)) {
        value = value.substring(0, 2) + '.' + value.substring(2);
    } else if (/^(\d{1,2})\.(\d{1,2})$/.test(value)) {
        const parts = value.split('.');
        value = parts[0].padStart(2, '0') + '.' + parts[1].padEnd(2, '0');
    }

    if (/^([0-1][0-9]|2[0-3])\.([0-5][0-9])$/.test(value)) {
        el.value = value;
    }
}

function autoFormatTimeInput(el) {
    if (!el) return;

    let digits = el.value.replace(/\D/g, '');
    if (digits.length > 4) digits = digits.substring(0, 4);
    el.value = digits.length >= 3 ? `${digits.substring(0, 2)}.${digits.substring(2)}` : digits;
}

Object.assign(MSF, {
    apiFetch,
    autoFormatTimeInput,
    escapeHtml,
    formatDateTimeIndonesia,
    formatPeriodIndonesian,
    formatTanggalIndonesia,
    formatWaktuIndonesia,
    normalizeTime24,
    renderTableSkeleton,
    showToast,
});

window.MSF = MSF;

// Backward-compatible aliases for existing page scripts.
window.apiFetch = apiFetch;
window.autoFormatTimeInput = autoFormatTimeInput;
window.escapeHtml = escapeHtml;
window.formatDateTimeIndonesia = formatDateTimeIndonesia;
window.formatPeriodIndonesian = formatPeriodIndonesian;
window.formatTanggalIndonesia = formatTanggalIndonesia;
window.formatWaktuIndonesia = formatWaktuIndonesia;
window.normalizeTime24 = normalizeTime24;
window.renderTableSkeleton = renderTableSkeleton;
window.showToast = showToast;

window.addEventListener('online', () => {
    showToast('Koneksi internet terhubung kembali.', 'info', 3000);
});

window.addEventListener('offline', () => {
    showToast('Koneksi internet terputus. Mode offline aktif, draf tersimpan lokal.', 'warning', 4500);
});
