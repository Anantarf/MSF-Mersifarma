@extends('layouts.app')

@section('title', 'Target Dokter')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; color: var(--secondary); letter-spacing: -0.02em; margin-bottom: 0.15rem;">Target Dokter</h1>
            <p style="color: var(--text-muted); font-size: 0.8125rem; margin: 0;">Call List Bulanan</p>
        </div>
        <div>
            @if(auth()->user()->role === 'mr')
            <button type="button" class="btn btn-primary" onclick="openCreateModal()">
                + Susun Target Dokter
            </button>
            @endif
        </div>
    </div>

    <!-- Tabs Navigation (Khusus Manajer) -->
    @if(in_array(auth()->user()->role, ['dm', 'rsm', 'mm']))
    <div style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--border);">
        <button id="tabReview" class="tab-btn active" onclick="switchTab('review')">
            Persetujuan Masuk (<span id="reviewCount" class="tabular-nums">0</span>)
        </button>
        <button id="tabMyLists" class="tab-btn" onclick="switchTab('myLists')">
            Target Saya (<span id="myListCount" class="tabular-nums">0</span>)
        </button>
    </div>
    @endif

    <!-- Alert Banner -->
    <div id="statusAlert" style="display: none;" class="alert"></div>

    <!-- ================= TAB 1: TARGET SAYA (MR VIEW) ================= -->
    <div id="myListsContainer" style="display: {{ auth()->user()->role === 'mr' ? 'flex' : 'none' }}; flex-direction: column; gap: 1.25rem;">
        <div id="loadingMyLists" style="padding: 0.25rem 0;"></div>

        <!-- Kartu Target Periode Berjalan / Utama -->
        <div id="currentMonthSpotlight" style="display: none; background: #ffffff; border: 1px solid var(--border); border-radius: 8px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05); overflow: hidden;">
            <!-- Header Kartu Target -->
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <div style="display: flex; align-items: center; gap: 0.65rem; margin-bottom: 0.25rem;">
                        <span style="font-size: 0.725rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Target Periode</span>
                        <div id="spotlightStatusBadge"></div>
                    </div>
                    <h2 id="spotlightPeriod" style="font-size: 1.35rem; font-weight: 700; color: var(--secondary); margin: 0;">-</h2>
                    <div style="font-size: 0.825rem; color: var(--text-muted); margin-top: 0.25rem;">
                        Atasan Peninjau: <strong id="spotlightApprover" style="color: var(--secondary);">-</strong>
                    </div>
                </div>

                <div id="spotlightActionBtns" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;"></div>
            </div>

            <!-- Pesan Alasan Revisi dari DM (Jika Ditolak) -->
            <div id="spotlightRejectionBox" style="display: none; margin: 1.25rem 1.5rem 0 1.5rem; padding: 0.85rem 1.15rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px;">
                <div style="font-size: 0.75rem; font-weight: 700; color: #991b1b; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 0.25rem;">
                    Catatan Revisi dari Atasan:
                </div>
                <div id="spotlightRejectionReason" style="font-size: 0.875rem; color: #7f1d1d; font-style: italic; line-height: 1.4;">-</div>
            </div>

            <!-- Daftar Dokter Sasaran (Langsung tampil di dalam kartu) -->
            <div style="padding: 1.25rem 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--secondary); margin: 0;">Daftar Dokter Sasaran</h4>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">
                            Total komitmen target: <strong id="spotlightDoctorCount" class="tabular-nums" style="color: var(--secondary);">0</strong> Dokter
                        </div>
                    </div>
                    <div id="spotlightQuickAddBtnWrap"></div>
                </div>

                <!-- Tabel Dokter Sasaran -->
                <div class="table-responsive" style="border: 1px solid var(--border); border-radius: 6px; overflow: hidden;">
                    <table class="enterprise-table" style="margin: 0;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="width: 45px; text-align: center;">No</th>
                                <th>Nama Dokter</th>
                                <th>Spesialisasi</th>
                                <th>Alamat / Faskes</th>
                            </tr>
                        </thead>
                        <tbody id="spotlightDoctorsTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Kartu Riwayat Periode Sebelumnya (Hanya tampil jika ada riwayat lampau) -->
        <div id="myListsTableWrap" style="display: none; background: #ffffff; border: 1px solid var(--border); border-radius: 8px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04); overflow: hidden;">
            <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); background: #ffffff;">
                <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--secondary); margin: 0;">Riwayat Periode Sebelumnya</h3>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.2rem 0 0 0;">Daftar target dokter dari bulan-bulan sebelumnya</p>
            </div>
            <div class="table-responsive" style="border: none; border-radius: 0; box-shadow: none;">
                <table class="enterprise-table" style="margin: 0;">
                    <thead>
                        <tr style="background: #f8fafc;">
                            <th style="padding-left: 1.25rem;">Periode</th>
                            <th>Target Dokter</th>
                            <th>Status</th>
                            <th>Atasan Peninjau</th>
                            <th style="text-align: right; padding-right: 1.25rem;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="myListsTableBody"></tbody>
                </table>
            </div>
        </div>

        <!-- Empty State Target -->
        <div id="emptyMyLists" style="display: none; text-align: center; padding: 3rem 1.5rem; background: white; border: 1px solid var(--border); border-radius: 8px;">
            <h4 style="font-size: 1.05rem; font-weight: 700; color: var(--secondary); margin-bottom: 0.35rem;">Belum Ada Target Dokter</h4>
            <p style="color: var(--text-muted); font-size: 0.875rem; max-width: 420px; margin: 0 auto 1.25rem;">
                Mulai susun komitmen target dokter bulanan Anda dari Master Dokter untuk diajukan ke atasan langsung.
            </p>
            @if(auth()->user()->role === 'mr')
            <button type="button" class="btn btn-primary" onclick="openCreateModal()">+ Susun Target Dokter</button>
            @endif
        </div>
    </div>

    <!-- ================= TAB 2: PERSETUJUAN MASUK (MANAGER VIEW) ================= -->
    @if(in_array(auth()->user()->role, ['dm', 'rsm', 'mm']))
    <div id="reviewContainer" style="display: {{ auth()->user()->role !== 'mr' ? 'flex' : 'none' }}; flex-direction: column; gap: 1rem;">
        <!-- Widget Ringkasan Monitoring Wilayah -->
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

            <!-- Metric Strip -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); border: 1px solid var(--border); border-radius: 5px; background: #f8fafc; overflow: hidden; margin-bottom: 0.85rem;">
                <div style="padding: 0.65rem 0.85rem; border-right: 1px solid var(--border);">
                    <div style="font-size: 0.6875rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">Tim Bawahan</div>
                    <div id="statTotalSubordinates" class="tabular-nums" style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin-top: 0.15rem;">0</div>
                </div>
                <div style="padding: 0.65rem 0.85rem; border-right: 1px solid var(--border);">
                    <div style="font-size: 0.6875rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">Target Dokter</div>
                    <div id="statTotalDoctors" class="tabular-nums" style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin-top: 0.15rem;">0</div>
                </div>
                <div style="padding: 0.65rem 0.85rem; border-right: 1px solid var(--border); background: #f0fdf4;">
                    <div style="font-size: 0.6875rem; color: #166534; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">Disetujui</div>
                    <div id="statApproved" class="tabular-nums" style="font-size: 1.25rem; font-weight: 700; color: #15803d; margin-top: 0.15rem;">0</div>
                </div>
                <div style="padding: 0.65rem 0.85rem; background: #fffbeb;">
                    <div style="font-size: 0.6875rem; color: #92400e; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">Menunggu Review</div>
                    <div id="statPending" class="tabular-nums" style="font-size: 1.25rem; font-weight: 700; color: #b45309; margin-top: 0.15rem;">0</div>
                </div>
            </div>

            <!-- Daftar Anggota Tim -->
            <div>
                <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.04em;">Daftar Anggota Tim & Status:</div>
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

<!-- ================= MODAL: PENYUSUNAN TARGET DOKTER (CREATE / EDIT) ================= -->
<div id="targetModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card" style="max-width: 680px;">
        <div class="target-detail-head">
            <div>
                <h3 id="targetModalTitle" style="font-size: 1.15rem; font-weight: 700; color: var(--secondary); margin: 0;">Susun Target Dokter</h3>
                <span id="targetModalSubtitle" style="font-size: 0.8rem; color: var(--text-muted);">Pilih dokter sasaran dari Master Customer List</span>
            </div>
            <button type="button" class="close-btn" onclick="closeTargetModal()">&times;</button>
        </div>

        <div id="targetModalError" style="display: none;" class="alert alert-danger"></div>

        <form id="targetForm" onsubmit="handleTargetFormSubmit(event)">
            <input type="hidden" id="editingCallListId" value="">

            <!-- 1. Pilihan Periode Bulan -->
            <div style="margin-bottom: 1rem;">
                <label for="periodMonthInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--secondary);">
                    Periode Bulan:
                </label>
                <input type="month" id="periodMonthInput" required
                       style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.9rem;">
            </div>

            <!-- 2. Pencarian & Filter Dokter -->
            <div style="margin-bottom: 0.75rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                    <label style="font-size: 0.85rem; font-weight: 600; color: var(--secondary);">
                        Pilih Dokter Target (Minimal 1 Dokter):
                    </label>
                    <span style="font-size: 0.8rem; color: var(--primary); font-weight: 600;">
                        <span id="selectedDoctorCount">0</span> Dokter Terpilih
                    </span>
                </div>

                <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <input type="text" id="doctorSearchInput" placeholder="Cari nama dokter, spesialisasi, atau rumah sakit..."
                           oninput="onDoctorSearchInput(this.value)"
                           style="flex: 1; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.875rem;">
                    <button type="button" id="btnFilterToggleSelected" class="btn btn-outline btn-sm" onclick="toggleShowOnlySelected()" style="white-space: nowrap; font-size: 0.8rem;">
                        Tampilkan Terpilih
                    </button>
                </div>
            </div>

            <!-- 3. Daftar Dokter Lega & Interaktif -->
            <div id="doctorPickerList" style="max-height: 320px; overflow-y: auto; border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.5rem; display: flex; flex-direction: column; gap: 0.4rem; background: #f8fafc;">
                <span style="font-size: 0.85rem; color: var(--text-muted); padding: 0.5rem;">Memuat daftar dokter...</span>
            </div>

            <!-- 4. Footer Modal -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.25rem; border-top: 1px solid var(--border); padding-top: 0.85rem; flex-wrap: wrap; gap: 0.5rem;">
                <span style="font-size: 0.85rem; color: var(--text-muted);">
                    Total: <strong id="footerCountText" class="tabular-nums" style="color: var(--secondary);">0</strong> dokter
                </span>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeTargetModal()">Batal</button>
                    <button type="submit" id="btnSubmitDraft" class="btn btn-outline" style="border-color: var(--primary); color: var(--primary); font-weight: 600;">
                        Simpan Draft
                    </button>
                    <button type="button" id="btnSubmitAndSend" class="btn btn-primary" onclick="handleSaveAndSubmit()">
                        Simpan & Ajukan ke DM
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL: DETAIL CALL LIST ================= -->
<div id="detailModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card target-detail-modal">
        <div class="target-detail-head">
            <div>
                <h3 id="detailTitle" class="target-detail-title">Detail Target Dokter</h3>
                <span id="detailPeriodSubtitle" class="target-detail-subtitle">-</span>
            </div>
            <button type="button" class="close-btn" onclick="closeDetailModal()">&times;</button>
        </div>

        <div id="detailContent" class="target-detail-content">
            <div style="text-align: center; padding: 2rem; color: var(--text-muted);">Memuat detail...</div>
        </div>
    </div>
</div>

<!-- ================= MODAL: TOLAK PENGAJUAN (REJECT MODAL) ================= -->
<div id="rejectModal" class="modal-backdrop" style="display: none; z-index: 60;">
    <div class="modal-card" style="max-width: 440px;">
        <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--danger); margin-bottom: 0.5rem;">Tolak Target Dokter</h3>
        <p style="font-size: 0.825rem; color: var(--text-muted); margin-bottom: 1rem; line-height: 1.4;">
            Target akan dikembalikan ke status <strong>Perlu Revisi (Draft)</strong> agar bawahan dapat menyesuaikan daftar dokter sasaran.
        </p>
        <div id="rejectModalError" style="display: none;" class="alert alert-danger"></div>
        <form onsubmit="handleRejectSubmit(event)">
            <input type="hidden" id="rejectCallListId">
            <div style="margin-bottom: 1rem;">
                <label for="rejectReasonInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--secondary);">
                    Alasan Penolakan (Wajib Diisi):
                </label>
                <textarea id="rejectReasonInput" rows="3" required placeholder="Contoh: Tambahkan 3 dokter spesialis anak di RSUD wilayah distrik."
                          style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.875rem;"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeRejectModal()">Batal</button>
                <button type="submit" id="btnConfirmReject" class="btn" style="background: var(--danger); color: white; font-weight: 600;">
                    Tolak & Kembalikan
                </button>
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
    .target-detail-modal {
        max-width: 640px;
        padding: 1.4rem 1.6rem;
    }
    .target-detail-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.15rem;
        padding-bottom: 0.85rem;
        border-bottom: 1px solid var(--border);
    }
    .target-detail-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--secondary);
        margin: 0 0 0.2rem;
    }
    .target-detail-subtitle {
        font-size: 0.82rem;
        color: var(--text-muted);
    }
    .target-detail-content {
        display: flex;
        flex-direction: column;
        gap: 1.1rem;
    }
    .target-detail-meta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem 1rem;
        font-size: 0.84rem;
        color: var(--text-muted);
    }
    .target-detail-meta strong {
        display: block;
        color: var(--secondary);
        font-size: 0.9rem;
        margin-top: 0.15rem;
    }
    .target-detail-section-title {
        font-size: 0.88rem;
        font-weight: 700;
        color: var(--secondary);
        margin: 0 0 0.5rem;
    }
    .target-detail-list {
        display: flex;
        flex-direction: column;
        border-top: 1px solid var(--border);
    }
    .target-detail-doctor {
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--border);
    }
    .target-detail-doctor strong {
        display: block;
        color: var(--secondary);
        font-size: 0.92rem;
        margin-bottom: 0.15rem;
    }
    .target-detail-doctor span,
    .target-detail-history {
        color: var(--text-muted);
        font-size: 0.82rem;
        line-height: 1.5;
    }
    .target-detail-history {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }
    .target-detail-history-row strong {
        color: var(--secondary);
    }
    .target-detail-actions {
        display: flex;
        justify-content: flex-end;
        padding-top: 0.25rem;
    }
    @media (max-width: 640px) {
        .target-detail-meta { grid-template-columns: 1fr; }
        .target-detail-modal { padding: 1.15rem; }
    }
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
    .doctor-selectable-row {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 6px;
        padding: 0.65rem 0.85rem;
        cursor: pointer;
        transition: background 0.15s ease, border-color 0.15s ease;
        user-select: none;
    }
    .doctor-selectable-row:hover {
        border-color: #94a3b8;
        background: #f1f5f9;
    }
    .doctor-selectable-row.selected {
        border-color: var(--primary);
        background: #f0fdf4;
    }
    .doctor-selectable-row input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: var(--primary);
        flex-shrink: 0;
    }
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
    .team-roster-list {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }
    .team-roster-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0.75rem;
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 5px;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    .role-pill {
        font-size: 0.6875rem;
        font-weight: 700;
        padding: 0.1rem 0.4rem;
        border-radius: 3px;
        letter-spacing: 0.04em;
    }
    .role-mr { background: #e0f2fe; color: #0369a1; }
    .role-dm { background: #fef3c7; color: #92400e; }
    .role-rsm { background: #f3e8ff; color: #7e22ce; }
</style>
@endpush

@push('scripts')
<script>
    const currentUserRole = "{{ auth()->user()->role }}";
    let allDoctors = [];
    let myCallLists = [];
    let reviewCallLists = [];
    let currentSelectedDoctorIds = new Set();
    let isFilterOnlySelected = false;

    document.addEventListener('DOMContentLoaded', () => {
        loadDoctors();
        if (currentUserRole === 'mr') {
            loadMyCallLists();
        } else {
            loadReviewCallLists();
            loadMyCallLists();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeTargetModal();
            closeDetailModal();
            closeRejectModal();
        }
    });

    function showAlert(message, type = 'success') {
        window.showToast(message, type);
    }

    function switchTab(tab, isManual = true) {
        if (isManual) {
            sessionStorage.setItem('call_list_tab_manual', '1');
        }
        if (document.getElementById('tabMyLists')) document.getElementById('tabMyLists').classList.remove('active');
        if (document.getElementById('tabReview')) document.getElementById('tabReview').classList.remove('active');

        if (tab === 'myLists') {
            if (document.getElementById('tabMyLists')) document.getElementById('tabMyLists').classList.add('active');
            document.getElementById('myListsContainer').style.display = 'flex';
            if (document.getElementById('reviewContainer')) document.getElementById('reviewContainer').style.display = 'none';
        } else {
            if (document.getElementById('tabReview')) document.getElementById('tabReview').classList.add('active');
            document.getElementById('myListsContainer').style.display = 'none';
            if (document.getElementById('reviewContainer')) {
                document.getElementById('reviewContainer').style.display = 'flex';
                loadManagerSummary();
            }
        }
    }

    async function loadDoctors() {
        try {
            const res = await window.apiFetch('/api/doctors');
            const data = await res.json();
            if (data.success) {
                allDoctors = data.data;
            }
        } catch (err) {
            console.error('Gagal memuat dokter', err);
        }
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
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
            if (loadingEl) loadingEl.style.display = 'none';

            if (result.success) {
                myCallLists = result.data;
                const countEl = document.getElementById('myListCount');
                if (countEl) countEl.innerText = myCallLists.length;
                renderMyListsView();
            }
        } catch (err) {
            if (loadingEl) loadingEl.style.display = 'none';
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
        return `${monthNames[monthNum - 1] || parts[1]} ${year}`;
    }

    function renderMyListsView() {
        const spotlight = document.getElementById('currentMonthSpotlight');
        const tableWrap = document.getElementById('myListsTableWrap');
        const tbody = document.getElementById('myListsTableBody');
        const empty = document.getElementById('emptyMyLists');

        if (!myCallLists.length) {
            if (spotlight) spotlight.style.display = 'none';
            if (tableWrap) tableWrap.style.display = 'none';
            if (empty) empty.style.display = 'block';
            return;
        }

        if (empty) empty.style.display = 'none';

        // 1. Render Kartu Target Periode Berjalan / Utama
        const activeList = myCallLists[0];
        if (spotlight && activeList) {
            spotlight.style.display = 'block';
            document.getElementById('spotlightPeriod').innerText = formatPeriodIndonesian(activeList.period_month);
            document.getElementById('spotlightDoctorCount').innerText = activeList.doctor_count;
            document.getElementById('spotlightApprover').innerText = activeList.assigned_approver_name || 'Atasan Langsung';

            const badgeEl = document.getElementById('spotlightStatusBadge');
            const btnsEl = document.getElementById('spotlightActionBtns');
            const rejBox = document.getElementById('spotlightRejectionBox');
            const quickAddWrap = document.getElementById('spotlightQuickAddBtnWrap');
            const doctorsTableBody = document.getElementById('spotlightDoctorsTableBody');

            let badgeHtml = '';
            let btnsHtml = '';

            if (activeList.status === 'approved') {
                badgeHtml = `<span class="status-badge status-approved" style="font-size: 0.8rem; padding: 0.25rem 0.65rem;">Disetujui</span>`;
                btnsHtml = `
                    <button class="btn btn-outline btn-sm" onclick="openDetailModal(${activeList.id})">Lihat Histori</button>
                    <a href="/call-plans" class="btn btn-primary btn-sm" style="text-decoration: none;">+ Jadwalkan Kunjungan</a>
                `;
                if (rejBox) rejBox.style.display = 'none';
                if (quickAddWrap) quickAddWrap.innerHTML = '';
            } else if (activeList.status === 'pending_approval') {
                badgeHtml = `<span class="status-badge status-pending" style="font-size: 0.8rem; padding: 0.25rem 0.65rem;">Menunggu Review</span>`;
                btnsHtml = `
                    <button class="btn btn-outline btn-sm" onclick="openDetailModal(${activeList.id})">Detail Pengajuan</button>
                `;
                if (rejBox) rejBox.style.display = 'none';
                if (quickAddWrap) quickAddWrap.innerHTML = '';
            } else if (activeList.status === 'draft' && activeList.is_rejected_revision) {
                badgeHtml = `<span class="status-badge status-rejected" style="font-size: 0.8rem; padding: 0.25rem 0.65rem;">Perlu Revisi</span>`;
                btnsHtml = `
                    <button class="btn btn-outline btn-sm" onclick="openDetailModal(${activeList.id})">Riwayat</button>
                    <button class="btn btn-primary btn-sm" onclick="submitCallList(${activeList.id})">Ajukan Ulang ke DM</button>
                `;
                if (rejBox) {
                    rejBox.style.display = 'block';
                    document.getElementById('spotlightRejectionReason').innerText = activeList.latest_rejection_reason || 'Perlu penyesuaian daftar dokter target.';
                }
                if (quickAddWrap) {
                    quickAddWrap.innerHTML = `
                        <button type="button" class="btn btn-outline btn-sm" onclick="openEditModal(${activeList.id})">
                            + Kelola Dokter Sasaran
                        </button>
                    `;
                }
            } else {
                badgeHtml = `<span class="status-badge status-draft" style="font-size: 0.8rem; padding: 0.25rem 0.65rem;">Draft</span>`;
                btnsHtml = `
                    <button class="btn btn-outline btn-sm" onclick="openDetailModal(${activeList.id})">Detail</button>
                    <button class="btn btn-primary btn-sm" onclick="submitCallList(${activeList.id})">Ajukan ke DM</button>
                `;
                if (rejBox) rejBox.style.display = 'none';
                if (quickAddWrap) {
                    quickAddWrap.innerHTML = `
                        <button type="button" class="btn btn-outline btn-sm" onclick="openEditModal(${activeList.id})">
                            + Kelola Dokter Sasaran
                        </button>
                    `;
                }
            }

            badgeEl.innerHTML = badgeHtml;
            btnsEl.innerHTML = btnsHtml;

            // Render Daftar Dokter Sasaran pada Kartu
            const docs = activeList.doctors || [];
            if (doctorsTableBody) {
                if (docs.length === 0) {
                    doctorsTableBody.innerHTML = `
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 2.5rem 1rem; color: var(--text-muted); font-size: 0.875rem;">
                                Belum ada dokter yang ditargetkan untuk periode ini.
                                ${activeList.status === 'draft' ? `<div style="margin-top: 0.75rem;"><button type="button" class="btn btn-primary btn-sm" onclick="openEditModal(${activeList.id})">+ Pilih Dokter Sasaran</button></div>` : ''}
                            </td>
                        </tr>
                    `;
                } else {
                    doctorsTableBody.innerHTML = docs.map((doc, idx) => `
                        <tr>
                            <td style="text-align: center; color: var(--text-muted); font-weight: 600; font-size: 0.8125rem;">${idx + 1}</td>
                            <td>
                                <strong style="color: var(--secondary); font-size: 0.9rem;">${escapeHtml(doc.name)}</strong>
                            </td>
                            <td>
                                <span class="badge" style="background: #e2e8f0; color: #334155; font-size: 0.75rem; font-weight: 500; padding: 0.2rem 0.5rem; border-radius: 4px;">
                                    ${escapeHtml(doc.specialization || 'Umum')}
                                </span>
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.8125rem;">
                                ${escapeHtml(doc.address || '-')}
                            </td>
                        </tr>
                    `).join('');
                }
            }
        }

        // 2. Render Tabel Riwayat Periode Sebelumnya (Hanya jika ada periode selain yang aktif)
        if (myCallLists.length > 1) {
            if (tableWrap) tableWrap.style.display = 'block';
            if (tbody) {
                const previousLists = myCallLists.slice(1);
                tbody.innerHTML = previousLists.map(cl => {
                    let statusClass = `status-${cl.status === 'pending_approval' ? 'pending' : cl.status}`;
                    let statusLabel = cl.status === 'pending_approval' ? 'Menunggu Review' : (cl.status === 'approved' ? 'Disetujui' : 'Draft');
                    if (cl.status === 'draft' && cl.is_rejected_revision) {
                        statusClass = 'status-rejected';
                        statusLabel = 'Perlu Revisi';
                    }

                    return `
                        <tr>
                            <td style="white-space: nowrap; padding-left: 1.25rem;">
                                <strong style="color: var(--secondary); font-size: 0.9rem;">${formatPeriodIndonesian(cl.period_month)}</strong>
                            </td>
                            <td style="white-space: nowrap;">
                                <strong style="color: var(--secondary); font-size: 0.875rem;">${cl.doctor_count} Dokter</strong>
                            </td>
                            <td>
                                <span class="status-badge ${statusClass}">${escapeHtml(statusLabel)}</span>
                            </td>
                            <td style="white-space: nowrap; color: var(--text-main); font-size: 0.875rem;">
                                <span>${escapeHtml(cl.assigned_approver_name || '-')}</span>
                            </td>
                            <td style="text-align: right; white-space: nowrap; padding-right: 1.25rem;">
                                <div style="display: inline-flex; gap: 0.35rem;">
                                    <button class="btn btn-outline btn-sm" onclick="openDetailModal(${cl.id})">Detail</button>
                                    ${cl.status === 'draft' ? `
                                        <button class="btn btn-outline btn-sm" onclick="openEditModal(${cl.id})">Ubah</button>
                                        <button class="btn btn-primary btn-sm" onclick="submitCallList(${cl.id})">Ajukan</button>
                                    ` : ''}
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        } else {
            if (tableWrap) tableWrap.style.display = 'none';
            if (tbody) tbody.innerHTML = '';
        }
    }

    // Modal Builder: Pemilihan Dokter yang Lega & Nyaman
    async function openCreateModal() {
        document.getElementById('editingCallListId').value = '';
        document.getElementById('targetModalTitle').innerText = 'Susun Target Dokter Baru';
        document.getElementById('targetModalSubtitle').innerText = 'Pilih dokter sasaran dari Master Customer List';

        const periodInput = document.getElementById('periodMonthInput');
        periodInput.disabled = false;

        // Default ke bulan berjalan atau bulan depan
        const now = new Date();
        const yyyy = now.getFullYear();
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        periodInput.value = `${yyyy}-${mm}`;

        currentSelectedDoctorIds = new Set();
        isFilterOnlySelected = false;
        document.getElementById('doctorSearchInput').value = '';
        document.getElementById('btnFilterToggleSelected').innerText = 'Tampilkan Terpilih';

        const errEl = document.getElementById('targetModalError');
        if (errEl) errEl.style.display = 'none';

        if (!allDoctors.length) {
            await loadDoctors();
        }

        renderDoctorPickerList();
        updateSelectedCounter();
        document.getElementById('targetModal').style.display = 'flex';
    }

    async function openEditModal(callListId) {
        document.getElementById('editingCallListId').value = callListId;
        document.getElementById('targetModalTitle').innerText = 'Ubah Target Dokter';
        document.getElementById('targetModalSubtitle').innerText = 'Perbarui daftar dokter sasaran untuk periode ini';

        const errEl = document.getElementById('targetModalError');
        if (errEl) errEl.style.display = 'none';

        if (!allDoctors.length) {
            await loadDoctors();
        }

        try {
            const res = await window.apiFetch(`/api/call-lists/${callListId}`);
            const result = await res.json();
            if (result.success) {
                const cl = result.data;
                const periodInput = document.getElementById('periodMonthInput');
                periodInput.value = cl.period_month;
                periodInput.disabled = true; // Periode tidak boleh diubah saat edit

                currentSelectedDoctorIds = new Set(cl.doctors.map(d => d.id));
                isFilterOnlySelected = false;
                document.getElementById('doctorSearchInput').value = '';
                document.getElementById('btnFilterToggleSelected').innerText = 'Tampilkan Terpilih';

                renderDoctorPickerList();
                updateSelectedCounter();
                document.getElementById('targetModal').style.display = 'flex';
            }
        } catch (err) {
            showAlert('Gagal memuat data target untuk diedit', 'danger');
        }
    }

    function closeTargetModal() {
        document.getElementById('targetModal').style.display = 'none';
    }

    function renderDoctorPickerList() {
        const container = document.getElementById('doctorPickerList');
        const searchTerm = (document.getElementById('doctorSearchInput').value || '').toLowerCase().trim();

        if (!allDoctors.length) {
            container.innerHTML = '<span style="font-size:0.85rem; color: var(--text-muted); padding: 0.5rem;">Memuat dokter...</span>';
            return;
        }

        let filtered = allDoctors.filter(doc => {
            const matchesSearch = doc.name.toLowerCase().includes(searchTerm) ||
                (doc.specialization || '').toLowerCase().includes(searchTerm) ||
                (doc.address || '').toLowerCase().includes(searchTerm);

            if (isFilterOnlySelected) {
                return matchesSearch && currentSelectedDoctorIds.has(doc.id);
            }
            return matchesSearch;
        });

        if (!filtered.length) {
            container.innerHTML = '<div style="padding: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.85rem;">Tidak ada dokter yang cocok dengan pencarian.</div>';
            return;
        }

        container.innerHTML = filtered.map(doc => {
            const isChecked = currentSelectedDoctorIds.has(doc.id);
            return `
                <div class="doctor-selectable-row ${isChecked ? 'selected' : ''}" onclick="toggleDoctorSelection(${doc.id}, event)">
                    <input type="checkbox" ${isChecked ? 'checked' : ''} onclick="event.stopPropagation(); toggleDoctorSelection(${doc.id}, event)">
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap;">
                            <strong style="color: var(--secondary); font-size: 0.9rem;">${escapeHtml(doc.name)}</strong>
                            <span style="font-size: 0.725rem; font-weight: 600; padding: 0.1rem 0.4rem; border-radius: 3px; background: #e2e8f0; color: #334155;">
                                ${escapeHtml(doc.specialization || 'Umum')}
                            </span>
                        </div>
                        <div style="font-size: 0.775rem; color: var(--text-muted); margin-top: 0.1rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            ${escapeHtml(doc.address || '-')}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function toggleDoctorSelection(doctorId, event) {
        if (currentSelectedDoctorIds.has(doctorId)) {
            currentSelectedDoctorIds.delete(doctorId);
        } else {
            currentSelectedDoctorIds.add(doctorId);
        }
        updateSelectedCounter();
        renderDoctorPickerList();
    }

    function updateSelectedCounter() {
        const count = currentSelectedDoctorIds.size;
        document.getElementById('selectedDoctorCount').innerText = count;
        document.getElementById('footerCountText').innerText = count;
    }

    function onDoctorSearchInput(val) {
        renderDoctorPickerList();
    }

    function toggleShowOnlySelected() {
        isFilterOnlySelected = !isFilterOnlySelected;
        document.getElementById('btnFilterToggleSelected').innerText = isFilterOnlySelected ? 'Tampilkan Semua' : 'Tampilkan Terpilih';
        renderDoctorPickerList();
    }

    async function handleTargetFormSubmit(e) {
        e.preventDefault();
        await saveCallList(false);
    }

    async function handleSaveAndSubmit() {
        await saveCallList(true);
    }

    async function saveCallList(autoSubmit = false) {
        const errEl = document.getElementById('targetModalError');
        errEl.style.display = 'none';

        const editingId = document.getElementById('editingCallListId').value;
        const period = document.getElementById('periodMonthInput').value;
        const selectedDoctors = Array.from(currentSelectedDoctorIds);

        if (!selectedDoctors.length) {
            errEl.innerText = 'Pilih minimal 1 target dokter.';
            errEl.style.display = 'block';
            return;
        }

        const btnDraft = document.getElementById('btnSubmitDraft');
        const btnSend = document.getElementById('btnSubmitAndSend');
        btnDraft.disabled = true;
        btnSend.disabled = true;

        try {
            let res, data;
            if (editingId) {
                // Update draft yang sudah ada
                res = await window.apiFetch(`/api/call-lists/${editingId}`, {
                    method: 'PUT',
                    body: JSON.stringify({ doctor_ids: selectedDoctors })
                });
                data = await res.json();
            } else {
                // Buat draft baru
                res = await window.apiFetch('/api/call-lists', {
                    method: 'POST',
                    body: JSON.stringify({
                        period_month: period,
                        doctor_ids: selectedDoctors
                    })
                });
                data = await res.json();
            }

            if (!data.success) {
                btnDraft.disabled = false;
                btnSend.disabled = false;
                errEl.innerText = data.message || 'Gagal menyimpan target dokter.';
                errEl.style.display = 'block';
                return;
            }

            const savedId = editingId || data.data.id;

            if (autoSubmit) {
                const subRes = await window.apiFetch(`/api/call-lists/${savedId}/submit`, { method: 'POST' });
                const subData = await subRes.json();
                btnDraft.disabled = false;
                btnSend.disabled = false;
                closeTargetModal();
                showAlert('Target dokter berhasil disimpan dan diajukan ke atasan!');
                loadMyCallLists();
            } else {
                btnDraft.disabled = false;
                btnSend.disabled = false;
                closeTargetModal();
                showAlert('Draft target dokter berhasil disimpan!');
                loadMyCallLists();
            }
        } catch (err) {
            btnDraft.disabled = false;
            btnSend.disabled = false;
            errEl.innerText = 'Terjadi kesalahan jaringan atau server.';
            errEl.style.display = 'block';
        }
    }

    async function submitCallList(id) {
        if (!confirm('Ajukan Target Dokter ini ke atasan langsung?')) return;
        try {
            const res = await window.apiFetch(`/api/call-lists/${id}/submit`, { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                showAlert('Target dokter berhasil diajukan untuk persetujuan.');
                loadMyCallLists();
                if (['dm', 'rsm', 'mm'].includes(currentUserRole)) loadReviewCallLists();
            } else {
                showAlert(data.message || 'Gagal mengajukan target dokter.', 'danger');
            }
        } catch (err) {
            showAlert('Terjadi kesalahan koneksi jaringan.', 'danger');
        }
    }

    function formatApprovalEvent(event) {
        const labels = {
            created_draft: 'Draft dibuat',
            submitted: 'Diajukan',
            approved: 'Disetujui',
            rejected: 'Ditolak',
            revised: 'Direvisi',
        };

        return labels[event] || String(event || '-').replaceAll('_', ' ');
    }

    async function openDetailModal(id) {
        document.getElementById('detailContent').innerHTML = '<div style="padding: 1rem 0; color: var(--text-muted);">Memuat detail...</div>';
        document.getElementById('detailModal').style.display = 'flex';

        try {
            const res = await window.apiFetch(`/api/call-lists/${id}`);
            const result = await res.json();
            if (result.success) {
                const cl = result.data;
                document.getElementById('detailPeriodSubtitle').innerText = formatPeriodIndonesian(cl.period_month);

                const statusClass = `status-${cl.status === 'pending_approval' ? 'pending' : cl.status}`;
                const statusLabel = cl.status === 'pending_approval' ? 'Menunggu review' : (cl.status === 'approved' ? 'Disetujui' : 'Draft');
                const doctorCount = cl.doctors.length;

                const historyHtml = cl.approval_history.length
                    ? cl.approval_history.map(h => `
                        <div class="target-detail-history-row">
                            <strong>${escapeHtml(formatApprovalEvent(h.event))}</strong>
                            <span>oleh ${escapeHtml(h.actor_name || 'Sistem')} pada ${new Date(h.created_at).toLocaleString('id-ID')}</span>
                            ${h.reason ? `<div>${escapeHtml(h.reason)}</div>` : ''}
                        </div>
                    `).join('')
                    : '<div class="target-detail-history-row">Belum ada riwayat.</div>';

                const html = `
                    <div class="target-detail-meta">
                        <div>
                            Status
                            <strong><span class="status-badge ${statusClass}">${escapeHtml(statusLabel)}</span></strong>
                        </div>
                        <div>
                            Peninjau
                            <strong>${escapeHtml(cl.assigned_approver ? cl.assigned_approver.name : '-')}</strong>
                        </div>
                        <div>
                            Pengaju
                            <strong>${escapeHtml(cl.owner ? cl.owner.name : '-')}</strong>
                        </div>
                        <div>
                            Jumlah dokter
                            <strong>${doctorCount} dokter</strong>
                        </div>
                    </div>

                    <section>
                        <h4 class="target-detail-section-title">Daftar dokter</h4>
                        <div class="target-detail-list">
                            ${cl.doctors.map(d => `
                                <div class="target-detail-doctor">
                                    <strong>${escapeHtml(d.name)}</strong>
                                    <span>${escapeHtml(d.specialization || 'Umum')} - ${escapeHtml(d.address || '-')}</span>
                                </div>
                            `).join('')}
                        </div>
                    </section>

                    <section>
                        <h4 class="target-detail-section-title">Riwayat</h4>
                        <div class="target-detail-history">
                            ${historyHtml}
                        </div>
                    </section>

                    <div class="target-detail-actions">
                        <button type="button" class="btn btn-outline btn-sm" onclick="closeDetailModal()">Tutup</button>
                    </div>
                `;
                document.getElementById('detailContent').innerHTML = html;
            }
        } catch (err) {
            document.getElementById('detailContent').innerHTML = '<div style="color: var(--danger); padding: 1rem 0;">Gagal memuat detail.</div>';
        }
    }

    function closeDetailModal() {
        document.getElementById('detailModal').style.display = 'none';
    }

    // Manager Actions
    async function loadReviewCallLists() {
        const loadingEl = document.getElementById('loadingReview');
        if (!loadingEl) return;
        loadingEl.innerHTML = window.renderTableSkeleton(3);
        loadingEl.style.display = 'block';
        try {
            const res = await window.apiFetch('/api/call-lists?mode=review');
            const result = await res.json();
            if (loadingEl) loadingEl.style.display = 'none';

            if (result.success) {
                reviewCallLists = result.data;
                const countEl = document.getElementById('reviewCount');
                if (countEl) countEl.innerText = reviewCallLists.length;
                renderReviewGrid();
            }
        } catch (err) {
            if (loadingEl) loadingEl.style.display = 'none';
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
            if (empty) empty.style.display = 'block';
            return;
        }
        if (empty) empty.style.display = 'none';
        if (tableWrap) tableWrap.style.display = 'block';
        tbody.innerHTML = reviewCallLists.map(cl => `
            <tr>
                <td style="white-space: nowrap;">
                    <strong style="color: var(--secondary); font-size: 0.95rem;">${formatPeriodIndonesian(cl.period_month)}</strong>
                </td>
                <td style="white-space: nowrap;">
                    <strong style="color: var(--secondary);">${escapeHtml(cl.owner_name || '-')}</strong>
                </td>
                <td>
                    <strong style="color: var(--secondary);">${cl.doctor_count} Dokter</strong>
                </td>
                <td>
                    <span class="status-badge status-pending">Menunggu Review</span>
                </td>
                <td style="text-align: right; white-space: nowrap;">
                    <div style="display: inline-flex; gap: 0.35rem;">
                        <button class="btn btn-outline btn-sm" onclick="openDetailModal(${cl.id})">Detail</button>
                        <button class="btn btn-primary btn-sm" onclick="approveCallList(${cl.id})">Setujui</button>
                        <button class="btn btn-outline btn-sm" style="color: var(--danger); border-color: #fecdd3;" onclick="openRejectModal(${cl.id})">Tolak</button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    async function approveCallList(id) {
        if (!confirm('Setujui Target Dokter ini?')) return;
        try {
            const res = await window.apiFetch(`/api/call-lists/${id}/approve`, {
                method: 'PATCH',
                body: JSON.stringify({ status: 'approved', reason: 'Disetujui' })
            });
            const data = await res.json();
            if (data.success) {
                showAlert('Target dokter berhasil disetujui!');
                loadReviewCallLists();
                loadManagerSummary();
            } else {
                showAlert(data.message || 'Gagal menyetujui target dokter.', 'danger');
            }
        } catch (err) {
            showAlert('Terjadi kesalahan jaringan.', 'danger');
        }
    }

    function openRejectModal(id) {
        document.getElementById('rejectCallListId').value = id;
        document.getElementById('rejectReasonInput').value = '';
        const errEl = document.getElementById('rejectModalError');
        if (errEl) errEl.style.display = 'none';
        document.getElementById('rejectModal').style.display = 'flex';
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').style.display = 'none';
    }

    async function handleRejectSubmit(e) {
        e.preventDefault();
        const id = document.getElementById('rejectCallListId').value;
        const reason = document.getElementById('rejectReasonInput').value.trim();
        const errEl = document.getElementById('rejectModalError');

        if (!reason) {
            errEl.innerText = 'Alasan penolakan wajib diisi.';
            errEl.style.display = 'block';
            return;
        }

        try {
            const res = await window.apiFetch(`/api/call-lists/${id}/approve`, {
                method: 'PATCH',
                body: JSON.stringify({ status: 'rejected', reason: reason })
            });
            const data = await res.json();
            if (data.success) {
                closeRejectModal();
                showAlert('Target dokter ditolak dan dikembalikan untuk direvisi.');
                loadReviewCallLists();
                loadManagerSummary();
            } else {
                errEl.innerText = data.message || 'Gagal menolak target.';
                errEl.style.display = 'block';
            }
        } catch (err) {
            errEl.innerText = 'Terjadi kesalahan jaringan.';
            errEl.style.display = 'block';
        }
    }

    async function loadManagerSummary() {
        const monthInput = document.getElementById('summaryMonthSelect');
        const card = document.getElementById('monitoringSummaryCard');
        if (!card) return;

        let selectedMonth = monthInput.value;
        if (!selectedMonth) {
            const now = new Date();
            const yyyy = now.getFullYear();
            const mm = String(now.getMonth() + 1).padStart(2, '0');
            selectedMonth = `${yyyy}-${mm}`;
            monthInput.value = selectedMonth;
        }

        try {
            const res = await window.apiFetch(`/api/call-lists/summary?period_month=${selectedMonth}`);
            const result = await res.json();
            if (result.success) {
                card.style.display = 'block';
                const data = result.data;
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
                            statusText = 'Menunggu Review';
                        } else if (sub.status === 'draft') {
                            statusTheme = 'status-draft';
                            statusText = 'Perlu Revisi';
                        }

                        const actionBtn = sub.call_list_id ? `
                            <button type="button" class="btn btn-outline btn-sm" onclick="openDetailModal(${sub.call_list_id})" style="padding: 0.25rem 0.65rem; font-size: 0.775rem;">
                                Lihat Target
                            </button>
                        ` : `
                            <span style="font-size: 0.75rem; color: #94a3b8; font-style: italic;">Belum Ada Target</span>
                        `;

                        return `
                            <div class="team-roster-item">
                                <div style="display: flex; align-items: center; gap: 0.45rem;">
                                    <strong style="font-size: 0.875rem; color: var(--secondary);">${escapeHtml(sub.name)}</strong>
                                    <span class="role-pill role-${sub.role}">${sub.role.toUpperCase()}</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <div style="font-size: 0.825rem; color: var(--secondary);">
                                        <strong class="tabular-nums">${sub.doctor_count}</strong> Dokter
                                    </div>
                                    <span class="status-badge ${statusTheme}">${statusText}</span>
                                    ${actionBtn}
                                </div>
                            </div>
                        `;
                    }).join('');
                } else {
                    listContainer.innerHTML = '<div style="padding: 0.75rem; text-align: center; color: var(--text-muted); font-size: 0.8rem;">Tidak ada anggota tim terdaftar.</div>';
                }
            }
        } catch (err) {
            console.error('Gagal mengambil ringkasan monitoring manajerial', err);
        }
    }
</script>
@endpush
@endsection
