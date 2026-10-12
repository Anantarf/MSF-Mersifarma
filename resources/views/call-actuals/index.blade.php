@extends('layouts.app')

@section('title', 'Pelaksanaan Kunjungan')

@section('content')
<div class="actual-page" data-call-actuals-page data-current-user-role="{{ auth()->user()->role }}" data-current-user-id="{{ auth()->id() }}" data-call-plans-url="{{ route('call-plans.index') }}">

    <div class="actual-page-head">
        <div>
            <h1 class="actual-page-title">Pelaksanaan Kunjungan</h1>
            @if(auth()->user()->role === 'mr')
            <p class="actual-page-subtitle" id="actualTodayLabel"><strong>Call Actual</strong> : Catatan kunjungan dokter dan pelaporan bukti lapangan.</p>
            @else
            <p class="actual-page-subtitle"><strong>Call Actual</strong> : Rekapitulasi pelaksanaan kunjungan dan audit bukti tim.</p>
            @endif
        </div>
    </div>

    <div id="actualStatusAlert" style="display: none;" class="alert"></div>

    @if(auth()->user()->role === 'mr')

    <section id="activeSessionSection" class="ca-session" style="display: none;" aria-live="polite">
        <div class="ca-session-top">
            <span id="activeSessionBadge" class="status-badge status-approved">Sedang Berlangsung</span>
            <span class="ca-chip">Sesi Kunjungan</span>
        </div>
        <h3 id="activeSessionDoctorName" class="ca-card-title">-</h3>
        <p id="activeSessionDoctorDetail" class="ca-card-sub">-</p>
        <p class="ca-session-time">
            <span class="text-muted">Check-in server</span> <strong id="activeSessionCheckInTime" class="tabular-nums">-</strong>
            <span id="activeSessionCheckOutWrap" style="display: none;">
                <span class="text-muted">&middot; Check-out server</span> <strong id="activeSessionCheckOutTime" class="tabular-nums">-</strong>
            </span>
        </p>
        <div class="ca-actions">
            <button type="button" id="btnSessionCheckout" class="ca-btn ca-btn-primary" data-action="session-checkout">
                Akhiri Kunjungan
            </button>
            <button type="button" id="btnSessionContinue" class="ca-btn ca-btn-primary" style="display: none;" data-action="open-session-actual">
                Lanjutkan Laporan
            </button>
            <button type="button" class="ca-btn ca-btn-danger" data-action="open-cancel-session">
                Batalkan Sesi
            </button>
        </div>
    </section>

    <section class="ca-section" aria-labelledby="todayPlansHeading">
        <div class="ca-section-head">
            <h2 id="todayPlansHeading" class="section-title">Jadwal Hari Ini</h2>
            <p class="section-note">Mulai dari jadwal yang sudah disetujui.</p>
        </div>
        <div id="todayPlansLoading" class="loading-slot"></div>
        <div id="todayPlansList" class="ca-card-list" style="display: none;"></div>
        <div id="todayPlansEmpty" class="empty-state empty-state-actionable" style="display: none;">
            <div class="empty-state-icon" aria-hidden="true"><i class="bi bi-calendar2-check"></i></div>
            <h4>Belum ada jadwal hari ini</h4>
            <p>Mulai dari jadwal bila ada. Jika dokter meminta kunjungan mendadak, catat sebagai kunjungan di luar rencana.</p>
            <button type="button" class="ca-btn ca-btn-primary ca-empty-action" data-action="open-unplanned-actual">Catat Kunjungan</button>
        </div>
        <button type="button" class="ca-add-visit" data-action="open-unplanned-actual">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Kunjungan di Luar Rencana</span>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
        </button>
    </section>

    <section id="expiredPlansSection" class="ca-section" style="display: none;" aria-labelledby="expiredPlansHeading">
        <div class="ca-section-head">
            <h2 id="expiredPlansHeading" class="section-title">Jadwal Terlewat</h2>
            <p class="section-note">Jadwal yang sudah kedaluwarsa tidak dapat diisi sebagai laporan terencana.</p>
        </div>
        <div id="expiredPlansList" class="ca-card-list"></div>
    </section>

    <section class="ca-section" aria-labelledby="actualsHeading">
        <div class="ca-section-head">
            <h2 id="actualsHeading" class="section-title">Riwayat Kunjungan</h2>
            <p class="section-note">Laporan kunjungan yang sudah disimpan.</p>
        </div>
        <div id="actualsLoading" class="loading-slot"></div>
        <div id="actualsList" class="ca-card-list" style="display: none;"></div>
        <div id="actualsEmpty" class="empty-state" style="display: none;">
            <div class="empty-state-icon" aria-hidden="true"><i class="bi bi-journal-check"></i></div>
            <h4>Belum ada laporan tersimpan</h4>
            <p>Laporan yang selesai akan muncul di sini setelah bukti kunjungan, verifikasi dokter, dan produk detailing disimpan.</p>
        </div>
    </section>

    @else

    <div class="filter-bar">
        <div class="filter-controls">
            <div class="filter-field">
                <label for="filterManagerMr">MR:</label>
                <select id="filterManagerMr">
                    <option value="">Semua MR</option>
                    @foreach(auth()->user()->getSubordinateMrUsers() as $subMr)
                        <option value="{{ $subMr->id }}">{{ $subMr->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field">
                <label for="filterManagerMonth">Periode:</label>
                <select id="filterManagerMonth">
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

            <div class="filter-field">
                <label for="filterManagerVisitType">Kategori:</label>
                <select id="filterManagerVisitType">
                    <option value="">Semua Kategori</option>
                    <option value="planned">Terencana</option>
                    <option value="unplanned">Di Luar Rencana</option>
                    <option value="non_target">Non-Target</option>
                </select>
            </div>
        </div>

        <div>
            <button type="button" class="btn btn-outline btn-sm" data-action="reset-manager-filters">
                Reset Filter
            </button>
        </div>
    </div>

    <div class="metric-grid">
        <div class="metric-card">
            <div class="metric-label">Total Kunjungan</div>
            <div id="metricTotalActuals" class="metric-value tabular-nums">0</div>
            <div class="metric-note">Total laporan</div>
        </div>

        <div class="metric-card">
            <div class="metric-label">Terencana</div>
            <div id="metricPlannedActuals" class="metric-value metric-value-primary tabular-nums">0</div>
            <div class="metric-note"><strong id="metricPlannedPercent">0%</strong> dari total</div>
        </div>

        <div class="metric-card">
            <div class="metric-label">Luar Rencana</div>
            <div id="metricUnplannedActuals" class="metric-value metric-value-warning tabular-nums">0</div>
            <div class="metric-note"><strong id="metricUnplannedPercent">0%</strong> dari total</div>
        </div>
    </div>

    <section class="actual-section">
        <div>
            <h2 class="section-title">Kunjungan Tim</h2>
            <p class="section-note">Laporan kunjungan dari MR bawahan.</p>
        </div>

        <div id="actualsLoading" class="loading-slot"></div>

        <div id="actualsTableWrap" class="table-responsive" style="display: none;">
            <table class="enterprise-table">
                <thead>
                    <tr>
                        <th>Tanggal &amp; Jam</th>
                        <th>MR</th>
                        <th>Target Dokter &amp; Faskes</th>
                        <th>Kategori Kunjungan</th>
                        <th>Detailing Produk</th>
                        <th class="ca-table-action-head">Bukti</th>
                    </tr>
                </thead>
                <tbody id="actualsTableBody"></tbody>
            </table>
        </div>

        <div id="actualsEmpty" class="empty-state" style="display: none;">
            <h4>Belum Ada Data</h4>
            <p>
                Tidak ada kunjungan untuk filter ini.
            </p>
        </div>
    </section>
    @endif
</div>

@if(auth()->user()->role === 'mr')
<div id="actualModal" class="ca-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="actualModalTitle">
    <div class="ca-sheet">

        <header class="ca-sheet-head">
            <button type="button" class="ca-back" data-action="close-actual-modal">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Pelaksanaan Kunjungan
            </button>
            <h3 id="actualModalTitle" class="ca-sheet-title">Laporan Kunjungan</h3>
            <p id="actualModeLabel" class="ca-sheet-mode"></p>
            <div class="ca-progress-head">
                <span id="actualStepName">1. Informasi &amp; Waktu</span>
                <span id="actualStepCount">Langkah 1 dari 3</span>
            </div>
            <div class="ca-progress" role="group" aria-label="Langkah laporan">
                <button type="button" id="stepIndicator1" class="ca-progress-seg" data-action="actual-step" data-step="1" aria-label="Langkah 1: Informasi dan Waktu"></button>
                <button type="button" id="stepIndicator2" class="ca-progress-seg" data-action="actual-step" data-step="2" aria-label="Langkah 2: Bukti dan Produk"></button>
                <button type="button" id="stepIndicator3" class="ca-progress-seg" data-action="actual-step" data-step="3" aria-label="Langkah 3: Konfirmasi"></button>
            </div>
        </header>

        <form id="actualForm" class="ca-form" novalidate>
            <input type="hidden" id="actualPlanId">
            <input type="hidden" id="actualSessionId">

            <div class="ca-sheet-body">
                <div id="actualModalError" class="ca-alert-error" role="alert" style="display: none;"></div>

                <div id="stepSection1" class="ca-step-panel">
                    <h4 class="ca-step-title">Informasi Kunjungan</h4>
                    <p class="ca-helper" id="stepOneHelper">Pilih dokter, tanggal, dan waktu kunjungan. Validasi final tetap dilakukan server saat laporan disimpan.</p>

                    <div class="ca-field">
                        <label for="actualDoctorSelect" class="ca-label">Dokter <span class="ca-req" aria-hidden="true">*</span></label>
                        <select id="actualDoctorSelect" class="ca-input" required></select>
                        <div id="unplannedScopeWrap" class="ca-scope-toggle" style="display: none; margin-top: 0.5rem;">
                            <label class="ca-checkbox-inline" style="font-size: 0.85rem; color: #475569; display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="chkAllMclScope" value="1">
                                <span>Cari di seluruh Master Customer List (lintas distrik)</span>
                            </label>
                        </div>
                        <p class="ca-field-note" id="doctorLockNote" style="display: none;"></p>
                        <p class="ca-field-error" data-error-for="doctor" role="alert"></p>
                    </div>

                    <div id="sessionTimeBox" class="ca-readbox" style="display: none;">
                        <span class="ca-readbox-cap">Tanggal &amp; waktu dari server &middot; hanya baca</span>
                        <strong id="sessionTimeText">-</strong>
                        <p>Waktu berasal dari Visit Session server dan tidak dapat diubah.</p>
                    </div>

                    <div id="manualTimeFields">
                        <div class="ca-field">
                            <label for="actualDateDisplay" class="ca-label">Tanggal kunjungan <span class="ca-req" aria-hidden="true">*</span></label>
                            <div class="date-input-shell">
                                <input type="text" id="actualDateDisplay" readonly placeholder="Pilih tanggal kunjungan"
                                       data-action="open-date-picker"
                                       class="ca-input ca-input-clickable"
                                       title="Klik untuk memilih tanggal">
                                <input type="date" id="actualDateInput" required class="date-native-input" tabindex="-1" aria-hidden="true">
                            </div>
                            <p class="ca-field-note" id="dateLockNote" style="display: none;">Tanggal mengikuti jadwal dan tidak dapat diubah.</p>
                            <p class="ca-field-error" data-error-for="date" role="alert"></p>
                        </div>
                        <div class="ca-two-col">
                            <div class="ca-field">
                                <label for="checkInInput" class="ca-label">Waktu Mulai <span class="ca-req" aria-hidden="true">*</span></label>
                                <input type="text" id="checkInInput" required value="08.30" maxlength="5" inputmode="numeric" placeholder="08.30" class="ca-input tabular-nums">
                            </div>
                            <div class="ca-field">
                                <label for="checkOutInput" class="ca-label">Waktu Selesai <span class="ca-req" aria-hidden="true">*</span></label>
                                <input type="text" id="checkOutInput" required value="09.00" maxlength="5" inputmode="numeric" placeholder="09.00" class="ca-input tabular-nums">
                            </div>
                        </div>
                        <p class="ca-field-error" data-error-for="time" role="alert"></p>
                    </div>

                    <div id="visitReasonWrap" class="ca-field" style="display: none;">
                        <label for="visitReasonInput" class="ca-label">Alasan kunjungan di luar rencana</label>
                        <textarea id="visitReasonInput" class="ca-input ca-textarea" rows="3" placeholder="Contoh: Dokter meminta bertemu di luar jadwal"></textarea>
                        <p class="ca-field-note">Opsional. Tuliskan keterangan singkat bila ada.</p>
                    </div>

                    <button type="button" data-action="reset-actual-form" class="ca-link-danger" title="Kosongkan seluruh isian formulir">
                        Kosongkan isian
                    </button>
                </div>

                <div id="stepSection2" class="ca-step-panel" style="display: none;">
                    <h4 class="ca-step-title">Bukti Kunjungan</h4>
                    <p class="ca-helper">Lengkapi bukti minimum sebelum lanjut: foto kunjungan, verifikasi dokter, dan minimal satu produk.</p>

                    <div class="ca-evidence">
                        <div class="ca-evidence-head">
                            <span class="ca-evidence-icon"><i class="bi bi-camera" aria-hidden="true"></i></span>
                            <div class="ca-evidence-meta">
                                <b>Foto kunjungan <span class="ca-req" aria-hidden="true">*</span></b><em class="ca-required-chip">Wajib</em>
                                <span id="photoStatusText">Belum ada foto</span>
                            </div>
                            <label class="ca-btn ca-btn-soft ca-btn-sm" for="photoFileInput" id="photoPickLabel">Pilih Foto</label>
                            <input type="file" id="photoFileInput" accept="image/*" class="ca-file-input">
                        </div>
                        <input type="hidden" id="photoUrlInput">
                        <div id="photoPreviewContainer" class="ca-preview" style="display: none;">
                            <img id="photoPreviewImg" src="" alt="Pratinjau foto kunjungan">
                            <span id="photoFileName" class="ca-preview-name"></span>
                            <button type="button" data-action="clear-photo" class="ca-btn ca-btn-danger ca-btn-sm" title="Hapus foto">Hapus</button>
                        </div>
                    </div>

                    <div class="ca-evidence">
                        <div class="ca-evidence-head">
                            <span class="ca-evidence-icon"><i class="bi bi-pencil-square" aria-hidden="true"></i></span>
                            <div class="ca-evidence-meta">
                                <b>Verifikasi dokter <span class="ca-req" aria-hidden="true">*</span></b><em class="ca-required-chip">Wajib</em>
                                <span><span id="sigBadgeRecorded" style="display: none;">Tanda tangan tersimpan</span><span id="sigStatusText">Belum ada tanda tangan</span></span>
                            </div>
                        </div>
                        <div class="ca-seg" role="tablist" aria-label="Jenis verifikasi">
                            <button type="button" id="tabSignTouch" class="ca-seg-btn is-active" data-action="verification-mode" data-mode="touch" role="tab">Tanda Tangan</button>
                            <button type="button" id="tabSignStamp" class="ca-seg-btn" data-action="verification-mode" data-mode="stamp" role="tab">Stempel / Resep</button>
                        </div>
                        <input type="hidden" id="signatureUrlInput">
                        <div id="panelSignTouch">
                            <div class="ca-canvas-wrap">
                                <canvas id="signatureCanvas" width="500" height="165"></canvas>
                                <div id="sigPlaceholder" class="ca-canvas-placeholder"><span>Tanda tangan di area ini</span></div>
                            </div>
                            <button type="button" class="ca-btn ca-btn-danger ca-btn-sm ca-clear-sig" data-action="clear-signature">Bersihkan</button>
                        </div>
                        <div id="panelSignStamp" style="display: none;">
                            <p class="ca-field-note">Unggah foto stempel atau paraf fisik.</p>
                            <label class="ca-btn ca-btn-soft ca-btn-sm" for="stampFileInput">Pilih Berkas</label>
                            <input type="file" id="stampFileInput" accept="image/*" class="ca-file-input">
                            <div id="stampPreviewContainer" class="ca-preview" style="display: none;">
                                <img id="stampPreviewImg" src="" alt="Pratinjau stempel">
                                <span id="stampFileName" class="ca-preview-name"></span>
                                <button type="button" data-action="clear-stamp-file" class="ca-btn ca-btn-danger ca-btn-sm" title="Hapus foto stempel">Hapus</button>
                            </div>
                        </div>
                    </div>
                    <p class="ca-field-error" data-error-for="evidence" role="alert"></p>

                    <div class="ca-split"></div>

                    <h4 class="ca-step-title">Detailing Produk <span class="ca-req" aria-hidden="true">*</span></h4>
                    <p class="ca-helper">Pilih setidaknya satu produk yang dijelaskan selama kunjungan.</p>
                    <div class="ca-search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="text" id="productSearchInput" class="ca-input" placeholder="Cari produk..." aria-label="Cari produk">
                    </div>
                    <div id="productPicker" class="product-picker">Memuat produk...</div>
                    <p id="productCount" class="ca-product-count">0 produk dipilih</p>
                    <p class="ca-field-error" data-error-for="products" role="alert"></p>
                </div>

                <div id="stepSection3" class="ca-step-panel" style="display: none;">
                    <h4 class="ca-step-title">Konfirmasi Laporan</h4>
                    <p class="ca-helper">Pastikan data sudah sesuai. Setelah disimpan, laporan menjadi kunjungan final.</p>
                    <div id="actualSummary" class="ca-summary"></div>
                    <p class="ca-field-note" style="text-align: center; margin: 0.5rem 0 0;">Data kunjungan akan divalidasi dan dicatat ke sistem.</p>
                </div>
            </div>

            <footer class="ca-sheet-foot">
                <button type="button" id="actualPrevButton" class="ca-btn ca-btn-outline" data-action="actual-prev">Batal</button>
                <button type="button" id="actualNextButton" class="ca-btn ca-btn-primary ca-btn-grow" data-action="actual-next">Lanjut ke Bukti &amp; Produk</button>
                <button type="submit" id="actualSubmitButton" class="ca-btn ca-btn-primary ca-btn-grow" style="display: none;">Simpan Laporan</button>
            </footer>
        </form>
    </div>
</div>

<div id="leaveConfirmModal" class="ca-backdrop ca-backdrop-top" style="display: none;" role="alertdialog" aria-modal="true" aria-labelledby="leaveConfirmTitle">
    <div class="ca-dialog">
        <h3 id="leaveConfirmTitle">Keluar dari laporan?</h3>
        <p>Laporan ini belum disimpan ke server dan belum tercatat sebagai kunjungan. Isian yang belum dikirim tidak dijamin tersimpan.</p>
        <div class="ca-actions ca-actions-row">
            <button type="button" id="leaveStayButton" class="ca-btn ca-btn-outline ca-btn-grow" data-action="stay-in-actual">Lanjut Mengisi</button>
            <button type="button" class="ca-btn ca-btn-danger ca-btn-grow" data-action="leave-actual">Keluar</button>
        </div>
    </div>
</div>

<div id="cancelSessionModal" class="modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="cancelSessionTitle">
    <div class="modal-card">
        <div class="modal-head">
            <h3 id="cancelSessionTitle" class="modal-title modal-title-danger">Batalkan Sesi</h3>
            <button type="button" class="close-btn" data-action="close-cancel-session" aria-label="Tutup">&times;</button>
        </div>

        <div id="cancelSessionModalError" style="display: none;" class="alert alert-danger" role="alert"></div>

        <p class="modal-copy">
            Batalkan sesi kunjungan ke <strong id="cancelSessionDoctorName">-</strong>? Jadwal tetap tersimpan.
        </p>

        <form id="cancelSessionForm">
            <input type="hidden" id="cancelSessionId">
            <div class="ca-field ca-field-spacious">
                <label for="cancelSessionReasonInput" class="ca-label">
                    Alasan Pembatalan
                </label>
                <textarea id="cancelSessionReasonInput" required minlength="3" rows="3" class="ca-input ca-textarea"
                          placeholder="Contoh: Dokter dipanggil operasi darurat sebelum detailing obat"></textarea>
            </div>

            <div class="ca-actions ca-actions-row">
                <button type="button" class="ca-btn ca-btn-outline ca-btn-grow" data-action="close-cancel-session">Kembali</button>
                <button type="submit" id="btnConfirmCancelSession" class="ca-btn ca-btn-danger-solid ca-btn-grow">
                    Batalkan Sesi
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<div id="evidenceModal" class="modal-backdrop" style="display: none;" role="dialog" aria-modal="true">
    <div class="modal-card modal-card-evidence">
        <div class="modal-head modal-head-start">
            <div>
                <h3 class="modal-title modal-title-sm">
                    Bukti: <span id="evidenceDoctorName">-</span>
                </h3>
                <div id="evidenceMrWrap" class="evidence-meta" style="display: none;">
                    MR: <strong id="evidenceMrName">-</strong>
                </div>
            </div>
            <button type="button" class="close-btn" data-action="close-evidence-modal" aria-label="Tutup">&times;</button>
        </div>
        <div class="evidence-stack">
            <div>
                <label class="evidence-label">
                    Foto:
                </label>
                <div class="evidence-image-frame evidence-image-frame-photo">
                    <img id="evidencePhotoImg" src="" alt="Foto Lokasi">
                </div>
            </div>
            <div>
                <label class="evidence-label">
                    Tanda tangan:
                </label>
                <div class="evidence-image-frame evidence-image-frame-signature">
                    <img id="evidenceSignatureImg" src="" alt="Tanda Tangan Dokter">
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline btn-sm" data-action="close-evidence-modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@vite('resources/js/pages/call-actuals.js')
@endpush
@push('styles')
@vite('resources/css/pages/call-actuals.css')
@endpush
@endsection