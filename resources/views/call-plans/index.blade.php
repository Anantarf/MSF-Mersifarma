@extends('layouts.app')

@section('title', 'Call Plan')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <!-- Header Section -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; color: var(--secondary); letter-spacing: -0.02em; margin-bottom: 0.15rem;">Jadwal Kunjungan</h1>
            <p style="color: var(--text-muted); font-size: 0.8125rem;">Call Plan : Perencanaan jadwal harian kunjungan target dokter dari Call List yang telah disetujui.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" onclick="openCreatePlanModal()">
                + Buat Jadwal Baru
            </button>
        </div>
    </div>

    <!-- Alert Banner inside view -->
    <div id="planStatusAlert" style="display: none;" class="alert"></div>

    <!-- Filter Bar -->
    <div style="background: #ffffff; border: 1px solid var(--border); border-radius: 6px; padding: 0.75rem 1rem; display: flex; flex-wrap: wrap; gap: 0.85rem; align-items: center; justify-content: space-between;">
        <div style="display: flex; flex-wrap: wrap; gap: 0.65rem; align-items: center;">
            <label for="filterDateInput" style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">
                Tanggal:
            </label>
            <input type="date" id="filterDateInput" onchange="applyFilters()"
                   style="padding: 0.35rem 0.55rem; border: 1px solid var(--border-strong); border-radius: 5px; font-size: 0.825rem; min-height: 36px;">
            <div style="display: flex; gap: 0.25rem;">
                <button type="button" class="btn btn-outline btn-sm" onclick="setQuickDate('all')">Semua</button>
                <button type="button" class="btn btn-outline btn-sm" onclick="setQuickDate('today')">Hari Ini</button>
                <button type="button" class="btn btn-outline btn-sm" onclick="setQuickDate('tomorrow')">Besok</button>
            </div>
        </div>

        <div style="display: flex; gap: 0.65rem; align-items: center;">
            <label for="filterStatusSelect" style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">
                Status:
            </label>
            <select id="filterStatusSelect" onchange="applyFilters()"
                    style="padding: 0.35rem 0.55rem; border: 1px solid var(--border-strong); border-radius: 5px; font-size: 0.825rem; min-height: 36px;">
                <option value="">Semua Status</option>
                <option value="scheduled">Terjadwal</option>
                <option value="completed">Selesai</option>
            </select>
        </div>
    </div>

    <!-- Plan List Container -->
    <div id="planListContainer" style="display: flex; flex-direction: column; gap: 1rem;">
        <div id="loadingPlans" style="padding: 0.25rem 0;"></div>

        <div id="planTableWrap" class="table-responsive" style="display: none;">
            <table class="enterprise-table">
                <thead>
                    <tr>
                        <th>Jadwal Kunjungan</th>
                        <th>Target Dokter</th>
                        <th>Spesialisasi & Faskes</th>
                        <th>Status</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="planTableBody"></tbody>
            </table>
        </div>

        <div id="emptyPlans" style="display: none; text-align: center; padding: 3rem 1.5rem; background: white; border: 1px solid var(--border); border-radius: 8px;">
            <h4 style="font-size: 1rem; font-weight: 700; color: var(--secondary); margin-bottom: 0.35rem;">Belum Ada Jadwal Kunjungan</h4>
            <p style="color: var(--text-muted); font-size: 0.875rem; max-width: 420px; margin: 0 auto 1.25rem;">
                Pastikan Anda telah memiliki <strong>Call List yang Disetujui (Approved)</strong> dan klik tombol di bawah untuk menyusun jadwal harian.
            </p>
            <button type="button" class="btn btn-primary" onclick="openCreatePlanModal()">+ Buat Jadwal Kunjungan</button>
        </div>
    </div>
</div>

<!-- MODAL: Buat Call Plan Baru -->
<div id="createPlanModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card" style="max-width: 520px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1.15rem; font-weight: 700;">Buat Jadwal Kunjungan Baru</h3>
            <button type="button" class="close-btn" onclick="closeCreatePlanModal()">&times;</button>
        </div>

        <div id="createPlanModalError" style="display: none;" class="alert alert-danger"></div>

        <form id="createPlanForm" onsubmit="handleCreatePlanSubmit(event)">
            <!-- 1. Pilih Call List yang Approved -->
            <div style="margin-bottom: 1rem;">
                <label for="callListSelect" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">
                    Pilih Call List Target yang Disetujui:
                </label>
                <select id="callListSelect" required onchange="onCallListChange()"
                        style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.9rem;">
                    <option value="">-- Memuat Call List Approved... --</option>
                </select>
                <small style="color: var(--text-muted); font-size: 0.75rem;">
                    Hanya Call List berstatus Disetujui (Approved) yang dapat dijadwalkan (PDF-02).
                </small>
            </div>

            <!-- 2. Pilih Target Dokter dari Call List Terpilih -->
            <div style="margin-bottom: 1rem;">
                <label for="doctorSelect" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">
                    Pilih Target Dokter:
                </label>
                <select id="doctorSelect" required disabled onchange="onDoctorChange()"
                        style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.9rem;">
                    <option value="">-- Pilih Call List terlebih dahulu --</option>
                </select>
            </div>

            <!-- 3. Tanggal Kunjungan -->
            <div style="margin-bottom: 1rem;">
                <label for="planDateInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">
                    Tanggal Rencana Kunjungan:
                </label>
                <input type="date" id="planDateInput" required onchange="onPlanDateChange()"
                       style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.9rem;">
                <small id="dateRangeHelp" style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 0.25rem;">
                    Tanggal harus berada dalam bulan periode Call List yang dipilih.
                </small>
                <!-- Live Availability Feedback Box (P1 Feature) -->
                <div id="availabilityNotice" style="display: none; margin-top: 0.5rem; font-size: 0.8rem; padding: 0.6rem 0.85rem; border-radius: 0.375rem; line-height: 1.4;"></div>
            </div>

            <!-- 4. Jam Rencana Kunjungan -->
            <div style="margin-bottom: 1.5rem;">
                <label for="plannedTimeInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">
                    Jam Rencana Kunjungan
                </label>
                <input type="text" id="plannedTimeInput" required value="09.00" maxlength="5" inputmode="numeric" placeholder="09.00"
                       style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.9rem; font-variant-numeric: tabular-nums;"
                       oninput="window.autoFormatTimeInput(this)" onblur="window.normalizeTime24(this)">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeCreatePlanModal()">Batal</button>
                <button type="submit" id="btnSubmitPlan" class="btn btn-primary">Simpan Jadwal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Batalkan Rencana Kunjungan (P1 Feature) -->
<div id="cancelPlanModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--danger); margin: 0;">Batalkan Rencana Kunjungan</h3>
            <button type="button" class="close-btn" onclick="closeCancelPlanModal()">&times;</button>
        </div>

        <div id="cancelPlanModalError" style="display: none;" class="alert alert-danger"></div>

        <p style="font-size: 0.875rem; color: var(--secondary); margin-bottom: 1rem; line-height: 1.5;">
            Apakah Anda yakin ingin membatalkan rencana kunjungan ke <strong id="cancelPlanDoctorName">-</strong> pada tanggal <strong id="cancelPlanDate">-</strong>?
        </p>

        <form id="cancelPlanForm" onsubmit="handleCancelPlanSubmit(event)">
            <input type="hidden" id="cancelPlanId">
            <div style="margin-bottom: 1.25rem;">
                <label for="cancelPlanReasonInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--secondary);">
                    Alasan Pembatalan (Wajib diisi):
                </label>
                <textarea id="cancelPlanReasonInput" required minlength="3" rows="3"
                          style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.875rem;"
                          placeholder="Contoh: Dokter ada simposium / operasi darurat di luar kota"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeCancelPlanModal()">Kembali</button>
                <button type="submit" id="btnConfirmCancelPlan" class="btn btn-danger" style="background: var(--danger); color: white; border: none; font-weight: 600;">
                    Konfirmasi Pembatalan
                </button>
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
    .plan-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: 0.75rem;
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 0.85rem;
        box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.04), 0 1px 2px -1px rgba(15, 23, 42, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.9);
        transition: transform 0.2s var(--ease-spring), box-shadow 0.2s var(--ease-spring), border-color 0.2s ease;
    }
    .plan-card:hover {
        transform: translateY(-2px);
        border-color: #cbd5e1;
        box-shadow: 0 8px 16px -4px rgba(15, 23, 42, 0.08), 0 4px 6px -2px rgba(15, 23, 42, 0.03);
    }
    .status-badge {
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-variant-numeric: tabular-nums;
        border: 1px solid transparent;
    }
    .status-scheduled { background: #e0f2fe; color: #0369a1; border-color: #bae6fd; }
    .status-completed { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
    .status-cancelled { background: #fef2f2; color: #991b1b; border-color: #fecaca; }

    .modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 50;
        padding: 1rem;
    }
    .modal-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: 8px;
        width: 100%;
        max-width: 500px;
        padding: 1.5rem;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.2);
    }
    .close-btn {
        background: transparent;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--text-muted);
        line-height: 1;
    }
</style>
@endpush

@push('scripts')
<script>
    let approvedCallLists = [];
    let currentCallListDoctors = [];
    let currentPlans = [];

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
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
                const m = timeMatch[2];
                return `${h}.${m} WIB`;
            }
            const d = new Date(trimmed);
            if (!isNaN(d.getTime())) {
                const h = String(d.getHours()).padStart(2, '0');
                const m = String(d.getMinutes()).padStart(2, '0');
                return `${h}.${m} WIB`;
            }
        }
        return String(timeOrDate);
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadPlans();
        loadApprovedCallLists();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeCreatePlanModal();
            closeCancelPlanModal();
        }
    });

    function showAlert(message, type = 'success') {
        window.showToast(message, type);
    }

    function applyFilters() {
        loadPlans();
    }

    function setQuickDate(type) {
        const input = document.getElementById('filterDateInput');
        if (type === 'all') {
            input.value = '';
        } else if (type === 'today') {
            const today = new Date();
            input.value = today.toISOString().split('T')[0];
        } else if (type === 'tomorrow') {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            input.value = tomorrow.toISOString().split('T')[0];
        }
        applyFilters();
    }

    async function loadPlans() {
        const loading = document.getElementById('loadingPlans');
        loading.innerHTML = window.renderTableSkeleton(3);
        loading.style.display = 'block';

        const filterDate = document.getElementById('filterDateInput').value;
        const filterStatus = document.getElementById('filterStatusSelect').value;

        let query = [];
        if (filterDate) query.push(`date=${encodeURIComponent(filterDate)}`);
        if (filterStatus) query.push(`status=${encodeURIComponent(filterStatus)}`);
        const queryString = query.length ? `?${query.join('&')}` : '';

        try {
            const res = await window.apiFetch(`/api/call-plans${queryString}`);
            const result = await res.json();
            loading.style.display = 'none';

            if (result.success) {
                currentPlans = result.data;
                renderPlansGrid();
            }
        } catch (err) {
            loading.style.display = 'none';
            console.error('Gagal memuat jadwal', err);
        }
    }

    function renderPlansGrid() {
        const tableWrap = document.getElementById('planTableWrap');
        const tbody = document.getElementById('planTableBody');
        const empty = document.getElementById('emptyPlans');

        if (!currentPlans.length) {
            if (tbody) tbody.innerHTML = '';
            if (tableWrap) tableWrap.style.display = 'none';
            empty.style.display = 'block';
            return;
        }

        empty.style.display = 'none';
        if (tableWrap) tableWrap.style.display = 'block';
        tbody.innerHTML = currentPlans.map(plan => {
            const isCompleted = plan.status === 'completed';
            const isCancelled = plan.status === 'cancelled';
            let statusLabel = 'Terjadwal';
            if (isCompleted) statusLabel = 'Selesai';
            if (isCancelled) statusLabel = 'Dibatalkan';

            const safeStatus = ['scheduled', 'completed', 'cancelled'].includes(plan.status) ? plan.status : 'scheduled';
            const statusClass = `status-${safeStatus}`;

            const dateObj = new Date(plan.plan_date + 'T' + plan.planned_time);
            const dateStr = dateObj.toLocaleDateString('id-ID', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
            const timeStr = formatWaktuIndonesia(plan.planned_time);

            return `
                <tr>
                    <td style="white-space: nowrap;">
                        <strong style="color: var(--secondary); font-size: 0.9rem;">${dateStr}</strong>
                        <div style="font-size: 0.775rem; color: var(--primary); font-weight: 600;">${timeStr}</div>
                    </td>
                    <td style="white-space: nowrap;">
                        <strong style="font-size: 0.95rem; color: var(--secondary);">${escapeHtml(plan.doctor_name || 'Dokter')}</strong>
                    </td>
                    <td>
                        <div style="font-weight: 500;">${escapeHtml(plan.doctor_specialization || 'Spesialis Umum')}</div>
                        <small style="color: var(--text-muted); font-size: 0.75rem;">${escapeHtml(plan.doctor_address || '-')}</small>
                    </td>
                    <td>
                        <span class="status-badge ${statusClass}">${escapeHtml(statusLabel)}</span>
                        ${isCancelled && plan.cancellation_reason ? `
                            <div style="font-size: 0.775rem; color: var(--danger); background: #fff1f2; border: 1px solid #fecdd3; border-radius: 4px; padding: 0.35rem 0.55rem; margin-top: 0.35rem; line-height: 1.35;">
                                <strong>Alasan Batal:</strong> "${escapeHtml(plan.cancellation_reason)}"
                            </div>
                        ` : ''}
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        ${plan.status === 'scheduled' ? `
                            <div style="display: inline-flex; gap: 0.35rem; justify-content: flex-end; align-items: center;">
                                <a href="/call-actuals?plan_id=${plan.id}&doctor_id=${plan.doctor_id}" class="btn btn-primary btn-sm" style="text-decoration: none;">
                                    Isi Laporan
                                </a>
                                <button type="button" class="btn btn-outline btn-sm" style="color: var(--danger); border-color: #fecdd3;" onclick="openCancelPlanModal(${plan.id}, '${escapeHtml(plan.doctor_name)}', '${escapeHtml(plan.plan_date)}')">
                                    Batalkan
                                </button>
                            </div>
                        ` : (isCompleted ? `
                            <span class="status-badge status-completed">
                                Selesai
                            </span>
                        ` : `
                            <span class="status-badge status-cancelled">
                                Dibatalkan
                            </span>
                        `)}
                    </td>
                </tr>
            `;
        }).join('');
    }

    function applyFilters() {
        loadPlans();
    }

    function setQuickDate(type) {
        const input = document.getElementById('filterDateInput');
        if (type === 'all') {
            input.value = '';
        } else if (type === 'today') {
            const today = new Date();
            input.value = today.toISOString().split('T')[0];
        } else if (type === 'tomorrow') {
            const tom = new Date();
            tom.setDate(tom.getDate() + 1);
            input.value = tom.toISOString().split('T')[0];
        }
        applyFilters();
    }

    async function loadApprovedCallLists() {
        const select = document.getElementById('callListSelect');
        try {
            const res = await window.apiFetch('/api/call-lists?status=approved');
            const result = await res.json();
            if (result.success) {
                approvedCallLists = result.data;
                if (!approvedCallLists.length) {
                    select.innerHTML = '<option value="">-- Tidak ada Call List yang Disetujui (Approved) --</option>';
                    return;
                }
                select.innerHTML = '<option value="">-- Pilih Call List Approved --</option>' +
                    approvedCallLists.map(cl => `
                        <option value="${cl.id}" data-period="${escapeHtml(cl.period_month)}">
                            Periode ${escapeHtml(cl.period_month)} (${cl.doctor_count} Dokter)
                        </option>
                    `).join('');
            }
        } catch (err) {
            select.innerHTML = '<option value="">Gagal memuat Call List</option>';
        }
    }

    async function onCallListChange() {
        const select = document.getElementById('callListSelect');
        const doctorSelect = document.getElementById('doctorSelect');
        const planDateInput = document.getElementById('planDateInput');
        const dateHelp = document.getElementById('dateRangeHelp');

        const selectedOption = select.options[select.selectedIndex];
        const callListId = select.value;
        const periodMonth = selectedOption.getAttribute('data-period'); // Format YYYY-MM

        if (!callListId) {
            doctorSelect.innerHTML = '<option value="">-- Pilih Call List terlebih dahulu --</option>';
            doctorSelect.disabled = true;
            planDateInput.removeAttribute('min');
            planDateInput.removeAttribute('max');
            dateHelp.innerText = 'Tanggal harus berada dalam bulan periode Call List yang dipilih.';
            return;
        }

        // Set batasan tanggal min & max sesuai bulan Call List
        if (periodMonth) {
            const [yyyy, mm] = periodMonth.split('-');
            const lastDay = new Date(yyyy, mm, 0).getDate();
            const minDate = `${periodMonth}-01`;
            const maxDate = `${periodMonth}-${String(lastDay).padStart(2, '0')}`;
            planDateInput.min = minDate;
            planDateInput.max = maxDate;
            planDateInput.value = minDate;
            dateHelp.innerText = `Pilih tanggal antara ${minDate} s.d. ${maxDate} (Periode ${periodMonth}).`;
        }

        doctorSelect.disabled = true;
        doctorSelect.innerHTML = '<option value="">Memuat dokter anggota Call List...</option>';

        try {
            const res = await window.apiFetch(`/api/call-lists/${callListId}`);
            const result = await res.json();
            if (result.success) {
                currentCallListDoctors = result.data.doctors;
                if (!currentCallListDoctors.length) {
                    doctorSelect.innerHTML = '<option value="">Tidak ada dokter di Call List ini</option>';
                    return;
                }
                doctorSelect.innerHTML = '<option value="">-- Pilih Target Dokter --</option>' +
                    currentCallListDoctors.map(doc => `
                        <option value="${doc.id}">
                            ${escapeHtml(doc.name)} (${escapeHtml(doc.specialization || 'Umum')})
                        </option>
                    `).join('');
                doctorSelect.disabled = false;
            }
        } catch (err) {
            doctorSelect.innerHTML = '<option value="">Gagal memuat dokter</option>';
        }
    }

    let currentDoctorBusyDates = [];

    async function onDoctorChange() {
        const doctorSelect = document.getElementById('doctorSelect');
        const doctorId = doctorSelect.value;
        const callListSelect = document.getElementById('callListSelect');
        const selectedOption = callListSelect.options[callListSelect.selectedIndex];
        const periodMonth = selectedOption ? selectedOption.getAttribute('data-period') : '';
        const noticeEl = document.getElementById('availabilityNotice');

        currentDoctorBusyDates = [];
        if (!doctorId || !periodMonth) {
            noticeEl.style.display = 'none';
            return;
        }

        noticeEl.style.display = 'block';
        noticeEl.style.background = '#f1f5f9';
        noticeEl.style.borderColor = '#cbd5e1';
        noticeEl.style.color = 'var(--text-muted)';
        noticeEl.innerHTML = 'Memeriksa ketersediaan jadwal dokter...';

        try {
            const res = await window.apiFetch(`/api/doctors/${doctorId}/availability?month=${periodMonth}`);
            const result = await res.json();
            if (result.success && result.data) {
                currentDoctorBusyDates = result.data.busy_dates || [];
                renderAvailabilityNotice();
                onPlanDateChange();
            } else {
                noticeEl.style.display = 'none';
            }
        } catch (err) {
            noticeEl.style.display = 'none';
        }
    }

    function renderAvailabilityNotice() {
        const noticeEl = document.getElementById('availabilityNotice');
        if (!currentDoctorBusyDates.length) {
            noticeEl.style.display = 'block';
            noticeEl.style.background = '#f0fdf4';
            noticeEl.style.borderColor = '#bbf7d0';
            noticeEl.style.color = '#166534';
            noticeEl.innerHTML = '✓ Seluruh tanggal pada bulan ini masih tersedia untuk target dokter ini.';
            return;
        }

        const formattedDays = currentDoctorBusyDates.map(d => parseInt(d.split('-')[2])).join(', ');
        noticeEl.style.display = 'block';
        noticeEl.style.background = '#fffbeb';
        noticeEl.style.borderColor = '#fde68a';
        noticeEl.style.color = '#92400e';
        noticeEl.innerHTML = `⚠️ <strong>Tanggal terisi:</strong> Tanggal ${formattedDays} sudah ada jadwal/kunjungan dokter ini. Pilih tanggal lain agar tidak bentrok.`;
    }

    function onPlanDateChange() {
        const planDateInput = document.getElementById('planDateInput');
        const errEl = document.getElementById('createPlanModalError');
        const btn = document.getElementById('btnSubmitPlan');
        const selectedDate = planDateInput.value;

        if (!selectedDate || !currentDoctorBusyDates.length) {
            if (errEl.innerText.includes('sudah memiliki jadwal')) {
                errEl.style.display = 'none';
            }
            btn.disabled = false;
            return;
        }

        if (currentDoctorBusyDates.includes(selectedDate)) {
            errEl.innerText = `Dokter sudah memiliki jadwal/kunjungan pada tanggal ${selectedDate}. Silakan pilih tanggal lain.`;
            errEl.style.display = 'block';
            btn.disabled = true;
        } else {
            if (errEl.innerText.includes('sudah memiliki jadwal')) {
                errEl.style.display = 'none';
            }
            btn.disabled = false;
        }
    }

    function openCreatePlanModal() {
        const errEl = document.getElementById('createPlanModalError');
        if (errEl) {
            errEl.style.display = 'none';
            errEl.innerText = '';
        }
        const noticeEl = document.getElementById('availabilityNotice');
        if (noticeEl) {
            noticeEl.style.display = 'none';
            noticeEl.innerHTML = '';
        }
        currentDoctorBusyDates = [];
        document.getElementById('createPlanModal').style.display = 'flex';
        loadApprovedCallLists();
    }

    function closeCreatePlanModal() {
        document.getElementById('createPlanModal').style.display = 'none';
    }

    async function handleCreatePlanSubmit(e) {
        e.preventDefault();
        const errEl = document.getElementById('createPlanModalError');
        errEl.style.display = 'none';

        const callListId = document.getElementById('callListSelect').value;
        const doctorId = document.getElementById('doctorSelect').value;
        const planDate = document.getElementById('planDateInput').value;
        const plannedTime = (document.getElementById('plannedTimeInput').value || '').trim().replace('.', ':');

        if (!callListId) {
            errEl.innerText = 'Pilih Call List yang telah disetujui (Approved).';
            errEl.style.display = 'block';
            return;
        }

        if (!doctorId) {
            errEl.innerText = 'Pilih target dokter dari Call List.';
            errEl.style.display = 'block';
            return;
        }

        if (!planDate) {
            errEl.innerText = 'Tanggal rencana kunjungan wajib diisi.';
            errEl.style.display = 'block';
            return;
        }

        const btn = document.getElementById('btnSubmitPlan');
        btn.disabled = true;
        btn.innerText = 'Menyimpan...';

        try {
            const res = await window.apiFetch('/api/call-plans', {
                method: 'POST',
                body: JSON.stringify({
                    call_list_id: parseInt(callListId),
                    doctor_id: parseInt(doctorId),
                    plan_date: planDate,
                    planned_time: plannedTime
                })
            });
            const data = await res.json();
            btn.disabled = false;
            btn.innerText = 'Simpan Jadwal';

            if (data.success) {
                closeCreatePlanModal();
                showAlert('Jadwal kunjungan (Call Plan) berhasil dibuat!');
                loadPlans();
            } else {
                errEl.innerText = data.message || 'Gagal membuat jadwal kunjungan.';
                errEl.style.display = 'block';
            }
        } catch (err) {
            btn.disabled = false;
            btn.innerText = 'Simpan Jadwal';
            errEl.innerText = 'Terjadi kesalahan jaringan.';
            errEl.style.display = 'block';
        }
    }

    function openCancelPlanModal(planId, doctorName, planDate) {
        document.getElementById('cancelPlanId').value = planId;
        document.getElementById('cancelPlanDoctorName').innerText = doctorName;
        document.getElementById('cancelPlanDate').innerText = planDate;
        document.getElementById('cancelPlanReasonInput').value = '';
        const errEl = document.getElementById('cancelPlanModalError');
        errEl.style.display = 'none';
        errEl.innerText = '';
        document.getElementById('cancelPlanModal').style.display = 'flex';
    }

    function closeCancelPlanModal() {
        document.getElementById('cancelPlanModal').style.display = 'none';
    }

    async function handleCancelPlanSubmit(e) {
        e.preventDefault();
        const planId = document.getElementById('cancelPlanId').value;
        const reason = document.getElementById('cancelPlanReasonInput').value.trim();
        const errEl = document.getElementById('cancelPlanModalError');
        const btn = document.getElementById('btnConfirmCancelPlan');

        errEl.style.display = 'none';

        if (reason.length < 3) {
            errEl.innerText = 'Alasan pembatalan wajib diisi minimal 3 karakter.';
            errEl.style.display = 'block';
            return;
        }

        btn.disabled = true;
        btn.innerText = 'Membatalkan...';

        try {
            const res = await window.apiFetch(`/api/call-plans/${planId}/cancel`, {
                method: 'PATCH',
                body: JSON.stringify({ reason: reason })
            });
            const result = await res.json();
            btn.disabled = false;
            btn.innerText = 'Konfirmasi Pembatalan';

            if (result.success) {
                closeCancelPlanModal();
                showAlert('Rencana kunjungan berhasil dibatalkan.');
                loadPlans();
            } else {
                errEl.innerText = result.message || 'Gagal membatalkan rencana kunjungan.';
                errEl.style.display = 'block';
            }
        } catch (err) {
            btn.disabled = false;
            btn.innerText = 'Konfirmasi Pembatalan';
            errEl.innerText = 'Terjadi kesalahan jaringan atau server saat membatalkan.';
            errEl.style.display = 'block';
        }
    }
</script>
@endpush
@endsection
