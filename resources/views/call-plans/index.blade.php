@extends('layouts.app')

@section('title', 'Call Plan')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <!-- Header Section -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.45rem; font-weight: 700; color: var(--secondary);">Jadwal Kunjungan (Call Plan)</h1>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Perencanaan jadwal harian kunjungan dokter sasaran dari Call List yang telah disetujui.</p>
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
    <div style="background: white; border: 1px solid var(--border); border-radius: 8px; padding: 0.85rem 1rem; display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: space-between;">
        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
            <label for="filterDateInput" style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">
                Filter Tanggal:
            </label>
            <input type="date" id="filterDateInput" onchange="applyFilters()"
                   style="padding: 0.4rem 0.6rem; border: 1px solid var(--border); border-radius: 6px; font-size: 0.875rem;">
            <div style="display: flex; gap: 0.35rem;">
                <button type="button" class="btn btn-outline" style="min-height: 36px; padding: 0.25rem 0.65rem; font-size: 0.8rem;" onclick="setQuickDate('all')">Semua</button>
                <button type="button" class="btn btn-outline" style="min-height: 36px; padding: 0.25rem 0.65rem; font-size: 0.8rem;" onclick="setQuickDate('today')">Hari Ini</button>
                <button type="button" class="btn btn-outline" style="min-height: 36px; padding: 0.25rem 0.65rem; font-size: 0.8rem;" onclick="setQuickDate('tomorrow')">Besok</button>
            </div>
        </div>

        <div style="display: flex; gap: 0.75rem; align-items: center;">
            <label for="filterStatusSelect" style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">
                Status:
            </label>
            <select id="filterStatusSelect" onchange="applyFilters()"
                    style="padding: 0.4rem 0.6rem; border: 1px solid var(--border); border-radius: 6px; font-size: 0.875rem;">
                <option value="">Semua Status</option>
                <option value="scheduled">Dijadwalkan (Scheduled)</option>
                <option value="completed">Selesai Dikunjungi (Completed)</option>
            </select>
        </div>
    </div>

    <!-- Plan List Container -->
    <div id="planListContainer" style="display: flex; flex-direction: column; gap: 1rem;">
        <div id="loadingPlans" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
            Memuat jadwal kunjungan...
        </div>

        <div id="planTableWrap" class="table-responsive" style="display: none;">
            <table class="enterprise-table">
                <thead>
                    <tr>
                        <th>Jadwal Kunjungan</th>
                        <th>Dokter Sasaran</th>
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

            <!-- 2. Pilih Dokter Sasaran dari Call List Terpilih -->
            <div style="margin-bottom: 1rem;">
                <label for="doctorSelect" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">
                    Pilih Dokter Sasaran:
                </label>
                <select id="doctorSelect" required disabled
                        style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.9rem;">
                    <option value="">-- Pilih Call List terlebih dahulu --</option>
                </select>
            </div>

            <!-- 3. Tanggal Kunjungan -->
            <div style="margin-bottom: 1rem;">
                <label for="planDateInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">
                    Tanggal Rencana Kunjungan:
                </label>
                <input type="date" id="planDateInput" required
                       style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.9rem;">
                <small id="dateRangeHelp" style="color: var(--text-muted); font-size: 0.75rem;">
                    Tanggal harus berada dalam bulan periode Call List yang dipilih.
                </small>
            </div>

            <!-- 4. Jam Rencana Kunjungan -->
            <div style="margin-bottom: 1.5rem;">
                <label for="plannedTimeInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">
                    Jam Rencana Kunjungan (WIB):
                </label>
                <input type="time" id="plannedTimeInput" required value="09:00"
                       style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.9rem;">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeCreatePlanModal()">Batal</button>
                <button type="submit" id="btnSubmitPlan" class="btn btn-primary">Simpan Jadwal</button>
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

    document.addEventListener('DOMContentLoaded', () => {
        loadPlans();
        loadApprovedCallLists();
    });    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeCreatePlanModal();
        }
    });

    function showAlert(message, type = 'success') {
        const alertEl = document.getElementById('planStatusAlert');
        alertEl.className = `alert alert-${type}`;
        alertEl.innerText = message;
        alertEl.style.display = 'block';
        setTimeout(() => { alertEl.style.display = 'none'; }, 4000);
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
            const statusLabel = isCompleted ? 'Selesai Dikunjungi' : 'Dijadwalkan';
            const safeStatus = ['scheduled', 'completed', 'cancelled'].includes(plan.status) ? plan.status : 'scheduled';
            const statusClass = `status-${safeStatus}`;

            const dateObj = new Date(plan.plan_date + 'T' + plan.planned_time);
            const dateStr = dateObj.toLocaleDateString('id-ID', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
            const timeStr = plan.planned_time.substring(0, 5) + ' WIB';

            return `
                <tr>
                    <td>
                        <strong style="color: var(--secondary); font-size: 0.9rem;">${dateStr}</strong>
                        <div style="font-size: 0.775rem; color: var(--primary); font-weight: 600;">${timeStr}</div>
                    </td>
                    <td>
                        <strong style="font-size: 0.95rem; color: var(--secondary);">${escapeHtml(plan.doctor_name || 'Dokter')}</strong>
                    </td>
                    <td>
                        <div>${escapeHtml(plan.doctor_specialization || 'Spesialis Umum')}</div>
                        <small style="color: var(--text-muted); font-size: 0.75rem;">${escapeHtml(plan.doctor_address || '-')}</small>
                    </td>
                    <td>
                        <span class="status-badge ${statusClass}">${escapeHtml(statusLabel)}</span>
                    </td>
                    <td style="text-align: right;">
                        ${!isCompleted ? `
                            <a href="/call-actuals?plan_id=${plan.id}&doctor_id=${plan.doctor_id}" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.35rem 0.65rem; text-decoration: none;">
                                Mulai Kunjungan
                            </a>
                        ` : `
                            <span style="font-size: 0.8rem; color: var(--success); font-weight: 600;">
                                Selesai
                            </span>
                        `}
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
                doctorSelect.innerHTML = '<option value="">-- Pilih Dokter Sasaran --</option>' +
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

    function openCreatePlanModal() {
        const errEl = document.getElementById('createPlanModalError');
        if (errEl) {
            errEl.style.display = 'none';
            errEl.innerText = '';
        }
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
        const plannedTime = document.getElementById('plannedTimeInput').value;

        if (!callListId) {
            errEl.innerText = 'Pilih Call List yang telah disetujui (Approved).';
            errEl.style.display = 'block';
            return;
        }

        if (!doctorId) {
            errEl.innerText = 'Pilih dokter sasaran dari Call List.';
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
</script>
@endpush
@endsection
