const escapeHtml = window.MSF.escapeHtml;

export const PLAN_STATUS = {
    scheduled: ['Terjadwal', 'status-scheduled'],
    completed: ['Selesai', 'status-approved'],
    cancelled: ['Dibatalkan', 'status-draft'],
    expired: ['Tidak Terlaksana', 'status-warning'],
};

export const ACTUAL_TYPE = {
    planned: ['Sesuai Jadwal', 'status-draft'],
    unplanned: ['Di Luar Rencana', 'status-scheduled'],
    non_target: ['Non-Target', 'status-draft'],
};

export const SESSION_STATUS = {
    in_progress: ['Sedang Berlangsung', 'status-approved'],
    checked_out: ['Menunggu Laporan', 'status-warning'],
};

export function statusBadge(map, key, kindTitle) {
    const [label, cls] = map[key] || [String(key || '-'), 'status-draft'];
    return `<span class="status-badge ${cls}" title="${escapeHtml(kindTitle)}">${escapeHtml(label)}</span>`;
}