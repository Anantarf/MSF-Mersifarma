@extends('layouts.app')

@section('title', 'Call List')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <!-- Header Section -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--secondary);">Target Dokter (Call List)</h1>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Kelola perencanaan target dokter bulanan dan proses persetujuan atasan.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" onclick="openCreateModal()">
                + Buat Call List Baru
            </button>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--border);">
        <button id="tabMyLists" class="tab-btn active" onclick="switchTab('myLists')">
            Target Saya (<span id="myListCount">0</span>)
        </button>
        @if(in_array(auth()->user()->role, ['dm', 'rsm', 'mm']))
        <button id="tabReview" class="tab-btn" onclick="switchTab('review')">
            Persetujuan Masuk (<span id="reviewCount">0</span>)
        </button>
        @endif
    </div>

    <!-- Alert Banner inside view -->
    <div id="statusAlert" style="display: none;" class="alert"></div>

    <!-- Container Tab 1: My Call Lists -->
    <div id="myListsContainer" style="display: flex; flex-direction: column; gap: 1rem;">
        <div id="loadingMyLists" style="text-align: center; padding: 2rem; color: var(--text-muted);">
            Memuat daftar Call List...
        </div>
        <div id="myListsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem;"></div>
        <div id="emptyMyLists" style="display: none; text-align: center; padding: 3rem; background: white; border: 1px dashed var(--border); border-radius: 0.5rem;">
            <p style="color: var(--text-muted); font-size: 0.95rem;">Belum ada Call List yang dibuat. Klik tombol <strong>+ Buat Call List Baru</strong> di atas untuk memulai.</p>
        </div>
    </div>

    <!-- Container Tab 2: Manager Review Queue -->
    @if(in_array(auth()->user()->role, ['dm', 'rsm', 'mm']))
    <div id="reviewContainer" style="display: none; flex-direction: column; gap: 1rem;">
        <div id="loadingReview" style="text-align: center; padding: 2rem; color: var(--text-muted);">
            Memuat antrean pengajuan...
        </div>
        <div id="reviewGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem;"></div>
        <div id="emptyReview" style="display: none; text-align: center; padding: 3rem; background: white; border: 1px dashed var(--border); border-radius: 0.5rem;">
            <p style="color: var(--text-muted); font-size: 0.95rem;">Tidak ada pengajuan bawahan yang sedang menunggu persetujuan Anda.</p>
        </div>
    </div>
    @endif
</div>

<!-- MODAL: Buat Call List Baru -->
<div id="createModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1.15rem; font-weight: 700;">Buat Draft Call List Baru</h3>
            <button type="button" class="close-btn" onclick="closeCreateModal()">&times;</button>
        </div>
        <div id="createModalError" style="display: none;" class="alert alert-danger"></div>
        <form id="createCallListForm" onsubmit="handleCreateSubmit(event)">
            <div style="margin-bottom: 1rem;">
                <label for="periodMonthInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">Periode Bulan (YYYY-MM)</label>
                <input type="month" id="periodMonthInput" required
                       style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem;">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">
                    Pilih Dokter Target dari MCL (Minimal 1 Dokter):
                </label>
                <div id="doctorPickerList" style="max-height: 220px; overflow-y: auto; border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.5rem; display: flex; flex-direction: column; gap: 0.5rem; background: #fafafa;">
                    <span style="font-size: 0.85rem; color: var(--text-muted);">Memuat daftar dokter...</span>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeCreateModal()">Batal</button>
                <button type="submit" id="btnSubmitCreate" class="btn btn-primary">Simpan Draft</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Detail & Approval Call List -->
<div id="detailModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card" style="max-width: 600px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 id="detailTitle" style="font-size: 1.15rem; font-weight: 700;">Detail Call List</h3>
            <button type="button" class="close-btn" onclick="closeDetailModal()">&times;</button>
        </div>

        <div id="detailContent" style="display: flex; flex-direction: column; gap: 1rem;">
            <div style="text-align: center; padding: 2rem; color: var(--text-muted);">Memuat detail...</div>
        </div>
    </div>
</div>

<!-- MODAL: Penolakan (Reject Modal) -->
<div id="rejectModal" class="modal-backdrop" style="display: none; z-index: 60;">
    <div class="modal-card" style="max-width: 440px;">
        <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--danger); margin-bottom: 0.75rem;">Tolak Pengajuan Call List</h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
            Sesuai aturan bisnis, Call List yang ditolak akan dikembalikan ke status <strong>Draft</strong> agar bawahan dapat melakukan revisi dan mengajukan ulang.
        </p>
        <div id="rejectModalError" style="display: none;" class="alert alert-danger"></div>
        <form onsubmit="handleRejectSubmit(event)">
            <input type="hidden" id="rejectCallListId">
            <div style="margin-bottom: 1rem;">
                <label for="rejectReasonInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">
                    Alasan Penolakan (Wajib Diisi):
                </label>
                <textarea id="rejectReasonInput" rows="3" required placeholder="Contoh: Jumlah dokter spesialis anak belum mencukupi target wilayah."
                          style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.9rem;"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeRejectModal()">Batal</button>
                <button type="submit" id="btnConfirmReject" class="btn" style="background: var(--danger); color: white;">Tolak & Kembalikan</button>
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
    .tab-btn {
        padding: 0.65rem 1.25rem;
        border: none;
        background: transparent;
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--text-muted);
        cursor: pointer;
        position: relative;
        bottom: -2px;
        border-bottom: 2px solid transparent;
        transition: all 0.15s ease;
    }
    .tab-btn.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }
    .call-card {
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
    .call-card:hover {
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
    .status-draft { background: #f1f5f9; color: #475569; }
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-approved { background: #dcfce7; color: #166534; }
    .status-rejected { background: #fee2e2; color: #991b1b; }

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
    let currentUserId = {{ auth()->id() }};
    let currentUserRole = "{{ auth()->user()->role }}";
    let allDoctors = [];
    let myCallLists = [];
    let reviewCallLists = [];

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
        loadDoctors();
        loadMyCallLists();
        if (['dm', 'rsm', 'mm'].includes(currentUserRole)) {
            loadReviewCallLists();
        }
        // Default month: bulan depan
        const now = new Date();
        now.setMonth(now.getMonth() + 1);
        const yyyy = now.getFullYear();
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        document.getElementById('periodMonthInput').value = `${yyyy}-${mm}`;
    });    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeCreateModal();
            closeDetailModal();
            closeRejectModal();
        }
    });

    function callListStatusClass(status) {
        if (status === 'pending_approval') {
            return 'pending';
        }

        return ['draft', 'approved', 'rejected'].includes(status) ? status : 'draft';
    }
    function showAlert(message, type = 'success') {
        const alertEl = document.getElementById('statusAlert');
        alertEl.className = `alert alert-${type}`;
        alertEl.innerText = message;
        alertEl.style.display = 'block';
        setTimeout(() => { alertEl.style.display = 'none'; }, 4000);
    }

    function switchTab(tab) {
        document.getElementById('tabMyLists').classList.remove('active');
        if (document.getElementById('tabReview')) {
            document.getElementById('tabReview').classList.remove('active');
        }

        if (tab === 'myLists') {
            document.getElementById('tabMyLists').classList.add('active');
            document.getElementById('myListsContainer').style.display = 'flex';
            if (document.getElementById('reviewContainer')) {
                document.getElementById('reviewContainer').style.display = 'none';
            }
        } else {
            document.getElementById('tabReview').classList.add('active');
            document.getElementById('myListsContainer').style.display = 'none';
            document.getElementById('reviewContainer').style.display = 'flex';
        }
    }

    async function loadDoctors() {
        try {
            const res = await window.apiFetch('/api/doctors');
            const data = await res.json();
            if (data.success) {
                allDoctors = data.data;
                renderDoctorPicker();
            }
        } catch (err) {
            console.error('Gagal mengambil daftar dokter', err);
        }
    }

    function renderDoctorPicker(selectedIds = []) {
        const container = document.getElementById('doctorPickerList');
        if (!allDoctors.length) {
            container.innerHTML = '<span style="font-size:0.85rem; color:red;">Gagal memuat dokter MCL.</span>';
            return;
        }
        container.innerHTML = allDoctors.map(doc => `
            <label style="display: flex; align-items: flex-start; gap: 0.5rem; font-size: 0.85rem; cursor: pointer; background: white; padding: 0.5rem; border: 1px solid var(--border); border-radius: 0.25rem;">
                <input type="checkbox" name="doctor_ids" value="${doc.id}" ${selectedIds.includes(doc.id) ? 'checked' : ''} style="margin-top: 0.2rem;">
                <div>
                    <strong>${escapeHtml(doc.name)}</strong> <span style="color: var(--text-muted); font-size: 0.75rem;">(${escapeHtml(doc.specialization || 'Umum')})</span>
                    <div style="font-size: 0.75rem; color: #64748b;">${escapeHtml(doc.address || '-')}</div>
                </div>
            </label>
        `).join('');
    }

    async function loadMyCallLists() {
        document.getElementById('loadingMyLists').style.display = 'block';
        try {
            const res = await window.apiFetch('/api/call-lists');
            const result = await res.json();
            document.getElementById('loadingMyLists').style.display = 'none';

            if (result.success) {
                myCallLists = result.data;
                document.getElementById('myListCount').innerText = myCallLists.length;
                renderMyListsGrid();
            }
        } catch (err) {
            document.getElementById('loadingMyLists').style.display = 'none';
            console.error(err);
        }
    }

    function renderMyListsGrid() {
        const grid = document.getElementById('myListsGrid');
        const empty = document.getElementById('emptyMyLists');
        if (!myCallLists.length) {
            grid.innerHTML = '';
            empty.style.display = 'block';
            return;
        }
        empty.style.display = 'none';
        grid.innerHTML = myCallLists.map(cl => {
            let statusClass = `status-${cl.status === 'pending_approval' ? 'pending' : cl.status}`;
            let statusLabel = cl.status === 'pending_approval' ? 'Menunggu Review' : (cl.status === 'approved' ? 'Disetujui' : 'Draft');
            if (cl.is_rejected_revision) {
                statusClass = 'status-rejected';
                statusLabel = 'Perlu Revisi (Ditolak)';
            }
            return `
                <div class="call-card" ${cl.is_rejected_revision ? 'style="border-left: 4px solid var(--danger);"' : ''}>
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; gap: 0.5rem;">
                            <span style="font-size: 0.8rem; font-weight: 700; color: var(--primary);">PERIODE: ${cl.period_month}</span>
                            <span class="status-badge ${statusClass}">${escapeHtml(statusLabel)}</span>
                        </div>
                        <div style="font-size: 1.05rem; font-weight: 700;">Target: ${cl.doctor_count} Dokter</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                            Approver: <strong>${escapeHtml(cl.assigned_approver_name || '-')}</strong>
                        </div>
                        ${cl.is_rejected_revision && cl.latest_rejection_reason ? `
                            <div style="font-size: 0.775rem; color: var(--danger); background: #fef2f2; padding: 0.35rem 0.5rem; border-radius: 0.25rem; margin-top: 0.5rem; border-left: 2px solid var(--danger);">
                                Catatan Penolakan: <em>"${escapeHtml(cl.latest_rejection_reason)}"</em>
                            </div>
                        ` : ''}
                    </div>
                    <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 0.5rem; border-top: 1px solid var(--border); padding-top: 0.75rem;">
                        <button class="btn btn-outline" style="font-size: 0.8rem; padding: 0.4rem 0.75rem; min-height: 40px;" onclick="openDetailModal(${cl.id})">Lihat Detail</button>
                        ${cl.status === 'draft' ? `
                            <button class="btn btn-primary" style="font-size: 0.8rem; padding: 0.4rem 0.75rem; min-height: 40px;" onclick="submitCallList(${cl.id})">Ajukan</button>
                        ` : ''}
                    </div>
                </div>
            `;
        }).join('');
    }

    async function loadReviewCallLists() {
        if (!document.getElementById('loadingReview')) return;
        document.getElementById('loadingReview').style.display = 'block';
        try {
            const res = await window.apiFetch('/api/call-lists?mode=review');
            const result = await res.json();
            document.getElementById('loadingReview').style.display = 'none';

            if (result.success) {
                reviewCallLists = result.data;
                document.getElementById('reviewCount').innerText = reviewCallLists.length;
                renderReviewGrid();
            }
        } catch (err) {
            document.getElementById('loadingReview').style.display = 'none';
            console.error(err);
        }
    }

    function renderReviewGrid() {
        const grid = document.getElementById('reviewGrid');
        const empty = document.getElementById('emptyReview');
        if (!reviewCallLists.length) {
            grid.innerHTML = '';
            empty.style.display = 'block';
            return;
        }
        empty.style.display = 'none';
        grid.innerHTML = reviewCallLists.map(cl => `
            <div class="call-card" style="border-left: 4px solid var(--warning);">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <span style="font-size: 0.8rem; font-weight: 700; color: var(--secondary);">${cl.period_month}</span>
                        <span class="status-badge status-pending">Perlu Review</span>
                    </div>
                    <div style="font-size: 1rem; font-weight: 700;">Pengaju: ${escapeHtml(cl.owner_name)}</div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                        Target: <strong>${cl.doctor_count} Dokter MCL</strong>
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 0.5rem; border-top: 1px solid var(--border); padding-top: 0.75rem;">
                    <button class="btn btn-outline" style="font-size: 0.8rem; padding: 0.4rem 0.75rem; min-height: 40px;" onclick="openDetailModal(${cl.id})">Detail</button>
                    <button class="btn btn-primary" style="font-size: 0.8rem; padding: 0.4rem 0.75rem; min-height: 40px; background: var(--success);" onclick="approveCallList(${cl.id})">Setujui</button>
                    <button class="btn" style="font-size: 0.8rem; padding: 0.4rem 0.75rem; min-height: 40px; background: var(--danger); color: white;" onclick="openRejectModal(${cl.id})">Tolak</button>
                </div>
            </div>
        `).join('');
    }

    function openCreateModal() {
        const errEl = document.getElementById('createModalError');
        if (errEl) {
            errEl.style.display = 'none';
            errEl.innerText = '';
        }
        document.getElementById('createModal').style.display = 'flex';
    }

    function closeCreateModal() {
        document.getElementById('createModal').style.display = 'none';
    }

    async function handleCreateSubmit(e) {
        e.preventDefault();
        const errEl = document.getElementById('createModalError');
        errEl.style.display = 'none';

        const period = document.getElementById('periodMonthInput').value;
        const selectedDoctors = Array.from(document.querySelectorAll('#doctorPickerList input[name="doctor_ids"]:checked')).map(cb => parseInt(cb.value));

        if (!selectedDoctors.length) {
            errEl.innerText = 'Pilih minimal 1 dokter target sasaran dari daftar.';
            errEl.style.display = 'block';
            return;
        }

        const btn = document.getElementById('btnSubmitCreate');
        btn.disabled = true;
        btn.innerText = 'Menyimpan...';

        try {
            const res = await window.apiFetch('/api/call-lists', {
                method: 'POST',
                body: JSON.stringify({
                    period_month: period,
                    doctor_ids: selectedDoctors
                })
            });
            const data = await res.json();
            btn.disabled = false;
            btn.innerText = 'Simpan Draft';

            if (data.success) {
                closeCreateModal();
                showAlert('Draft Call List berhasil dibuat!');
                loadMyCallLists();
            } else {
                errEl.innerText = data.message || 'Gagal membuat Call List.';
                errEl.style.display = 'block';
            }
        } catch (err) {
            btn.disabled = false;
            btn.innerText = 'Simpan Draft';
            errEl.innerText = 'Terjadi kesalahan jaringan.';
            errEl.style.display = 'block';
        }
    }

    async function submitCallList(id) {
        if (!confirm('Apakah Anda yakin ingin mengajukan Call List ini ke atasan langsung?')) return;
        try {
            const res = await window.apiFetch(`/api/call-lists/${id}/submit`, { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                showAlert('Call List berhasil diajukan untuk persetujuan.');
                loadMyCallLists();
                if (['dm', 'rsm', 'mm'].includes(currentUserRole)) loadReviewCallLists();
            } else {
                showAlert(data.message || 'Gagal mengajukan Call List.', 'danger');
            }
        } catch (err) {
            showAlert('Terjadi kesalahan koneksi jaringan.', 'danger');
        }
    }

    async function approveCallList(id) {
        if (!confirm('Setujui Call List ini?')) return;
        try {
            const res = await window.apiFetch(`/api/call-lists/${id}/approve`, {
                method: 'PATCH',
                body: JSON.stringify({ status: 'approved', reason: 'Disetujui' })
            });
            const data = await res.json();
            if (data.success) {
                showAlert('Call List berhasil disetujui!');
                loadReviewCallLists();
                loadMyCallLists();
                closeDetailModal();
            } else {
                showAlert(data.message || 'Gagal menyetujui Call List.', 'danger');
            }
        } catch (err) {
            showAlert('Terjadi kesalahan koneksi jaringan.', 'danger');
        }
    }

    function openRejectModal(id) {
        const errEl = document.getElementById('rejectModalError');
        if (errEl) {
            errEl.style.display = 'none';
            errEl.innerText = '';
        }
        document.getElementById('rejectCallListId').value = id;
        document.getElementById('rejectReasonInput').value = '';
        document.getElementById('rejectModal').style.display = 'flex';
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').style.display = 'none';
    }

    async function handleRejectSubmit(e) {
        e.preventDefault();
        const errEl = document.getElementById('rejectModalError');
        errEl.style.display = 'none';

        const id = document.getElementById('rejectCallListId').value;
        const reason = document.getElementById('rejectReasonInput').value.trim();

        if (!reason) {
            errEl.innerText = 'Alasan penolakan wajib diisi untuk panduan revisi bawahan.';
            errEl.style.display = 'block';
            return;
        }

        const btn = document.getElementById('btnConfirmReject');
        btn.disabled = true;
        btn.innerText = 'Memproses...';

        try {
            const res = await window.apiFetch(`/api/call-lists/${id}/approve`, {
                method: 'PATCH',
                body: JSON.stringify({ status: 'rejected', reason: reason })
            });
            const data = await res.json();
            btn.disabled = false;
            btn.innerText = 'Tolak & Kembalikan';

            if (data.success) {
                closeRejectModal();
                closeDetailModal();
                showAlert('Call List berhasil ditolak dan dikembalikan ke Draft.');
                loadReviewCallLists();
                loadMyCallLists();
            } else {
                errEl.innerText = data.message || 'Gagal menolak Call List.';
                errEl.style.display = 'block';
            }
        } catch (err) {
            btn.disabled = false;
            btn.innerText = 'Tolak & Kembalikan';
            errEl.innerText = 'Terjadi kesalahan jaringan.';
            errEl.style.display = 'block';
        }
    }

    async function openDetailModal(id) {
        document.getElementById('detailModal').style.display = 'flex';
        const content = document.getElementById('detailContent');
        content.innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--text-muted);">Memuat detail...</div>';

        try {
            const res = await window.apiFetch(`/api/call-lists/${id}`);
            const result = await res.json();

            if (!result.success) {
                content.innerHTML = `<div class="alert alert-danger">${escapeHtml(result.message)}</div>`;
                return;
            }

            const cl = result.data;
            const isAssignedApprover = (cl.assigned_approver_id === currentUserId && cl.status === 'pending_approval');
            const isOwnerDraft = (cl.owner_id === currentUserId && cl.status === 'draft');

            content.innerHTML = `
                <div style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem; font-size: 0.875rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Periode: <strong>${cl.period_month}</strong></span>
                        <span class="status-badge status-${cl.status === 'pending_approval' ? 'pending' : cl.status}">${escapeHtml(cl.status)}</span>
                    </div>
                    <div>Pemilik: <strong>${escapeHtml(cl.owner_name)}</strong></div>
                    <div>Approver: <strong>${escapeHtml(cl.assigned_approver_name || '-')}</strong></div>
                </div>

                <div>
                    <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.5rem;">Daftar Dokter Sasaran (${cl.doctors.length})</h4>
                    <div style="display: flex; flex-direction: column; gap: 0.4rem; max-height: 180px; overflow-y: auto;">
                        ${cl.doctors.map((doc, idx) => `
                            <div style="font-size: 0.85rem; padding: 0.4rem; background: white; border: 1px solid var(--border); border-radius: 0.25rem;">
                                ${idx + 1}. <strong>${escapeHtml(doc.name)}</strong> <span style="color: var(--text-muted);">(${escapeHtml(doc.specialization || 'Umum')})</span>
                            </div>
                        `).join('')}
                    </div>
                </div>

                <div>
                    <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.5rem;">Riwayat Persetujuan (Audit Trail)</h4>
                    <div style="display: flex; flex-direction: column; gap: 0.4rem; font-size: 0.8rem; max-height: 150px; overflow-y: auto;">
                        ${cl.approval_history.map(h => `
                            <div style="padding: 0.4rem; background: #f1f5f9; border-radius: 0.25rem;">
                                <div><strong>${escapeHtml(h.actor_name)}</strong> melakukan <code>${escapeHtml(h.event)}</code> (${new Date(h.created_at).toLocaleString('id-ID')})</div>
                                ${h.reason ? `<div style="color: #64748b; margin-top: 0.2rem;">Catatan: <em>"${escapeHtml(h.reason)}"</em></div>` : ''}
                            </div>
                        `).join('')}
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.5rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                    ${isOwnerDraft ? `
                        <button class="btn btn-primary" onclick="submitCallList(${cl.id}); closeDetailModal();">Ajukan ke Atasan</button>
                    ` : ''}
                    ${isAssignedApprover ? `
                        <button class="btn" style="background: var(--danger); color: white;" onclick="openRejectModal(${cl.id})">Tolak</button>
                        <button class="btn btn-primary" style="background: var(--success);" onclick="approveCallList(${cl.id})">Setujui</button>
                    ` : ''}
                    <button class="btn btn-outline" onclick="closeDetailModal()">Tutup</button>
                </div>
            `;
        } catch (err) {
            content.innerHTML = '<div class="alert alert-danger">Gagal memuat detail Call List.</div>';
        }
    }

    function closeDetailModal() {
        document.getElementById('detailModal').style.display = 'none';
    }
</script>
@endpush
@endsection
