@extends('layouts.app')

@section('title', 'Target Dokter')

@section('content')
<div class="target-page" data-call-lists-page data-current-user-role="{{ auth()->user()->role }}" data-current-user-district="{{ auth()->user()->district ?? '' }}">
    <!-- Page Header -->
    <div class="target-page-head">
        <div>
            <h1 class="target-page-title">Target Dokter</h1>
            <p class="target-page-subtitle"><strong>Call List Bulanan</strong> : Daftar target dokter dan komitmen kunjungan bulanan.</p>
        </div>
        <div>
            @if(auth()->user()->role === 'mr')
            <button type="button" class="btn btn-primary" data-action="open-create">
                + Susun Target Dokter
            </button>
            @endif
        </div>
    </div>

    <!-- Tabs Navigation (Khusus Manajer) -->
    @if(in_array(auth()->user()->role, ['dm', 'rsm', 'mm']))
    <div class="target-tabs">
        <button id="tabReview" class="tab-btn active" data-action="switch-tab" data-tab="review">
            Persetujuan Masuk (<span id="reviewCount" class="tabular-nums">0</span>)
        </button>
        <button id="tabCampaigns" class="tab-btn" data-action="switch-tab" data-tab="campaigns">
            Program Rekomendasi
        </button>
        <button id="tabMyLists" class="tab-btn" data-action="switch-tab" data-tab="myLists">
            Target Saya (<span id="myListCount" class="tabular-nums">0</span>)
        </button>
    </div>
    @endif

    <!-- Alert Banner -->
    <div id="statusAlert" style="display: none;" class="alert"></div>

    <!-- ================= TAB 1: TARGET SAYA (MR VIEW) ================= -->
    <div id="myListsContainer" class="target-list-stack" style="display: {{ auth()->user()->role === 'mr' ? 'flex' : 'none' }};">
        <div id="loadingMyLists" class="target-loader"></div>

        <!-- Kartu Target Periode Berjalan / Utama -->
        <div id="currentMonthSpotlight" class="target-card" style="display: none;">
            <!-- Header Kartu Target -->
            <div class="target-card-head">
                <div>
                    <span class="target-kicker">Target Periode</span>
                    <div class="target-period-title-row">
                        <h2 id="spotlightPeriod" class="target-period-title">-</h2>
                        <span id="spotlightStatusBadge"></span>
                    </div>
                    <div class="target-muted-line">
                        Atasan Peninjau: <strong id="spotlightApprover">-</strong>
                    </div>
                </div>

                <div id="spotlightActionBtns" class="target-actions"></div>
            </div>

            <!-- Pesan Alasan Revisi dari DM (Jika Ditolak) -->
            <div id="spotlightRejectionBox" class="target-rejection-box" style="display: none;">
                <div class="target-rejection-title">
                    Catatan Revisi dari Atasan:
                </div>
                <div id="spotlightRejectionReason" class="target-rejection-reason">-</div>
            </div>

            <!-- Daftar Dokter Sasaran (Langsung tampil di dalam kartu) -->
            <div class="target-card-body">
                <div class="target-section-head">
                    <div>
                        <h4 class="target-section-title">Daftar Target Dokter</h4>
                        <span id="spotlightDoctorCount" style="display: none;">0</span>
                        <p class="target-small-muted">Komitmen target dokter untuk periode ini.</p>
                    </div>
                    <div id="spotlightQuickAddBtnWrap"></div>
                </div>

                <!-- Tabel Dokter Sasaran -->
                <div class="table-responsive target-table-shell">
                    <table class="enterprise-table target-table">
                        <thead>
                            <tr class="target-table-head-row">
                                <th class="target-col-number">No</th>
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
        <div id="myListsTableWrap" class="target-card" style="display: none;">
            <div class="target-card-subhead">
                <h3 class="target-section-title">Riwayat Periode Sebelumnya</h3>
                <p class="target-section-subtitle">Daftar target dokter dari bulan-bulan sebelumnya</p>
            </div>
            <div class="table-responsive target-table-flush">
                <table class="enterprise-table target-table">
                    <thead>
                        <tr class="target-table-head-row">
                            <th class="target-cell-left">Periode</th>
                            <th>Target Dokter</th>
                            <th>Status</th>
                            <th>Atasan Peninjau</th>
                            <th class="target-cell-action">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="myListsTableBody"></tbody>
                </table>
            </div>
        </div>

        <!-- Empty State Target -->
        <div id="emptyMyLists" class="target-empty" style="display: none;">
            <h4>Belum Ada Target Dokter</h4>
            <p>
                Mulai susun komitmen target dokter bulanan Anda dari Master Dokter untuk diajukan ke atasan langsung.
            </p>
            @if(auth()->user()->role === 'mr')
            <button type="button" class="btn btn-primary" data-action="open-create">+ Susun Target Dokter</button>
            @endif
        </div>
    </div>

    <!-- ================= TAB 2: PERSETUJUAN MASUK (MANAGER VIEW) ================= -->
    @if(in_array(auth()->user()->role, ['dm', 'rsm', 'mm']))
    <div id="reviewContainer" class="target-list-stack" style="display: {{ auth()->user()->role !== 'mr' ? 'flex' : 'none' }};">
        <!-- Widget Ringkasan Monitoring Wilayah -->
        <div id="monitoringSummaryCard" class="target-monitoring" style="display: none;">
            <div class="target-monitoring-head">
                <div>
                    <h4 class="target-monitoring-title">
                        Monitoring Pengajuan Wilayah
                    </h4>
                    <p class="target-section-subtitle">
                        Rekapitulasi target dokter dari tim bawahan langsung pada periode terpilih.
                    </p>
                </div>
                <div class="target-filter-inline">
                    <label for="summaryMonthSelect" class="target-filter-label">Periode:</label>
                    <select id="summaryMonthSelect" class="target-input target-nowrap">
                        @php
                            $indonesianMonths = [
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                            ];
                            $currYear = now()->year;
                            $currMonth = now()->month;
                        @endphp
                        @foreach([$currYear - 1, $currYear, $currYear + 1] as $yr)
                            @foreach($indonesianMonths as $mNum => $mName)
                                @php $val = sprintf('%04d-%02d', $yr, $mNum); @endphp
                                <option value="{{ $val }}" {{ ($yr === $currYear && $mNum === $currMonth) ? 'selected' : '' }}>
                                    {{ $mName }} {{ $yr }}
                                </option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Metric Strip -->
            <div class="target-metric-grid">
                <div class="target-metric">
                    <div class="target-metric-label">Tim Bawahan</div>
                    <div id="statTotalSubordinates" class="target-metric-value tabular-nums">0</div>
                </div>
                <div class="target-metric">
                    <div class="target-metric-label">Target Dokter</div>
                    <div id="statTotalDoctors" class="target-metric-value tabular-nums">0</div>
                </div>
                <div class="target-metric target-metric-success">
                    <div class="target-metric-label">Disetujui</div>
                    <div id="statApproved" class="target-metric-value tabular-nums">0</div>
                </div>
                <div class="target-metric target-metric-warning">
                    <div class="target-metric-label">Menunggu Persetujuan</div>
                    <div id="statPending" class="target-metric-value tabular-nums">0</div>
                </div>
            </div>

            <!-- Daftar Anggota Tim -->
            <div>
                <div class="target-roster-label">Daftar Anggota Tim & Status:</div>
                <div id="subordinatesListContainer" class="team-roster-list"></div>
            </div>
        </div>

        <div id="loadingReview" class="target-loader"></div>
        <div id="reviewTableWrap" class="table-responsive" style="display: none;">
            <table class="enterprise-table">
                <thead>
                    <tr>
                        <th>Periode</th>
                        <th>Bawahan Pengaju</th>
                        <th>Target Dokter</th>
                        <th>Status</th>
                        <th class="target-cell-action">Aksi</th>
                    </tr>
                </thead>
                <tbody id="reviewTableBody"></tbody>
            </table>
        </div>
        <div id="emptyReview" class="target-empty" style="display: none;">
            <h4>Semua Pengajuan Telah Ditinjau</h4>
            <p>
                Tidak ada pengajuan bawahan yang sedang menunggu persetujuan Anda saat ini.
            </p>
        </div>
    </div>

    <!-- ================= TAB 3: PROGRAM REKOMENDASI MANAJERIAL (P1) ================= -->
    <div id="campaignsContainer" class="target-list-stack" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
            <div>
                <h3 style="font-size: 1.05rem; color: var(--secondary); margin-bottom: 0.2rem;">Program Rekomendasi Distrik & Fokus Produk</h3>
                <p style="font-size: 0.8rem; color: var(--text-muted);">Arahan strategis dokter target dan produk promosi (durasi 1–3 bulan).</p>
            </div>
            @if(in_array(auth()->user()->role, ['dm', 'rsm']))
            <button type="button" class="btn btn-primary btn-sm" data-action="open-create-campaign">
                + Buat Program Baru
            </button>
            @endif
        </div>

        <div id="loadingCampaigns" class="target-loader" style="display: none;"></div>

        <!-- Executive Summary Section (Khusus RSM & MM) -->
        <div id="executiveSummarySection" style="display: none; margin-bottom: 1.25rem;">
            <div id="executiveMetricGrid" class="metric-strip-grid"></div>
            <div id="districtBreakdownWrap" class="target-card" style="margin-top: 1rem;">
                <div class="target-card-subhead">
                    <div class="target-title-with-badge">
                        <i class="bi bi-bar-chart-line-fill" style="color: var(--primary); font-size: 1rem;"></i>
                        <h4 class="target-section-title">Ringkasan Performa Antar-Distrik</h4>
                        <span class="badge badge-info" style="font-size: 0.7rem; font-weight: 600;">Executive View</span>
                    </div>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.2rem 0 0;">
                        Rekapitulasi capaian dokter target, tingkat jangkauan (reach %), dan total detailing obat fokus di tiap distrik.
                    </p>
                </div>
                <div class="table-responsive" style="border: none; border-radius: 0; box-shadow: none;">
                    <table class="enterprise-table">
                        <thead>
                            <tr>
                                <th>Distrik</th>
                                <th>Penanggung Jawab (DM)</th>
                                <th style="text-align: right;">Target Dokter</th>
                                <th>Terkunjungi (Reach %)</th>
                                <th style="text-align: right;">Detailing Obat Fokus</th>
                            </tr>
                        </thead>
                        <tbody id="districtBreakdownTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Stack Kartu Kampanye Aktif -->
        <div id="campaignCardsStack" class="target-list-stack"></div>

        <div id="emptyCampaigns" class="target-empty" style="display: none;">
            <h4>Belum Ada Program Rekomendasi</h4>
            <p>Belum ada program kampanye rekomendasi aktif untuk wilayah ini.</p>
        </div>
    </div>
    @endif
</div>

<!-- ================= MODAL: PENYUSUNAN TARGET DOKTER (CREATE / EDIT) ================= -->
<div id="targetModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card target-form-modal">
        <div class="modal-sheet-head">
            <div>
                <h3 id="targetModalTitle" class="modal-sheet-title">Susun Target Dokter</h3>
                <span id="targetModalSubtitle" class="modal-sheet-subtitle">Pilih target dokter dari Master Customer List</span>
            </div>
            <button type="button" class="close-btn" data-action="close-target" aria-label="Tutup">&times;</button>
        </div>

        <form id="targetForm" class="modal-sheet-form">
            <input type="hidden" id="editingCallListId" value="">

            <div class="modal-sheet-body">
                <div id="targetModalError" style="display: none;" class="alert alert-danger"></div>

                <!-- 1. Pilihan Periode Bulan (100% Bahasa Indonesia) -->
                <div class="target-field">
                    <label class="target-field-label">
                        Periode Bulan:
                    </label>
                    <div class="target-period-row">
                        <select id="periodMonthSelect" class="target-input target-period-select" aria-label="Bulan Periode">
                            <option value="01">Januari</option>
                            <option value="02">Februari</option>
                            <option value="03">Maret</option>
                            <option value="04">April</option>
                            <option value="05">Mei</option>
                            <option value="06">Juni</option>
                            <option value="07">Juli</option>
                            <option value="08">Agustus</option>
                            <option value="09">September</option>
                            <option value="10">Oktober</option>
                            <option value="11">November</option>
                            <option value="12">Desember</option>
                        </select>
                        <select id="periodYearSelect" class="target-input target-period-year" aria-label="Tahun Periode">
                            <option value="2025">2025</option>
                            <option value="2026">2026</option>
                            <option value="2027">2027</option>
                            <option value="2028">2028</option>
                        </select>
                    </div>
                    <input type="hidden" id="periodMonthInput" required>
                </div>

                <!-- 2. Pencarian & Filter Dokter -->
                <div class="target-field">
                    <div class="target-picker-head">
                        <label class="target-field-label">
                            Pilih Dokter Target (Minimal 1 Dokter):
                        </label>
                        <span class="target-selected-count">
                            <span id="selectedDoctorCount">0</span> Dokter Terpilih
                        </span>
                    </div>

                    <div class="target-search-row">
                        <input type="text" id="doctorSearchInput" class="target-input" placeholder="Cari nama dokter, spesialisasi, atau rumah sakit...">
                        <button type="button" id="btnFilterToggleSelected" class="btn btn-outline btn-sm target-nowrap" data-action="toggle-selected-filter">
                            Tampilkan Terpilih
                        </button>
                    </div>
                </div>

                <!-- 3. Daftar Dokter Lega & Interaktif (Tampilan Halaman Bersih Tanpa Scrollbar Ganda) -->
                <div id="doctorPickerList" class="target-doctor-picker">
                    <span class="target-picker-loading">Memuat daftar dokter...</span>
                </div>

                <!-- Kontrol Navigasi Halaman Dokter -->
                <div id="doctorPickerPagination" class="target-picker-pagination" style="display: none;">
                    <button type="button" id="btnPickerPrevPage" class="btn btn-outline btn-sm" data-action="picker-prev-page">
                        &larr; Sebelumnya
                    </button>
                    <span id="pickerPaginationInfo" class="target-picker-page-info">Halaman 1 dari 1</span>
                    <button type="button" id="btnPickerNextPage" class="btn btn-outline btn-sm" data-action="picker-next-page">
                        Berikutnya &rarr;
                    </button>
                </div>
            </div>

            <!-- 4. Footer Modal (STICKY AT BOTTOM) -->
            <div class="modal-sheet-foot target-modal-foot">
                <span class="target-footer-count">
                    Total: <strong id="footerCountText" class="tabular-nums">0</strong> dokter
                </span>
                <div class="target-actions">
                    <button type="button" class="btn btn-outline" data-action="close-target">Batal</button>
                    <button type="submit" id="btnSubmitDraft" class="btn btn-outline target-primary-outline">
                        Simpan Draft
                    </button>
                    <button type="button" id="btnSubmitAndSend" class="btn btn-primary" data-action="save-and-submit">
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
        <div class="modal-sheet-head">
            <div>
                <h3 id="detailTitle" class="modal-sheet-title">Detail Target Dokter</h3>
                <span id="detailPeriodSubtitle" class="modal-sheet-subtitle">-</span>
            </div>
            <button type="button" class="close-btn" data-action="close-detail" aria-label="Tutup">&times;</button>
        </div>

        <div class="modal-sheet-body">
            <div id="detailContent" class="target-detail-content" style="padding: 0;">
                <div class="target-loading-text">Memuat detail...</div>
            </div>
        </div>

        <div class="modal-sheet-foot">
            <button type="button" class="btn btn-outline" data-action="close-detail">Tutup</button>
        </div>
    </div>
</div>

<!-- ================= MODAL: TOLAK PENGAJUAN (REJECT MODAL) ================= -->
<div id="rejectModal" class="modal-backdrop target-modal-priority" style="display: none;">
    <div class="modal-card target-reject-modal" style="max-width: 480px;">
        <div class="modal-sheet-head">
            <div>
                <h3 class="modal-sheet-title" style="color: var(--danger);">Tolak Target Dokter</h3>
                <span class="modal-sheet-subtitle">Kembalikan ke status Perlu Revisi (Draft)</span>
            </div>
            <button type="button" class="close-btn" data-action="close-reject" aria-label="Tutup">&times;</button>
        </div>

        <form id="rejectForm" class="modal-sheet-form">
            <input type="hidden" id="rejectCallListId">
            <div class="modal-sheet-body">
                <div id="rejectModalError" style="display: none;" class="alert alert-danger"></div>
                <p class="target-reject-copy" style="margin: 0;">
                    Target akan dikembalikan ke status <strong>Perlu Revisi (Draft)</strong> agar bawahan dapat menyesuaikan daftar target dokter.
                </p>
                <div class="target-field">
                    <label for="rejectReasonInput" class="target-field-label">
                        Alasan Penolakan (Wajib Diisi):
                    </label>
                    <textarea id="rejectReasonInput" class="target-input target-textarea" rows="3" required placeholder="Contoh: Tambahkan 3 dokter spesialis anak di RSUD wilayah distrik."></textarea>
                </div>
            </div>

            <div class="modal-sheet-foot">
                <button type="button" class="btn btn-outline" data-action="close-reject">Batal</button>
                <button type="submit" id="btnConfirmReject" class="btn target-danger-button">
                    Tolak & Kembalikan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL: KONFIRMASI AKSI ================= -->
<div id="confirmModal" class="modal-backdrop target-modal-priority" style="display: none;">
    <div class="modal-card target-confirm-modal" style="max-width: 420px;">
        <div class="modal-sheet-head">
            <div>
                <h3 id="confirmModalTitle" class="modal-sheet-title">Konfirmasi Aksi</h3>
            </div>
            <button type="button" class="close-btn" data-action="close-confirm" aria-label="Tutup">&times;</button>
        </div>

        <div class="modal-sheet-body">
            <p id="confirmModalMessage" class="target-confirm-copy" style="margin: 0; line-height: 1.5;">Lanjutkan aksi ini?</p>
        </div>

        <div class="modal-sheet-foot">
            <button type="button" class="btn btn-outline" data-action="close-confirm">Batal</button>
            <button type="button" id="btnConfirmAction" class="btn btn-primary" data-action="confirm-action">Lanjutkan</button>
        </div>
    </div>
</div>

<!-- ================= MODAL: BUAT PROGRAM REKOMENDASI (DM/RSM) ================= -->
<div id="createCampaignModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card target-form-modal" style="max-width: 650px;">
        <div class="modal-sheet-head">
            <div>
                <h3 id="campaignModalHeading" class="modal-sheet-title">Program Rekomendasi</h3>
                <span id="campaignModalSubheading" class="modal-sheet-subtitle">Tentukan arahan strategis obat fokus dan dokter target</span>
            </div>
            <button type="button" class="close-btn" data-action="close-create-campaign" aria-label="Tutup">&times;</button>
        </div>

        <form id="createCampaignForm" class="modal-sheet-form">
            <div class="modal-sheet-body">
                <div id="createCampaignError" style="display: none;" class="alert alert-danger"></div>

                <!-- Section 1: Informasi & Lingkup Program -->
                <div class="modal-section-card">
                    <div id="campaignDistrictBadgeWrap" class="campaign-scope-banner">
                        <span>Wilayah Cakupan Program:</span>
                        <strong id="campaignDistrictLabel" style="color: var(--secondary); font-size: 0.85rem;">-</strong>
                    </div>

                    <div id="campaignGuidanceLockBanner" class="guidance-lock-banner" style="display: none;">
                        <i class="bi bi-shield-lock-fill" style="font-size: 1rem; color: #0284c7; flex-shrink: 0; margin-top: 1px;"></i>
                        <div>
                            <strong>Terkunci Sesuai Arahan RSM:</strong>
                            <span id="campaignGuidanceLockDesc">Durasi dan obat fokus promosi telah disesuaikan otomatis dengan arahan regional.</span>
                        </div>
                    </div>

                    <!-- Pilihan Arahan RSM (Khusus DM) -->
                    <div id="campaignRsmGuidanceField" class="target-field" style="display: none; margin-bottom: 0.85rem;">
                        <label class="target-field-label" style="font-weight: 600; color: var(--secondary);">Pilih Program Arahan dari RSM (Opsional):</label>
                        <select id="campaignRsmGuidanceSelect" class="target-input">
                            <option value="">-- Buat Usulan Distrik Mandiri --</option>
                        </select>
                        <small style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 3px;">
                            Memilih arahan RSM akan otomatis mengunci durasi dan obat fokus promosi.
                        </small>
                    </div>

                    <div class="target-field" style="margin-bottom: 0.85rem;">
                        <label class="target-field-label" style="font-weight: 600; color: var(--secondary);">Judul Program Rekomendasi:</label>
                        <input type="text" id="campaignTitleInput" class="target-input" required placeholder="Contoh: Fokus Pediatri Q4 Jaksel">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div class="target-field" style="margin: 0;">
                            <label class="target-field-label" style="font-weight: 600; color: var(--secondary);">Durasi Waktu:</label>
                            <select id="campaignDurationSelect" class="target-input" required>
                                <option value="1">1 Bulan</option>
                                <option value="2">2 Bulan</option>
                                <option value="3" selected>3 Bulan (Maksimal)</option>
                            </select>
                        </div>
                        <div class="target-field" style="margin: 0;">
                            <label class="target-field-label" style="font-weight: 600; color: var(--secondary);">Target Spesialisasi:</label>
                            <select id="campaignSpecSelect" class="target-input">
                                <option value="">-- Semua Spesialisasi --</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Fokus Produk Promosi -->
                <div class="modal-section-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem;">
                        <div>
                            <label class="target-field-label" style="margin: 0; font-weight: 700; color: var(--secondary); font-size: 0.88rem;">
                                Obat Fokus Promosi
                            </label>
                            <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">
                                Pilih 1 hingga maksimal 5 obat untuk fokus detailing
                            </span>
                        </div>
                        <span class="badge badge-info" style="font-size: 0.75rem; font-weight: 600;">
                            <span id="campaignSelectedProdCount">0</span>/5 Terpilih
                        </span>
                    </div>

                    <div id="campaignProductCheckboxes" class="product-chips-grid">
                        <span style="font-size: 0.8rem; color: var(--text-muted); grid-column: 1 / -1; padding: 0.5rem; text-align: center;">
                            Memuat produk master...
                        </span>
                    </div>
                </div>

                <!-- Section 3: Target Dokter Distrik (Khusus DM) -->
                <div id="campaignDoctorSection" class="modal-section-card" style="display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem;">
                        <div>
                            <label class="target-field-label" style="margin: 0; font-weight: 700; color: var(--secondary); font-size: 0.88rem;">
                                Target Dokter di Distrik
                            </label>
                            <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">
                                Pilih dokter sasaran rekomendasi di wilayah distrik Anda
                            </span>
                        </div>
                        <span class="badge badge-success" style="font-size: 0.75rem; font-weight: 600;">
                            <span id="campaignSelectedDocCount">0</span> Dokter Terpilih
                        </span>
                    </div>

                    <!-- Toolbar Pencarian & Aksi Cepat -->
                    <div class="doctor-picker-toolbar">
                        <div style="position: relative; flex: 1;">
                            <i class="bi bi-search" style="position: absolute; left: 0.65rem; top: 50%; transform: translateY(-50%); font-size: 0.75rem; color: var(--text-muted);"></i>
                            <input type="text" id="campaignDoctorSearchInput" class="target-input" style="padding-left: 2rem; font-size: 0.8rem; height: 32px;" placeholder="Cari nama atau spesialisasi dokter...">
                        </div>
                        <div style="display: flex; gap: 0.35rem;">
                            <button type="button" class="btn btn-outline btn-sm" id="btnSelectAllCampaignDocs" data-action="select-all-campaign-docs" style="font-size: 0.72rem; padding: 0.2rem 0.55rem;">Pilih Semua</button>
                            <button type="button" class="btn btn-outline btn-sm" id="btnClearAllCampaignDocs" data-action="clear-all-campaign-docs" style="font-size: 0.72rem; padding: 0.2rem 0.55rem;">Batal</button>
                        </div>
                    </div>

                    <div id="campaignDoctorCheckboxes" class="doctor-chips-list">
                        <span style="font-size: 0.8rem; color: var(--text-muted); padding: 0.5rem; text-align: center;">
                            Memuat dokter distrik...
                        </span>
                    </div>
                </div>

                <!-- Section 4: Catatan Strategis (Opsional) -->
                <div class="modal-section-card" style="margin-bottom: 0;">
                    <div class="target-field" style="margin: 0;">
                        <label class="target-field-label" style="font-weight: 600; color: var(--secondary);">Catatan Tambahan (Opsional):</label>
                        <textarea id="campaignNotesInput" class="target-input" rows="2" placeholder="Arahan strategi detailing untuk MR..."></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-sheet-foot">
                <button type="button" class="btn btn-outline" data-action="close-create-campaign">Batal</button>
                <button type="submit" id="btnSubmitCampaign" class="btn btn-primary">
                    Simpan & Ajukan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL: DAFTAR DOKTER BELUM DIKUNJUNGI ================= -->
<div id="unvisitedDoctorsModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card" style="max-width: 540px;">
        <div class="modal-sheet-head">
            <div>
                <h3 id="unvisitedModalTitle" class="modal-sheet-title">Dokter Belum Dikunjungi</h3>
                <span class="modal-sheet-subtitle">Daftar dokter target rekomendasi yang belum memiliki Call Actual</span>
            </div>
            <button type="button" class="close-btn" data-action="close-unvisited-modal" aria-label="Tutup">&times;</button>
        </div>

        <div class="modal-sheet-body">
            <div id="unvisitedDoctorsListWrap" style="overflow-y: auto;">
                <ul id="unvisitedDoctorsList" style="list-style: none; padding: 0; display: flex; flex-direction: column; gap: 0.5rem; margin: 0;"></ul>
            </div>
        </div>

        <div class="modal-sheet-foot">
            <button type="button" class="btn btn-primary btn-sm" data-action="close-unvisited-modal">Tutup</button>
        </div>
    </div>
</div>

<!-- ================= MODAL: TOLAK PROGRAM REKOMENDASI ================= -->
<div id="rejectCampaignModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card" style="max-width: 440px;">
        <div class="modal-sheet-head">
            <div>
                <h3 class="modal-sheet-title" style="color: var(--danger);">Tolak Program Rekomendasi</h3>
                <span class="modal-sheet-subtitle">Berikan catatan alasan penolakan revisi</span>
            </div>
            <button type="button" class="close-btn" data-action="close-reject-campaign" aria-label="Tutup">&times;</button>
        </div>

        <form id="rejectCampaignForm" class="modal-sheet-form">
            <input type="hidden" id="rejectCampaignId" value="">
            <div class="modal-sheet-body">
                <p style="font-size: 0.825rem; color: var(--text-muted); margin: 0;">
                    Berikan catatan alasan penolakan agar pembuat program dapat merevisi target atau fokus produk.
                </p>
                <div class="target-field">
                    <label class="target-field-label">Alasan Penolakan:</label>
                    <textarea id="rejectCampaignReasonInput" class="target-input target-textarea" rows="3" required placeholder="Contoh: Sesuaikan fokus obat dengan ketersediaan stok regional..."></textarea>
                </div>
            </div>

            <div class="modal-sheet-foot">
                <button type="button" class="btn btn-outline" data-action="close-reject-campaign">Batal</button>
                <button type="submit" class="btn target-danger-button">Tolak Program</button>
            </div>
        </form>
    </div>
</div>

@push('styles')
@vite('resources/css/pages/call-lists.css')
@endpush

@push('scripts')
@vite('resources/js/pages/call-lists.js')
@endpush
@endsection
