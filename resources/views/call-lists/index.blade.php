@extends('layouts.app')

@section('title', 'Call List')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <!-- Header Section -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; color: var(--secondary); letter-spacing: -0.02em; margin-bottom: 0.15rem;">Target Dokter</h1>
            <p style="color: var(--text-muted); font-size: 0.8125rem;">Call List Bulanan : Perencanaan target dokter bulanan dan alur persetujuan atasan langsung.</p>
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
            Target Saya (<span id="myListCount" class="tabular-nums">0</span>)
        </button>
        @if(in_array(auth()->user()->role, ['dm', 'rsm', 'mm']))
        <button id="tabReview" class="tab-btn" onclick="switchTab('review')">
            Persetujuan Masuk (<span id="reviewCount" class="tabular-nums">0</span>)
        </button>
        @endif
    </div>

    <!-- Alert Banner inside view -->
    <div id="statusAlert" style="display: none;" class="alert"></div>

    <!-- Container Tab 1: My Call Lists -->
    <div id="myListsContainer" style="display: flex; flex-direction: column; gap: 1rem;">
        <div id="loadingMyLists" style="padding: 0.25rem 0;"></div>
        <div id="myListsTableWrap" class="table-responsive" style="display: none;">
            <table class="enterprise-table">
                <thead>
                    <tr>
                        <th>Periode</th>
                        <th>Target Dokter</th>
                        <th>Status</th>
                        <th>Atasan Peninjau</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="myListsTableBody"></tbody>
            </table>
        </div>
        <div id="emptyMyLists" style="display: none; text-align: center; padding: 3rem 1.5rem; background: white; border: 1px solid var(--border); border-radius: 8px;">
            <h4 style="font-size: 1rem; font-weight: 700; color: var(--secondary); margin-bottom: 0.35rem;">Belum Ada Target Call List</h4>
            <p style="color: var(--text-muted); font-size: 0.875rem; max-width: 420px; margin: 0 auto 1.25rem;">
                Susun perencanaan target dokter bulanan dan ajukan ke atasan langsung Anda untuk mendapatkan persetujuan operasional.
            </p>
            <button type="button" class="btn btn-primary" onclick="openCreateModal()">+ Buat Call List Baru</button>
        </div>
    </div>

    <!-- Container Tab 2: Manager Review Queue -->
    @if(in_array(auth()->user()->role, ['dm', 'rsm', 'mm']))
    <div id="reviewContainer" style="display: none; flex-direction: column; gap: 1rem;">
        <!-- Widget Ringkasan Monitoring Wilayah (Read-Only) -->
        <div id="monitoringSummaryCard" style="display: none; background: #ffffff; border: 1px solid var(--border); border-radius: 6px; padding: 1rem 1.15rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; flex-wrap: wrap; gap: 0.5rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border);">
                <div>
                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--secondary); margin-bottom: 0.1rem;">
                        Monitoring Pengajuan Wilayah
                    </h4>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">
                        Rekapitulasi target dokter dari tim bawahan langsung pada periode terpilih.
                    </p>
                </div>
                <div style="display: flex; align-items: center; gap: 0.4rem;">
                    <label for="summaryMonthSelect" style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">Periode:</label>
                    <input type="month" id="summaryMonthSelect" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; min-height: 34px;" onchange="loadManagerSummary()">
                </div>
            </div>

            <!-- Integrated Enterprise Metric Strip -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); border: 1px solid var(--border); border-radius: 5px; background: #f8fafc; overflow: hidden; margin-bottom: 0.85rem;">
                <div style="padding: 0.65rem 0.85rem; border-right: 1px solid var(--border);">
                    <div style="font-size: 0.6875rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">Tim Bawahan</div>
                    <div id="statTotalSubordinates" class="tabular-nums" style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin-top: 0.15rem;">0</div>
                </div>
                <div style="padding: 0.65rem 0.85rem; border-right: 1px solid var(--border);">
                    <div style="font-size: 0.6875rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">Target Dokter</div>
                    <div id="statTotalDoctors" class="tabular-nums" style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin-top: 0.15rem;">0</div>
                </div>
                <div style="padding: 0.65rem 0.85rem; border-right: 1px solid var(--border); background: #ecfdf5;">
                    <div style="font-size: 0.6875rem; color: #065f46; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">Disetujui</div>
                    <div id="statApproved" class="tabular-nums" style="font-size: 1.25rem; font-weight: 700; color: #047857; margin-top: 0.15rem;">0</div>
                </div>
                <div style="padding: 0.65rem 0.85rem; background: #fffbeb;">
                    <div style="font-size: 0.6875rem; color: #92400e; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">Menunggu Persetujuan</div>
                    <div id="statPending" class="tabular-nums" style="font-size: 1.25rem; font-weight: 700; color: #b45309; margin-top: 0.15rem;">0</div>
                </div>
            </div>

            <!-- Subordinates Progress Breakdown as a Clean List -->
            <div>
                <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.04em;">Daftar Anggota Tim & Target:</div>
                <div id="subordinatesListContainer" class="team-roster-list"></div>
            </div>
        </div>

        <div id="loadingReview" style="padding: 0.25rem 0;"></div>
        <div id="reviewTableWrap" class="table-responsive" style="display: none;">
            <table class="enterprise-table">
                <thead>
                    <tr>
                        <th>Periode</th>
                        <th>Bawahan Pengaju</th>
                        <th>Target Dokter</th>
                        <th>Status</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="reviewTableBody"></tbody>
            </table>
        </div>
        <div id="emptyReview" style="display: none; text-align: center; padding: 3rem 1.5rem; background: white; border: 1px solid var(--border); border-radius: 8px;">
            <h4 style="font-size: 1rem; font-weight: 700; color: var(--secondary); margin-bottom: 0.35rem;">Semua Pengajuan Telah Ditinjau</h4>
            <p style="color: var(--text-muted); font-size: 0.875rem; max-width: 400px; margin: 0 auto;">
                Tidak ada pengajuan bawahan yang sedang menunggu persetujuan Anda saat ini.
            </p>
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
                    <i class="bi bi-person-circle" style="color: var(--primary); margin-right: 0.25rem;"></i>Pilih Dokter Target (Minimal 1):
                </label>
                <div class="modal-filter-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" id="doctorSearchInput" class="modal-filter-input" placeholder="Cari nama atau spesialisasi dokter..." oninput="filterDoctorPicker(this.value)">
                </div>
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
        border-radius: 0.75rem;
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 0.85rem;
        box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.04), 0 1px 2px -1px rgba(15, 23, 42, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.9);
        transition: transform 0.2s var(--ease-spring), box-shadow 0.2s var(--ease-spring), border-color 0.2s ease;
    }
    .call-card:hover {
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
    .status-draft { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
    .status-pending { background: #fffbeb; color: #92400e; border-color: #fde68a; }
    .status-approved { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
    .status-rejected { background: #fef2f2; color: #991b1b; border-color: #fecaca; }

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
            loadManagerSummary();
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
        window.showToast(message, type);
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
            loadManagerSummary();
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
            container.innerHTML = '<span style="font-size:0.85rem; color:red;">Gagal memuat daftar dokter.</span>';
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

    function filterDoctorPicker(keyword) {
        const term = String(keyword || '').toLowerCase().trim();
        const labels = document.querySelectorAll('#doctorPickerList label');
        labels.forEach(label => {
            const text = label.innerText.toLowerCase();
            label.style.display = text.includes(term) ? 'flex' : 'none';
        });
    }

    async function loadMyCallLists() {
        const loadingEl = document.getElementById('loadingMyLists');
        if (loadingEl) {
            loadingEl.innerHTML = window.renderTableSkeleton(3);
            loadingEl.style.display = 'block';
        }
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

    function formatPeriodIndonesian(periodStr) {
        if (!periodStr) return '-';
        const parts = String(periodStr).split('-');
        if (parts.length !== 2) return periodStr;
        const year = parts[0];
        const monthNum = parseInt(parts[1], 10);
        const monthNames = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        const monthName = monthNames[monthNum - 1] || parts[1];
        return `${monthName} ${year}`;
    }

    function renderMyListsGrid() {
        const tableWrap = document.getElementById('myListsTableWrap');
        const tbody = document.getElementById('myListsTableBody');
        const empty = document.getElementById('emptyMyLists');
        if (!myCallLists.length) {
            if (tbody) tbody.innerHTML = '';
            if (tableWrap) tableWrap.style.display = 'none';
            empty.style.display = 'block';
            return;
        }
        empty.style.display = 'none';
        if (tableWrap) tableWrap.style.display = 'block';
        tbody.innerHTML = myCallLists.map(cl => {
            let statusClass = `status-${cl.status === 'pending_approval' ? 'pending' : cl.status}`;
            let statusLabel = cl.status === 'pending_approval' ? 'Menunggu Persetujuan' : (cl.status === 'approved' ? 'Disetujui' : 'Draft');
            if (cl.status === 'draft' && cl.is_rejected_revision) {
                statusClass = 'status-rejected';
                statusLabel = 'Perlu Revisi (Ditolak)';
            }
            return `
                <tr>
                    <td style="white-space: nowrap;">
                        <strong style="color: var(--secondary); font-size: 0.95rem;">${formatPeriodIndonesian(cl.period_month)}</strong>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">${escapeHtml(cl.period_month)}</div>
                    </td>
                    <td style="white-space: nowrap;">
                        <strong style="color: var(--text-main);">${cl.doctor_count} Target Dokter</strong>
                    </td>
                    <td>
                        <span class="status-badge ${statusClass}">${escapeHtml(statusLabel)}</span>
                        ${cl.status === 'draft' && cl.is_rejected_revision && cl.latest_rejection_reason ? `
                            <div style="font-size: 0.775rem; color: var(--danger); background: #fff1f2; border: 1px solid #fecdd3; border-radius: 4px; padding: 0.35rem 0.55rem; margin-top: 0.35rem; line-height: 1.35;">
                                <strong>Catatan Revisi:</strong> "${escapeHtml(cl.latest_rejection_reason)}"
                            </div>
                        ` : ''}
                    </td>
                    <td style="white-space: nowrap;">
                        <span>${escapeHtml(cl.assigned_approver_name || '-')}</span>
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        <div style="display: inline-flex; gap: 0.35rem;">
                            <button class="btn btn-outline btn-sm" onclick="openDetailModal(${cl.id})">Detail</button>
                            ${cl.status === 'draft' ? `
                                <button class="btn btn-primary btn-sm" onclick="submitCallList(${cl.id})">Ajukan</button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    async function loadReviewCallLists() {
        const loadingEl = document.getElementById('loadingReview');
        if (!loadingEl) return;
        loadingEl.innerHTML = window.renderTableSkeleton(3);
        loadingEl.style.display = 'block';
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
        const tableWrap = document.getElementById('reviewTableWrap');
        const tbody = document.getElementById('reviewTableBody');
        const empty = document.getElementById('emptyReview');
        if (!reviewCallLists.length) {
            if (tbody) tbody.innerHTML = '';
            if (tableWrap) tableWrap.style.display = 'none';
            empty.style.display = 'block';
            return;
        }
        empty.style.display = 'none';
        if (tableWrap) tableWrap.style.display = 'block';
        tbody.innerHTML = reviewCallLists.map(cl => `
            <tr>
                <td style="white-space: nowrap;">
                    <strong style="color: var(--secondary); font-size: 0.95rem;">${formatPeriodIndonesian(cl.period_month)}</strong>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">${escapeHtml(cl.period_month)}</div>
                </td>
                <td style="white-space: nowrap;">
                    <strong style="color: var(--text-main);">${escapeHtml(cl.owner_name)}</strong>
                </td>
                <td style="white-space: nowrap;">
                    <strong>${cl.doctor_count} Target Dokter</strong>
                </td>
                <td>
                    <span class="status-badge status-pending">Menunggu Persetujuan</span>
                </td>
                <td style="text-align: right; white-space: nowrap;">
                    <div style="display: inline-flex; gap: 0.35rem;">
                        <button class="btn btn-outline btn-sm" onclick="openDetailModal(${cl.id})">Periksa</button>
                        <button class="btn btn-primary btn-sm" style="background: var(--success); border-color: var(--success);" onclick="approveCallList(${cl.id})">Setujui</button>
                        <button class="btn btn-outline btn-sm" style="color: var(--danger); border-color: #fecdd3;" onclick="openRejectModal(${cl.id})">Tolak</button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    async function loadManagerSummary() {
        const summaryCard = document.getElementById('monitoringSummaryCard');
        if (!summaryCard) return;

        let month = document.getElementById('summaryMonthSelect')?.value;
        let url = '/api/call-lists/summary';
        if (month) {
            url += `?period_month=${encodeURIComponent(month)}`;
        }

        try {
            const res = await window.apiFetch(url);
            const result = await res.json();
            if (result.success && result.data) {
                const data = result.data;
                summaryCard.style.display = 'block';
                if (!month && data.period_month) {
                    document.getElementById('summaryMonthSelect').value = data.period_month.substring(0, 7);
                }
                document.getElementById('statTotalSubordinates').innerText = data.total_subordinates;
                document.getElementById('statTotalDoctors').innerText = data.total_target_doctors;
                document.getElementById('statApproved').innerText = `${data.approved_count} / ${data.total_subordinates}`;
                document.getElementById('statPending').innerText = data.pending_count;

                const listContainer = document.getElementById('subordinatesListContainer');
                if (data.subordinates_detail && data.subordinates_detail.length) {
                    listContainer.innerHTML = data.subordinates_detail.map(sub => {
                        let statusTheme = 'status-none';
                        let statusText = 'Belum Mengajukan';

                        if (sub.status === 'approved') {
                            statusTheme = 'status-approved';
                            statusText = 'Disetujui';
                        } else if (sub.status === 'pending_approval') {
                            statusTheme = 'status-pending';
                            statusText = 'Menunggu Persetujuan';
                        } else if (sub.status === 'draft') {
                            statusTheme = 'status-draft';
                            statusText = 'Perlu Revisi';
                        }

                        const actionBtn = sub.call_list_id ? `
                            <button type="button" class="btn btn-outline btn-sm" onclick="viewListDetail(${sub.call_list_id})" style="padding: 0.25rem 0.65rem; font-size: 0.775rem;">
                                Lihat Target
                            </button>
                        ` : `
                            <span style="font-size: 0.75rem; color: #94a3b8; font-style: italic;">Belum Ada Target</span>
                        `;

                        return `
                            <div class="team-roster-item">
                                <div class="team-roster-user">
                                    <div class="team-roster-name-wrap">
                                        <strong class="team-roster-name">${escapeHtml(sub.name)}</strong>
                                        <span class="role-pill role-${sub.role}">${sub.role.toUpperCase()}</span>
                                    </div>
                                    <span class="team-roster-subtitle">${escapeHtml(sub.role_subtitle || '')}</span>
                                </div>
                                <div class="team-roster-meta">
                                    <div class="team-roster-count">
                                        <span class="team-roster-num tabular-nums">${sub.doctor_count}</span>
                                        <span class="team-roster-label">Target Dokter</span>
                                    </div>
                                    <div class="team-roster-status ${statusTheme}">
                                        <span class="team-roster-dot"></span>
                                        <span>${statusText}</span>
                                    </div>
                                    <div class="team-roster-action">
                                        ${actionBtn}
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');
                } else {
                    listContainer.innerHTML = '<div style="padding: 1rem; text-align: center; color: var(--text-muted); font-size: 0.8rem;">Tidak ada anggota tim terdaftar.</div>';
                }
            }
        } catch (err) {
            console.error('Gagal mengambil ringkasan monitoring manajerial', err);
        }
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
            errEl.innerText = 'Pilih minimal 1 target dokter dari daftar.';
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
                    <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.5rem;">Daftar Target Dokter (${cl.doctors.length})</h4>
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
                                <div><strong>${escapeHtml(h.actor_name)}</strong> melakukan <code>${escapeHtml(h.event)}</code> (${window.formatDateTimeIndonesia(h.created_at)})</div>
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
