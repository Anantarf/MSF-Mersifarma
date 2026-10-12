@extends('layouts.app')

@section('title', 'Target Dokter')

@section('content')
<div class="target-page" data-call-lists-page data-current-user-role="{{ auth()->user()->role }}">
    <!-- Page Header -->
    <div class="target-page-head">
        <div>
            <h1 class="target-page-title">Target Dokter</h1>
            <p class="target-page-subtitle">Daftar dokter sasaran dan komitmen kunjungan bulanan.</p>
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
                        <h4 class="target-section-title">Daftar Dokter Sasaran</h4>
                        <span id="spotlightDoctorCount" style="display: none;">0</span>
                        <p class="target-small-muted">Komitmen dokter sasaran untuk periode ini.</p>
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
    @endif
</div>

<!-- ================= MODAL: PENYUSUNAN TARGET DOKTER (CREATE / EDIT) ================= -->
<div id="targetModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card target-form-modal">
        <div class="target-detail-head">
            <div>
                <h3 id="targetModalTitle" class="target-detail-title">Susun Target Dokter</h3>
                <span id="targetModalSubtitle" class="target-detail-subtitle">Pilih dokter sasaran dari Master Customer List</span>
            </div>
            <button type="button" class="close-btn" data-action="close-target">&times;</button>
        </div>

        <div id="targetModalError" style="display: none;" class="alert alert-danger"></div>

        <form id="targetForm">
            <input type="hidden" id="editingCallListId" value="">

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

            <!-- 4. Footer Modal -->
            <div class="target-modal-foot">
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
        <div class="target-detail-head">
            <div>
                <h3 id="detailTitle" class="target-detail-title">Detail Target Dokter</h3>
                <span id="detailPeriodSubtitle" class="target-detail-subtitle">-</span>
            </div>
            <button type="button" class="close-btn" data-action="close-detail">&times;</button>
        </div>

        <div id="detailContent" class="target-detail-content">
            <div class="target-loading-text">Memuat detail...</div>
        </div>
    </div>
</div>

<!-- ================= MODAL: TOLAK PENGAJUAN (REJECT MODAL) ================= -->
<div id="rejectModal" class="modal-backdrop target-modal-priority" style="display: none;">
    <div class="modal-card target-reject-modal">
        <h3 class="target-reject-title">Tolak Target Dokter</h3>
        <p class="target-reject-copy">
            Target akan dikembalikan ke status <strong>Perlu Revisi (Draft)</strong> agar bawahan dapat menyesuaikan daftar dokter sasaran.
        </p>
        <div id="rejectModalError" style="display: none;" class="alert alert-danger"></div>
        <form id="rejectForm">
            <input type="hidden" id="rejectCallListId">
            <div class="target-field">
                <label for="rejectReasonInput" class="target-field-label">
                    Alasan Penolakan (Wajib Diisi):
                </label>
                <textarea id="rejectReasonInput" class="target-input target-textarea" rows="3" required placeholder="Contoh: Tambahkan 3 dokter spesialis anak di RSUD wilayah distrik."></textarea>
            </div>
            <div class="target-dialog-actions">
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
    <div class="modal-card target-confirm-modal">
        <h3 id="confirmModalTitle" class="target-confirm-title">Konfirmasi Aksi</h3>
        <p id="confirmModalMessage" class="target-confirm-copy">Lanjutkan aksi ini?</p>
        <div class="target-dialog-actions">
            <button type="button" class="btn btn-outline" data-action="close-confirm">Batal</button>
            <button type="button" id="btnConfirmAction" class="btn btn-primary" data-action="confirm-action">
                Lanjutkan
            </button>
        </div>
    </div>
</div>

@push('styles')
@vite('resources/css/pages/call-lists.css')
@endpush

@push('scripts')
@vite('resources/js/pages/call-lists.js')
@endpush
@endsection
