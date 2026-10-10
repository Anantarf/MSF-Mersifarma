@extends('layouts.app')

@section('title', 'Call Plan')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <!-- Header Section -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--secondary);">Jadwal Kunjungan (Call Plan)</h1>
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
    <div style="background: white; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem; display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: space-between;">
        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
            <label for="filterDateInput" style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">
                Filter Tanggal:
            </label>
            <input type="date" id="filterDateInput" onchange="applyFilters()"
                   style="padding: 0.4rem 0.6rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.875rem;">
            <div style="display: flex; gap: 0.35rem;">
                <button type="button" class="btn btn-outline" style="min-height: 36px; padding: 0.25rem 0.6rem; font-size: 0.8rem;" onclick="setQuickDate('all')">Semua</button>
                <button type="button" class="btn btn-outline" style="min-height: 36px; padding: 0.25rem 0.6rem; font-size: 0.8rem;" onclick="setQuickDate('today')">Hari Ini</button>
                <button type="button" class="btn btn-outline" style="min-height: 36px; padding: 0.25rem 0.6rem; font-size: 0.8rem;" onclick="setQuickDate('tomorrow')">Besok</button>
            </div>
        </div>

        <div style="display: flex; gap: 0.75rem; align-items: center;">
            <label for="filterStatusSelect" style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">
                Status:
            </label>
            <select id="filterStatusSelect" onchange="applyFilters()"
                    style="padding: 0.4rem 0.6rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.875rem;">
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

        <div id="plansGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem;"></div>

        <div id="emptyPlans" style="display: none; text-align: center; padding: 3rem; background: white; border: 1px dashed var(--border); border-radius: 0.5rem;">
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Belum ada jadwal kunjungan untuk kriteria filter ini.
            </p>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">
                Pastikan Anda memiliki <strong>Call List yang Disetujui (Approved)</strong> dan klik tombol <strong>+ Buat Jadwal Baru</strong> di atas.
            </p>
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
        border-radius: 0.5rem;
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 0.75rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        transition: transform 0.1s ease, box-shadow 0.1s ease;
    }
    .plan-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    }
    .status-badge {
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
        text-transform: uppercase;
        display: inline-block;
    }
    .status-scheduled { background: #e0f2fe; color: #0369a1; }
    .status-completed { background: #dcfce7; color: #166534; }
    .status-cancelled { background: #fee2e2; color: #991b1b; }

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
        border-radius: 0.75rem;
        width: 100%;
        max-width: 500px;
        padding: 1.5rem;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);
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

    document.addEventListener('DOMContentLoaded', () => {
        loadPlans();
        loadApprovedCallLists();
    });

    // R-32 Keyboard Accessibility: Escape key closes modal
    document.addEventListener('keydown', (e) => {
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
        const grid = document.getElementById('plansGrid');
        const empty = document.getElementById('emptyPlans');

        if (!currentPlans.length) {
            grid.innerHTML = '';
            empty.style.display = 'block';
            return;
        }

        empty.style.display = 'none';
        grid.innerHTML = currentPlans.map(plan => {
            const isCompleted = plan.status === 'completed';
            const statusLabel = isCompleted ? 'Selesai Dikunjungi' : 'Dijadwalkan';
            const statusClass = `status-${plan.status}`;

            // Format tanggal Indonesia sederhana
            const dateObj = new Date(plan.plan_date + 'T' + plan.planned_time);
            const dateStr = dateObj.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' });
            const timeStr = plan.planned_time.substring(0, 5) + ' WIB';

            return `
                <div class="plan-card" style="${isCompleted ? 'border-left: 4px solid var(--success);' : 'border-left: 4px solid var(--primary);'}">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; gap: 0.5rem;">
                            <span style="font-size: 0.8rem; font-weight: 700; color: var(--primary);">
                                ${dateStr}
                            </span>
                            <span class="status-badge ${statusClass}">${statusLabel}</span>
                        </div>
                        <div style="font-size: 1.05rem; font-weight: 700; color: var(--secondary);">
                            ${plan.doctor_name || 'Dokter'}
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                            ${plan.doctor_specialization || 'Spesialis Umum'}
                        </div>
                        <div style="font-size: 0.775rem; color: #64748b; margin-top: 0.25rem;">
                            ${plan.doctor_address || '-'}
                        </div>
                        <div style="font-size: 0.8rem; font-weight: 600; color: #0f766e; margin-top: 0.5rem;">
                            Pukul: ${timeStr}
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 0.5rem; border-top: 1px solid var(--border); padding-top: 0.75rem;">
                        ${!isCompleted ? `
                            <a href="/call-actuals?plan_id=${plan.id}&doctor_id=${plan.doctor_id}" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.35rem 0.75rem; min-height: 40px; text-decoration: none;">
                                Mulai Kunjungan
                            </a>
                        ` : `
                            <span style="font-size: 0.75rem; color: var(--success); font-weight: 600; align-self: center;">
                                Kunjungan Selesai
                            </span>
                        `}
                    </div>
                </div>
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
                        <option value="${cl.id}" data-period="${cl.period_month}">
                            Periode ${cl.period_month} (${cl.doctor_count} Dokter)
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
                            ${doc.name} (${doc.specialization || 'Umum'})
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
