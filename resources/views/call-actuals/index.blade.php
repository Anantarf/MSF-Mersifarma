@extends('layouts.app')

@section('title', 'Pelaksanaan Kunjungan')

@section('content')
<div class="actual-page">

    <div class="actual-page-head">
        <div>
            <h1 class="actual-page-title">Pelaksanaan Kunjungan</h1>
            @if(auth()->user()->role === 'mr')
            <p class="actual-page-subtitle">Catatan kunjungan dokter.</p>
            @else
            <p class="actual-page-subtitle">Rekap kunjungan tim.</p>
            @endif
        </div>
        @if(auth()->user()->role === 'mr')
        <button type="button" class="btn btn-outline btn-primary-outline" data-action="open-unplanned-actual">
            + Luar Rencana
        </button>
        @endif
    </div>

    <div id="actualStatusAlert" style="display: none;" class="alert"></div>

    @if(auth()->user()->role === 'mr')

    <section id="activeSessionSection" class="active-session-card" style="display: none;">
        <div class="active-session-layout">
            <div>
                <div class="active-session-kicker">
                    <span class="active-session-dot"></span>
                    Sesi aktif
                </div>
                <h3 id="activeSessionDoctorName" class="active-session-title">-</h3>
                <p id="activeSessionDoctorDetail" class="active-session-detail">-</p>
                <div class="active-session-time">
                    <span class="text-muted">Check-in:</span> <strong id="activeSessionCheckInTime" class="tabular-nums">-</strong>
                    <span id="activeSessionCheckOutWrap" class="active-session-checkout" style="display: none;">
                        | <span class="text-muted">Check-out:</span> <strong id="activeSessionCheckOutTime" class="tabular-nums">-</strong>
                    </span>
                </div>
            </div>
            <div class="button-row">
                <button type="button" id="btnSessionCheckout" class="btn btn-primary" data-action="session-checkout">
                    Check-out
                </button>
                <button type="button" id="btnSessionContinue" class="btn btn-primary" style="display: none;" data-action="open-session-actual">
                    Lanjutkan Laporan
                </button>
                <button type="button" class="btn btn-outline btn-danger-outline" data-action="open-cancel-session">
                    Batalkan
                </button>
            </div>
        </div>
    </section>

    <section class="actual-section">
        <div>
            <h2 class="section-title">Jadwal Hari Ini</h2>
            <p class="section-note">Mulai dari jadwal yang sudah disetujui.</p>
        </div>
        <div id="todayPlansLoading" class="loading-slot"></div>
        <div id="todayPlansTableWrap" class="table-responsive" style="display: none;">
            <table class="enterprise-table">
                <thead>
                    <tr>
                        <th>Jam Rencana</th>
                        <th>Target Dokter</th>
                        <th>Spesialisasi & Alamat</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="todayPlansTableBody"></tbody>
            </table>
        </div>
        <div id="todayPlansEmpty" class="empty-state" style="display: none;">
            <h4>Tidak Ada Jadwal Hari Ini</h4>
            <p>
                Gunakan <strong>+ Luar Rencana</strong> jika kunjungan tidak ada di jadwal.
            </p>
        </div>
    </section>

    <section class="actual-section">
        <div>
            <h2 class="section-title">Riwayat Kunjungan</h2>
            <p class="section-note">Laporan kunjungan yang sudah disimpan.</p>
        </div>
        <div id="actualsLoading" class="loading-slot"></div>
        <div id="actualsTableWrap" class="table-responsive" style="display: none;">
            <table class="enterprise-table">
                <thead>
                    <tr>
                        <th>Tanggal & Jam</th>
                        <th>Target Dokter</th>
                        <th>Kategori Kunjungan</th>
                        <th>Detailing Produk</th>
                        <th style="text-align: center;">Bukti</th>
                    </tr>
                </thead>
                <tbody id="actualsTableBody"></tbody>
            </table>
        </div>
        <div id="actualsEmpty" class="empty-state" style="display: none;">
            <h4>Belum Ada Riwayat</h4>
            <p>
                Laporan yang selesai akan muncul di sini.
            </p>
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
                <input type="month" id="filterManagerMonth" value="{{ now()->format('Y-m') }}">
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
                        <th>Tanggal & Jam</th>
                        <th>MR</th>
                        <th>Target Dokter & Faskes</th>
                        <th>Kategori Kunjungan</th>
                        <th>Detailing Produk</th>
                        <th style="text-align: center;">Bukti</th>
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

<div id="actualModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card modal-card-actual">

        <div class="modal-head">
            <h3 id="actualModalTitle" class="modal-title">Catat Kunjungan</h3>
            <button type="button" class="close-btn" data-action="close-actual-modal">&times;</button>
        </div>

        <div id="actualModalError" class="alert alert-danger modal-error" style="display: none;"></div>
        <div class="stepper-wrap">
            <div id="stepIndicator1" class="step-item" data-action="actual-step" data-step="1">
                <span class="step-num">1</span>
                <span class="step-title">Dokter</span>
            </div>
            <div id="stepLine1" class="step-line"></div>
            <div id="stepIndicator2" class="step-item" data-action="actual-step" data-step="2">
                <span class="step-num">2</span>
                <span class="step-title">Produk</span>
            </div>
            <div id="stepLine2" class="step-line"></div>
            <div id="stepIndicator3" class="step-item" data-action="actual-step" data-step="3">
                <span class="step-num">3</span>
                <span class="step-title">Bukti</span>
            </div>
        </div>

        <form id="actualForm">
            <input type="hidden" id="actualPlanId">
            <input type="hidden" id="actualSessionId">
            <div id="stepSection1">
                <div class="form-field">
                    <label for="actualDoctorSelect" class="form-label">
                        Dokter
                    </label>
                    <select id="actualDoctorSelect" class="form-control" required></select>
                </div>
                <div class="form-field">
                    <label for="actualDateDisplay" class="form-label">
                        Tanggal
                    </label>
                    <div class="date-input-shell">
                        <input type="text" id="actualDateDisplay" readonly placeholder="Pilih tanggal kunjungan"
                               data-action="open-date-picker"
                               class="form-control form-control-clickable"
                               title="Klik untuk memilih tanggal">
                        <input type="date" id="actualDateInput" required
                               class="date-native-input"
                               >
                    </div>
                </div>
                <div class="time-grid">
                    <div>
                        <label for="checkInInput" class="form-label">
                            Check-in
                        </label>
                        <input type="text" id="checkInInput" required value="08.30" maxlength="5" inputmode="numeric" placeholder="08.30"
                               class="form-control tabular-nums"
                               >
                    </div>
                    <div>
                        <label for="checkOutInput" class="form-label">
                            Check-out
                        </label>
                        <input type="text" id="checkOutInput" required value="09.00" maxlength="5" inputmode="numeric" placeholder="09.00"
                               class="form-control tabular-nums"
                               >
                    </div>
                </div>
                <div id="visitReasonWrap" class="form-field form-field-spaced" style="display: none;">
                    <label for="visitReasonInput" class="form-label">
                        Alasan
                    </label>
                    <textarea id="visitReasonInput" class="form-control form-textarea" rows="5" placeholder="Opsional"></textarea>
                </div>
                <div class="step-actions">
                    <div>
                        <button type="button" data-action="reset-actual-form" class="btn btn-outline btn-sm btn-danger-soft" title="Kosongkan seluruh isian formulir">
                            Hapus
                        </button>
                    </div>
                    <div class="step-actions-right">
                        <button type="button" class="btn btn-outline" data-action="close-actual-modal">Batal</button>
                        <button type="button" class="btn btn-primary" data-action="actual-step" data-step="2">
                            Lanjut
                        </button>
                    </div>
                </div>
            </div>
            <div id="stepSection2" style="display: none;">
                <div class="form-field">
                    <label class="form-label form-label-tight">
                        Produk
                    </label>
                    <p class="field-help">
                        Pilih minimal satu produk.
                    </p>
                    <div class="modal-filter-wrap modal-filter-spaced">
                        <i class="bi bi-search"></i>
                        <input type="text" id="productSearchInput" class="modal-filter-input" placeholder="Cari produk obat..." >
                    </div>
                    <div id="productPicker" class="product-picker">
                        Memuat produk...
                    </div>
                </div>
                <div class="step-actions">
                    <button type="button" class="btn btn-outline" data-action="actual-step" data-step="1">
                        Kembali
                    </button>
                    <button type="button" class="btn btn-primary" data-action="actual-step" data-step="3">
                        Lanjut
                    </button>
                </div>
            </div>
            <div id="stepSection3" style="display: none;">
                <div class="evidence-card">
                    <div style="margin-bottom: 0.5rem;">
                        <label style="font-size: 0.85rem; font-weight: 600; color: var(--secondary); margin-bottom: 0;">Foto</label>
                    </div>
                    <input type="hidden" id="photoUrlInput" required>
                    <div id="photoActionRow" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                        <label class="btn btn-outline btn-sm" style="font-size: 0.8rem; padding: 0.35rem 0.85rem; cursor: pointer; margin-bottom: 0; font-weight: 500;">
                            <span>Upload Foto</span>
                            <input type="file" id="photoFileInput" accept="image/*" style="display: none;" >
                        </label>
                    </div>
                    <div id="photoPreviewContainer" style="display: none; margin-top: 0.65rem; align-items: center; justify-content: space-between; gap: 0.75rem; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 0.375rem; padding: 0.45rem 0.65rem;">
                        <div style="display: flex; align-items: center; gap: 0.65rem; min-width: 0;">
                            <img id="photoPreviewImg" src="" alt="Pratinjau Foto" style="width: 44px; height: 44px; object-fit: cover; border-radius: 0.25rem; border: 1px solid var(--border);">
                            <div style="min-width: 0;">
                                <div style="font-size: 0.775rem; font-weight: 600; color: var(--secondary);">Foto tersimpan</div>
                                <span id="photoFileName" style="font-size: 0.725rem; color: var(--text-muted); display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 220px;"></span>
                            </div>
                        </div>
                        <button type="button" data-action="clear-photo" class="btn btn-outline btn-sm" style="color: #dc2626; border-color: #fecdd3; background: #fff5f5; font-size: 0.75rem; padding: 0.2rem 0.5rem; font-weight: 500;" title="Hapus foto">
                            Hapus
                        </button>
                    </div>
                </div>
                <div class="evidence-card evidence-card-spaced">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem; flex-wrap: wrap; gap: 0.5rem;">
                        <label style="font-size: 0.85rem; font-weight: 600; color: var(--secondary); margin-bottom: 0;">Verifikasi</label>
                        <div style="display: flex; gap: 0.25rem; background: #f1f5f9; padding: 0.2rem; border-radius: 0.375rem; border: 1px solid var(--border);">
                            <button type="button" id="tabSignTouch" class="btn btn-sm" style="font-size: 0.75rem; padding: 0.25rem 0.65rem; border-radius: 0.25rem; background: white; color: var(--primary); font-weight: 600; border: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05);" data-action="verification-mode" data-mode="touch">
                                Tanda Tangan
                            </button>
                            <button type="button" id="tabSignStamp" class="btn btn-sm" style="font-size: 0.75rem; padding: 0.25rem 0.65rem; border-radius: 0.25rem; background: transparent; color: var(--text-muted); font-weight: 500; border: none;" data-action="verification-mode" data-mode="stamp">
                                Stempel / Resep
                            </button>
                        </div>
                    </div>

                    <input type="hidden" id="signatureUrlInput" required>
                    <div id="panelSignTouch">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                            <span id="sigBadgeRecorded" style="display: none; font-size: 0.75rem; font-weight: 600; color: var(--success);">
                                Tanda tangan tersimpan
                            </span>
                            <button type="button" class="btn btn-outline btn-sm" style="color: #dc2626; border-color: #fecdd3; background: #fff5f5; font-size: 0.75rem; padding: 0.2rem 0.55rem; margin-left: auto; font-weight: 500;" data-action="clear-signature">
                                Bersihkan
                            </button>
                        </div>
                        <div style="border: 1px solid var(--border); border-radius: 0.375rem; background: #ffffff; padding: 0.25rem; touch-action: none; position: relative;">
                            <canvas id="signatureCanvas" width="500" height="165" style="width: 100%; height: 165px; display: block; border-radius: 0.25rem; cursor: crosshair; background: #fbfcfd;"></canvas>
                            <div id="sigPlaceholder" style="position: absolute; bottom: 16px; left: 16px; right: 16px; pointer-events: none; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; transition: opacity 0.2s ease;">
                                <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 500;">Tanda tangan di area ini</span>
                            </div>
                        </div>
                    </div>
                    <div id="panelSignStamp" style="display: none; text-align: center; border: 1px dashed var(--border-strong); border-radius: 0.375rem; padding: 1rem; background: #fbfcfd;">
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.65rem;">
                            Unggah stempel atau paraf fisik.
                        </p>
                        <div style="display: flex; justify-content: center; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                            <label class="btn btn-outline btn-sm" style="font-size: 0.8rem; padding: 0.35rem 0.75rem; cursor: pointer; margin-bottom: 0;">
                                <span>Pilih Berkas</span>
                                <input type="file" id="stampFileInput" accept="image/*" style="display: none;" >
                            </label>
                        </div>
                        <div id="stampPreviewContainer" style="display: none; margin-top: 0.65rem; align-items: center; justify-content: space-between; gap: 0.75rem; background: white; border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.45rem 0.65rem; max-width: 320px; margin-left: auto; margin-right: auto;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; min-width: 0;">
                                <img id="stampPreviewImg" src="" alt="Pratinjau Stempel" style="width: 36px; height: 36px; object-fit: cover; border-radius: 0.25rem; border: 1px solid var(--border);">
                                <span id="stampFileName" style="font-size: 0.725rem; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 160px;"></span>
                            </div>
                            <button type="button" data-action="clear-stamp-file" class="btn btn-outline btn-sm" style="color: #dc2626; border-color: #fecdd3; background: #fff5f5; font-size: 0.75rem; padding: 0.15rem 0.45rem; font-weight: 500;" title="Hapus foto stempel">Hapus</button>
                        </div>
                    </div>
                </div>
                <div class="step-actions">
                    <button type="button" class="btn btn-outline" data-action="actual-step" data-step="2">
                        Kembali
                    </button>
                    <button type="submit" id="actualSubmitButton" class="btn btn-primary" style="font-weight: 700; padding: 0.55rem 1.25rem;">
                        Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<div id="cancelSessionModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card">
        <div class="modal-head">
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--danger); margin: 0;">Batalkan Sesi</h3>
            <button type="button" class="close-btn" data-action="close-cancel-session">&times;</button>
        </div>

        <div id="cancelSessionModalError" style="display: none;" class="alert alert-danger"></div>

        <p style="font-size: 0.875rem; color: var(--secondary); margin-bottom: 1rem; line-height: 1.5;">
            Batalkan sesi kunjungan ke <strong id="cancelSessionDoctorName">-</strong>? Jadwal tetap tersimpan.
        </p>

        <form id="cancelSessionForm">
            <input type="hidden" id="cancelSessionId">
            <div style="margin-bottom: 1.25rem;">
                <label for="cancelSessionReasonInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--secondary);">
                    Alasan Pembatalan
                </label>
                <textarea id="cancelSessionReasonInput" required minlength="3" rows="3"
                          style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.875rem;"
                          placeholder="Contoh: Dokter dipanggil operasi darurat sebelum detailing obat"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-outline" data-action="close-cancel-session">Kembali</button>
                <button type="submit" id="btnConfirmCancelSession" class="btn btn-danger" style="background: var(--danger); color: white; border: none; font-weight: 600;">
                    Batalkan
                </button>
            </div>
        </form>
    </div>
</div>
<div id="evidenceModal" class="modal-backdrop" style="display: none;">
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
            <button type="button" class="close-btn" data-action="close-evidence-modal">&times;</button>
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
<script>
    window.mersiCallActual = {
        currentUserRole: @json(auth()->user()->role),
        currentUserId: @json(auth()->id()),
    };
</script>
@vite('resources/js/pages/call-actuals.js')
@endpush
@push('styles')
<style>
    .actual-page {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    .actual-page-head,
    .active-session-layout,
    .button-row,
    .filter-bar,
    .filter-controls,
    .filter-field {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
    }
    .actual-page-head,
    .active-session-layout,
    .filter-bar {
        justify-content: space-between;
        gap: 1rem;
    }
    .actual-page-title {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--secondary);
        margin: 0 0 0.15rem;
    }
    .actual-page-subtitle,
    .section-note,
    .metric-note,
    .active-session-detail,
    .text-muted {
        color: var(--text-muted);
    }
    .actual-page-subtitle,
    .section-note {
        font-size: 0.8125rem;
        margin: 0;
    }
    .btn-primary-outline {
        border-color: var(--primary);
        color: var(--primary);
        font-weight: 600;
    }
    .btn-danger-outline {
        color: var(--danger);
        border-color: #fecdd3;
    }
    .active-session-card,
    .filter-bar,
    .metric-card,
    .empty-state {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 8px;
    }
    .active-session-card {
        border-left: 4px solid var(--success);
        padding: 1.15rem 1.25rem;
    }
    .active-session-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--success);
        text-transform: uppercase;
        margin-bottom: 0.35rem;
    }
    .active-session-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--success);
        display: inline-block;
    }
    .active-session-title,
    .section-title,
    .modal-title {
        color: var(--secondary);
        font-weight: 700;
        margin: 0;
    }
    .active-session-title { font-size: 1.1rem; margin-bottom: 0.15rem; }
    .active-session-detail { font-size: 0.8rem; margin: 0; }
    .active-session-time {
        margin-top: 0.4rem;
        font-size: 0.775rem;
        color: var(--text-main);
    }
    .active-session-checkout { margin-left: 0.65rem; }
    .button-row { gap: 0.5rem; }
    .actual-section {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
    }
    .section-title { font-size: 1.15rem; margin-bottom: 0.15rem; }
    .loading-slot { padding: 0.25rem 0; }
    .empty-state {
        padding: 2.5rem 1.5rem;
        color: var(--text-muted);
        text-align: center;
    }
    .empty-state h4 {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--secondary);
        margin: 0 0 0.25rem;
    }
    .empty-state p {
        font-size: 0.825rem;
        max-width: 420px;
        margin: 0 auto;
    }
    .filter-bar { padding: 0.85rem 1.15rem; }
    .filter-controls { gap: 0.85rem; }
    .filter-field { gap: 0.4rem; }
    .filter-field label {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--secondary);
        white-space: nowrap;
    }
    .filter-field select,
    .filter-field input {
        padding: 0.4rem 0.65rem;
        border: 1px solid var(--border);
        border-radius: 6px;
        font-size: 0.825rem;
        color: var(--secondary);
        background: #fff;
    }
    .metric-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
    }
    .metric-card { padding: 1rem 1.15rem; }
    .metric-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
    }
    .metric-value {
        font-size: 1.65rem;
        font-weight: 700;
        color: var(--secondary);
        margin-top: 0.2rem;
    }
    .metric-value-primary { color: var(--primary); }
    .metric-value-warning { color: #d97706; }
    .metric-note {
        font-size: 0.75rem;
        margin-top: 0.2rem;
    }
    .modal-card-actual { max-width: 580px; }
    .modal-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        border-bottom: 1px solid var(--border);
        padding-bottom: 0.75rem;
    }
    .modal-title { font-size: 1.15rem; }
    .modal-error { margin-bottom: 0.85rem; }
    .stepper-wrap {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.25rem;
        padding: 0.6rem 0.85rem;
        background: #f8fafc;
        border: 1px solid var(--border);
        border-radius: 8px;
    }
    .step-item {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        cursor: pointer;
    }
    .step-num {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #e2e8f0;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
    }
    .step-title {
        font-size: 0.8rem;
        font-weight: 500;
        color: var(--text-muted);
    }
    .step-line {
        flex: 1;
        height: 2px;
        background: var(--border);
        margin: 0 0.5rem;
    }
    .form-field { margin-bottom: 1rem; }
    .form-field-spaced { margin-bottom: 1.15rem; }
    .form-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 0.35rem;
        color: var(--secondary);
    }
    .form-label-tight { margin-bottom: 0.25rem; }
    .form-control {
        width: 100%;
        padding: 0.55rem 0.75rem;
        border: 1px solid var(--border);
        border-radius: 0.375rem;
        font-size: 0.9rem;
    }
    .form-control-clickable {
        background: var(--surface);
        cursor: pointer;
        font-weight: 500;
        color: var(--secondary);
    }
    .form-textarea {
        padding: 0.6rem 0.75rem;
        font-size: 0.85rem;
        line-height: 1.5;
        resize: vertical;
    }
    .date-input-shell {
        position: relative;
        display: flex;
        align-items: center;
    }
    .date-native-input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
        width: 1px;
        height: 1px;
        bottom: 0;
        left: 0;
    }
    .time-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.85rem;
        margin-bottom: 1.15rem;
    }
    .step-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid var(--border);
        padding-top: 0.85rem;
        margin-top: 1rem;
    }
    .step-actions-right {
        display: flex;
        gap: 0.5rem;
        margin-left: auto;
    }
    .btn-danger-soft {
        color: #dc2626;
        border-color: #fecdd3;
        background: #fff5f5;
        font-size: 0.8rem;
        font-weight: 500;
    }
    .field-help {
        font-size: 0.775rem;
        color: var(--text-muted);
        margin-bottom: 0.5rem;
    }
    .modal-filter-spaced { margin-bottom: 0.5rem; }
    .product-picker {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
        max-height: 220px;
        overflow-y: auto;
        border: 1px solid var(--border);
        border-radius: 0.375rem;
        padding: 0.5rem;
        background: #fafafa;
    }
    .evidence-card {
        margin-bottom: 1.15rem;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 0.85rem;
    }
    .evidence-card-spaced { margin-bottom: 1rem; }
    .modal-card-evidence { max-width: 520px; }
    .modal-head-start { align-items: flex-start; }
    .modal-title-sm { font-size: 1.1rem; }
    .evidence-meta {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-top: 0.25rem;
    }
    .evidence-meta strong { color: var(--secondary); }
    .evidence-stack {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .evidence-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-muted);
        display: block;
        margin-bottom: 0.35rem;
    }
    .evidence-image-frame {
        border: 1px solid var(--border);
        border-radius: 6px;
        overflow: hidden;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .evidence-image-frame img {
        max-width: 100%;
        object-fit: contain;
    }
    .evidence-image-frame-photo {
        background: #f8fafc;
        max-height: 220px;
    }
    .evidence-image-frame-photo img { max-height: 220px; }
    .evidence-image-frame-signature {
        background: #fff;
        height: 110px;
        padding: 0.5rem;
    }
    .evidence-image-frame-signature img { max-height: 100px; }
    .modal-actions {
        text-align: right;
        border-top: 1px solid var(--border);
        padding-top: 0.75rem;
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
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.18rem 0.55rem;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        border: 1px solid transparent;
        line-height: 1.25;
        white-space: nowrap;
    }
    .status-draft { background: #f8fafc; color: #475569; border-color: #e2e8f0; }
    .status-scheduled { background: #f0f9ff; color: #0369a1; border-color: #bae6fd; }
    .status-approved { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }

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
        max-width: 580px;
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
@endsection