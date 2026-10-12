@extends('layouts.app')

@section('title', 'Jadwal Kunjungan')

@section('content')
<div class="plan-page">
    <!-- Header Section -->
    <div class="plan-page-head">
        <div>
            <h1 class="plan-page-title">Jadwal Kunjungan</h1>
            <p class="plan-page-subtitle">Jadwal dari target dokter yang sudah disetujui.</p>
        </div>
        <div>
            @if(auth()->user()->role === 'mr')
            <button type="button" class="btn btn-primary" data-action="open-create-plan">
                + Buat Jadwal
            </button>
            @endif
        </div>
    </div>

    <!-- Alert Banner inside view -->
    <div id="planStatusAlert" style="display: none;" class="alert"></div>

    <!-- Filter Bar -->
    <div class="plan-filter-bar">
        <div class="plan-filter-group">
            <label for="filterDateInput" class="plan-filter-label">Tanggal:</label>
            <input type="date" id="filterDateInput" class="plan-filter-input">
            <div class="plan-quick-actions">
                <button type="button" class="btn btn-outline btn-sm" data-action="quick-date" data-date="all">Semua</button>
                <button type="button" class="btn btn-outline btn-sm" data-action="quick-date" data-date="today">Hari Ini</button>
                <button type="button" class="btn btn-outline btn-sm" data-action="quick-date" data-date="tomorrow">Besok</button>
            </div>
        </div>

        <div class="plan-filter-group">
            <label for="filterStatusSelect" class="plan-filter-label">Status:</label>
            <select id="filterStatusSelect" class="plan-filter-input">
                <option value="">Semua Status</option>
                <option value="scheduled">Terjadwal</option>
                <option value="completed">Selesai</option>
                <option value="expired">Tidak Terlaksana</option>
                <option value="cancelled">Dibatalkan</option>
            </select>
        </div>
    </div>

    <!-- Plan List Container -->
    <div id="planListContainer" class="plan-list-stack">
        <div id="loadingPlans" class="plan-loader"></div>

        <div id="planTableWrap" class="table-responsive" style="display: none;">
            <table class="enterprise-table">
                <thead>
                    <tr>
                        <th>Jadwal Kunjungan</th>
                        <th>Target Dokter</th>
                        <th>Spesialisasi & Faskes</th>
                        <th>Status</th>
                        <th class="plan-action-heading">Aksi</th>
                    </tr>
                </thead>
                <tbody id="planTableBody"></tbody>
            </table>
        </div>

        <div id="emptyPlans" class="plan-empty" style="display: none;">
            <h4>Belum Ada Jadwal</h4>
            @if(auth()->user()->role === 'mr')
            <p>
                Pilih target dokter yang sudah disetujui, lalu tentukan tanggal dan jam kunjungan.
            </p>
            <button type="button" class="btn btn-primary" data-action="open-create-plan">+ Buat Jadwal</button>
            @else
            <p>
                Belum ada jadwal yang tercatat.
            </p>
            @endif
        </div>
    </div>
</div>


<div id="createPlanModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card plan-form-modal">
        <div class="plan-modal-head">
            <h3 class="plan-modal-title">Buat Jadwal</h3>
            <button type="button" class="close-btn" data-action="close-create-plan">&times;</button>
        </div>

        <div id="createPlanModalError" style="display: none;" class="alert alert-danger"></div>

        <form id="createPlanForm">
            
            <div class="plan-field">
                <label for="callListSelect" class="plan-field-label">
                    Target Dokter Bulanan
                </label>
                <select id="callListSelect" class="plan-input" required>
                    <option value="">-- Memuat target dokter... --</option>
                </select>
                <small class="plan-help-text">
                    Hanya target dokter yang sudah disetujui yang dapat dijadwalkan.
                </small>
            </div>

            
            <div class="plan-field">
                <label for="doctorSelect" class="plan-field-label">
                    Target Dokter
                </label>
                <select id="doctorSelect" class="plan-input" required disabled>
                    <option value="">-- Pilih target bulanan terlebih dahulu --</option>
                </select>
            </div>

            <!-- 3. Tanggal Kunjungan -->
            <div class="plan-field">
                <label for="planDateInput" class="plan-field-label">
                    Tanggal Kunjungan
                </label>
                <input type="date" id="planDateInput" class="plan-input" required disabled>
                <div id="planSelectedDateText" class="plan-date-preview" style="display: none;"></div>
                <small id="dateRangeHelp" class="plan-help-text">
                    Tanggal mengikuti periode target dokter.
                </small>
                
                <div id="availabilityNotice" class="plan-availability" style="display: none;"></div>
            </div>

            <!-- 4. Jam Kunjungan -->
            <div class="plan-field plan-field-large">
                <label for="plannedTimeInput" class="plan-field-label">
                    Jam Kunjungan
                </label>
                <input type="text" id="plannedTimeInput" class="plan-input tabular-nums" required value="09.00" maxlength="5" inputmode="numeric" placeholder="09.00">
            </div>

            <div class="plan-dialog-actions">
                <button type="button" class="btn btn-outline" data-action="close-create-plan">Batal</button>
                <button type="submit" id="btnSubmitPlan" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>


<div id="cancelPlanModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card">
        <div class="plan-modal-head plan-modal-head-bordered">
            <h3 class="plan-modal-title plan-danger-title">Batalkan Jadwal</h3>
            <button type="button" class="close-btn" data-action="close-cancel-plan">&times;</button>
        </div>

        <div id="cancelPlanModalError" style="display: none;" class="alert alert-danger"></div>

        <p class="plan-cancel-copy">
            Batalkan jadwal kunjungan ke <strong id="cancelPlanDoctorName">-</strong> pada tanggal <strong id="cancelPlanDate">-</strong>?
        </p>

        <form id="cancelPlanForm">
            <input type="hidden" id="cancelPlanId">
            <div class="plan-field plan-field-wide">
                <label for="cancelPlanReasonInput" class="plan-field-label">
                    Alasan Pembatalan
                </label>
                <textarea id="cancelPlanReasonInput" class="plan-input plan-textarea" required minlength="3" rows="3" placeholder="Contoh: Dokter ada simposium / operasi darurat di luar kota"></textarea>
            </div>

            <div class="plan-dialog-actions">
                <button type="button" class="btn btn-outline" data-action="close-cancel-plan">Kembali</button>
                <button type="submit" id="btnConfirmCancelPlan" class="btn btn-danger plan-danger-button">
                    Batalkan Jadwal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detail Jadwal -->
<div id="planDetailModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card plan-detail-modal">
        <div class="plan-modal-head plan-modal-head-bordered">
            <h3 class="plan-modal-title">Detail Jadwal</h3>
            <button type="button" class="close-btn" data-action="close-plan-detail">&times;</button>
        </div>

        <div class="plan-detail-stack">
            <div>
                <div class="plan-detail-label">Target Dokter</div>
                <div id="planDetailDoctorName" class="plan-detail-primary">-</div>
                <div id="planDetailDoctorSub" class="plan-detail-sub">-</div>
            </div>

            <div>
                <div class="plan-detail-label">Jadwal</div>
                <div id="planDetailSchedule" class="plan-detail-value">-</div>
            </div>

            <div>
                <div class="plan-detail-label">Status</div>
                <div class="plan-detail-value">
                    <span class="status-badge status-cancelled">Dibatalkan</span>
                </div>
            </div>

            <div class="plan-reason-box">
                <div class="plan-reason-label">Alasan Pembatalan:</div>
                <div id="planDetailReason" class="plan-reason-text">-</div>
            </div>

            <div id="planDetailCancelledAtWrap" class="plan-cancel-time">
                Waktu pembatalan: <strong id="planDetailCancelledAt" class="tabular-nums">-</strong>
            </div>

            <div class="plan-detail-actions">
                <button type="button" class="btn btn-outline btn-sm" data-action="close-plan-detail">Tutup</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
@vite('resources/css/pages/call-plans.css')
@endpush

@push('scripts')
@vite('resources/js/pages/call-plans.js')
@endpush
@endsection
