@extends('layouts.app')

@section('title', 'Jadwal Kunjungan')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <!-- Header Section -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; color: var(--secondary); letter-spacing: -0.02em; margin-bottom: 0.15rem;">Jadwal Kunjungan</h1>
            <p style="color: var(--text-muted); font-size: 0.8125rem;">Jadwal dari target dokter yang sudah disetujui.</p>
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
    <div style="background: #ffffff; border: 1px solid var(--border); border-radius: 6px; padding: 0.75rem 1rem; display: flex; flex-wrap: wrap; gap: 0.85rem; align-items: center; justify-content: space-between;">
        <div style="display: flex; flex-wrap: wrap; gap: 0.65rem; align-items: center;">
            <label for="filterDateInput" style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">
                Tanggal:
            </label>
            <input type="date" id="filterDateInput" 
                   style="padding: 0.35rem 0.55rem; border: 1px solid var(--border-strong); border-radius: 5px; font-size: 0.825rem; min-height: 36px;">
            <div style="display: flex; gap: 0.25rem;">
                <button type="button" class="btn btn-outline btn-sm" data-action="quick-date" data-date="all">Semua</button>
                <button type="button" class="btn btn-outline btn-sm" data-action="quick-date" data-date="today">Hari Ini</button>
                <button type="button" class="btn btn-outline btn-sm" data-action="quick-date" data-date="tomorrow">Besok</button>
            </div>
        </div>

        <div style="display: flex; gap: 0.65rem; align-items: center;">
            <label for="filterStatusSelect" style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">
                Status:
            </label>
            <select id="filterStatusSelect" 
                    style="padding: 0.35rem 0.55rem; border: 1px solid var(--border-strong); border-radius: 5px; font-size: 0.825rem; min-height: 36px;">
                <option value="">Semua Status</option>
                <option value="scheduled">Terjadwal</option>
                <option value="completed">Selesai</option>
                <option value="expired">Tidak Terlaksana</option>
                <option value="cancelled">Dibatalkan</option>
            </select>
        </div>
    </div>

    <!-- Plan List Container -->
    <div id="planListContainer" style="display: flex; flex-direction: column; gap: 1rem;">
        <div id="loadingPlans" style="padding: 0.25rem 0;"></div>

        <div id="planTableWrap" class="table-responsive" style="display: none;">
            <table class="enterprise-table">
                <thead>
                    <tr>
                        <th>Jadwal Kunjungan</th>
                        <th>Target Dokter</th>
                        <th>Spesialisasi & Faskes</th>
                        <th>Status</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="planTableBody"></tbody>
            </table>
        </div>

        <div id="emptyPlans" style="display: none; text-align: center; padding: 3rem 1.5rem; background: white; border: 1px solid var(--border); border-radius: 8px;">
            <h4 style="font-size: 1rem; font-weight: 700; color: var(--secondary); margin-bottom: 0.35rem;">Belum Ada Jadwal</h4>
            @if(auth()->user()->role === 'mr')
            <p style="color: var(--text-muted); font-size: 0.875rem; max-width: 420px; margin: 0 auto 1.25rem;">
                Pilih target dokter yang sudah disetujui, lalu tentukan tanggal dan jam kunjungan.
            </p>
            <button type="button" class="btn btn-primary" data-action="open-create-plan">+ Buat Jadwal</button>
            @else
            <p style="color: var(--text-muted); font-size: 0.875rem; max-width: 420px; margin: 0 auto;">
                Belum ada jadwal yang tercatat.
            </p>
            @endif
        </div>
    </div>
</div>


<div id="createPlanModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card" style="max-width: 520px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1.15rem; font-weight: 700;">Buat Jadwal</h3>
            <button type="button" class="close-btn" data-action="close-create-plan">&times;</button>
        </div>

        <div id="createPlanModalError" style="display: none;" class="alert alert-danger"></div>

        <form id="createPlanForm">
            
            <div style="margin-bottom: 1rem;">
                <label for="callListSelect" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">
                    Target Dokter Bulanan
                </label>
                <select id="callListSelect" required 
                        style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.9rem;">
                    <option value="">-- Memuat target dokter... --</option>
                </select>
                <small style="color: var(--text-muted); font-size: 0.75rem;">
                    Hanya target dokter yang sudah disetujui yang dapat dijadwalkan.
                </small>
            </div>

            
            <div style="margin-bottom: 1rem;">
                <label for="doctorSelect" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">
                    Target Dokter
                </label>
                <select id="doctorSelect" required disabled 
                        style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.9rem;">
                    <option value="">-- Pilih target bulanan terlebih dahulu --</option>
                </select>
            </div>

            <!-- 3. Tanggal Kunjungan -->
            <div style="margin-bottom: 1rem;">
                <label for="planDateInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">
                    Tanggal Kunjungan
                </label>
                <input type="date" id="planDateInput" required 
                       style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.9rem;">
                <small id="dateRangeHelp" style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 0.25rem;">
                    Tanggal mengikuti periode target dokter.
                </small>
                
                <div id="availabilityNotice" style="display: none; margin-top: 0.5rem; font-size: 0.8rem; padding: 0.6rem 0.85rem; border-radius: 0.375rem; line-height: 1.4;"></div>
            </div>

            <!-- 4. Jam Kunjungan -->
            <div style="margin-bottom: 1.5rem;">
                <label for="plannedTimeInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">
                    Jam Kunjungan
                </label>
                <input type="text" id="plannedTimeInput" required value="09.00" maxlength="5" inputmode="numeric" placeholder="09.00"
                       style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.9rem; font-variant-numeric: tabular-nums;"
                       oninput="window.autoFormatTimeInput(this)" onblur="window.normalizeTime24(this)">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-outline" data-action="close-create-plan">Batal</button>
                <button type="submit" id="btnSubmitPlan" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>


<div id="cancelPlanModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--danger); margin: 0;">Batalkan Jadwal</h3>
            <button type="button" class="close-btn" data-action="close-cancel-plan">&times;</button>
        </div>

        <div id="cancelPlanModalError" style="display: none;" class="alert alert-danger"></div>

        <p style="font-size: 0.875rem; color: var(--secondary); margin-bottom: 1rem; line-height: 1.5;">
            Batalkan jadwal kunjungan ke <strong id="cancelPlanDoctorName">-</strong> pada tanggal <strong id="cancelPlanDate">-</strong>?
        </p>

        <form id="cancelPlanForm">
            <input type="hidden" id="cancelPlanId">
            <div style="margin-bottom: 1.25rem;">
                <label for="cancelPlanReasonInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--secondary);">
                    Alasan Pembatalan
                </label>
                <textarea id="cancelPlanReasonInput" required minlength="3" rows="3"
                          style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.875rem;"
                          placeholder="Contoh: Dokter ada simposium / operasi darurat di luar kota"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-outline" data-action="close-cancel-plan">Kembali</button>
                <button type="submit" id="btnConfirmCancelPlan" class="btn btn-danger" style="background: var(--danger); color: white; border: none; font-weight: 600;">
                    Batalkan Jadwal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detail Jadwal -->
<div id="planDetailModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card" style="max-width: 480px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--secondary); margin: 0;">Detail Jadwal</h3>
            <button type="button" class="close-btn" data-action="close-plan-detail">&times;</button>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.85rem; font-size: 0.875rem;">
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Target Dokter</div>
                <div id="planDetailDoctorName" style="font-weight: 700; color: var(--secondary); font-size: 1rem; margin-top: 0.15rem;">-</div>
                <div id="planDetailDoctorSub" style="color: var(--text-muted); font-size: 0.8rem;">-</div>
            </div>

            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Jadwal</div>
                <div id="planDetailSchedule" style="font-weight: 600; color: var(--secondary); margin-top: 0.15rem;">-</div>
            </div>

            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Status</div>
                <div style="margin-top: 0.15rem;">
                    <span class="status-badge status-cancelled">Dibatalkan</span>
                </div>
            </div>

            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 6px; padding: 0.75rem;">
                <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; margin-bottom: 0.25rem;">Alasan Pembatalan:</div>
                <div id="planDetailReason" style="color: var(--secondary); font-style: italic; line-height: 1.4;">-</div>
            </div>

            <div id="planDetailCancelledAtWrap" style="font-size: 0.75rem; color: var(--text-muted);">
                Waktu pembatalan: <strong id="planDetailCancelledAt" class="tabular-nums">-</strong>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 0.5rem; border-top: 1px solid var(--border); padding-top: 0.75rem;">
                <button type="button" class="btn btn-outline btn-sm" data-action="close-plan-detail">Tutup</button>
            </div>
        </div>
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
    .status-scheduled { background: #f0f9ff; color: #0369a1; border-color: #bae6fd; }
    .status-completed { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
    .status-cancelled { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
    .status-expired { background: #f8fafc; color: #475569; border-color: #cbd5e1; }


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

    function formatWaktuIndonesia(timeOrDate) {
        if (!timeOrDate) return '-';
        if (timeOrDate instanceof Date) {
            const h = String(timeOrDate.getHours()).padStart(2, '0');
            const m = String(timeOrDate.getMinutes()).padStart(2, '0');
            return `${h}.${m} WIB`;
        }
        if (typeof timeOrDate === 'string') {
            const trimmed = timeOrDate.trim();
            const timeMatch = trimmed.match(/^(\d{1,2}):(\d{2})/);
            if (timeMatch) {
                const h = timeMatch[1].padStart(2, '0');
                const m = timeMatch[2];
                return `${h}.${m} WIB`;
            }
            const d = new Date(trimmed);
            if (!isNaN(d.getTime())) {
                const h = String(d.getHours()).padStart(2, '0');
                const m = String(d.getMinutes()).padStart(2, '0');
                return `${h}.${m} WIB`;
            }
        }
        return String(timeOrDate);
    }

    document.addEventListener('DOMContentLoaded', () => {
        bindPlanEvents();
        loadPlans();
        loadApprovedCallLists();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeCreatePlanModal();
            closeCancelPlanModal();
            closePlanDetailModal();
        }
    });

    function showAlert(message, type = 'success') {
        window.showToast(message, type);
    }

    function bindPlanEvents() {
        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-action]');
            if (!trigger) return;

            switch (trigger.dataset.action) {
                case 'open-create-plan':
                    openCreatePlanModal();
                    break;
                case 'close-create-plan':
                    closeCreatePlanModal();
                    break;
                case 'quick-date':
                    setQuickDate(trigger.dataset.date);
                    break;
                case 'open-cancel-plan':
                    openCancelPlanModal(parseInt(trigger.dataset.planId, 10));
                    break;
                case 'close-cancel-plan':
                    closeCancelPlanModal();
                    break;
                case 'open-plan-detail':
                    openPlanDetailModal(parseInt(trigger.dataset.planId, 10));
                    break;
                case 'close-plan-detail':
                    closePlanDetailModal();
                    break;
                default:
                    break;
            }
        });

        document.getElementById('filterDateInput')?.addEventListener('change', applyFilters);
        document.getElementById('filterStatusSelect')?.addEventListener('change', applyFilters);
        document.getElementById('callListSelect')?.addEventListener('change', onCallListChange);
        document.getElementById('doctorSelect')?.addEventListener('change', onDoctorChange);
        document.getElementById('planDateInput')?.addEventListener('change', onPlanDateChange);
        document.getElementById('createPlanForm')?.addEventListener('submit', handleCreatePlanSubmit);
        document.getElementById('cancelPlanForm')?.addEventListener('submit', handleCancelPlanSubmit);
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
        loading.innerHTML = window.renderTableSkeleton(3);
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
            currentPlans = (result && result.success && Array.isArray(result.data)) ? result.data : [];
        } catch (err) {
            currentPlans = [];
            console.error('Gagal memuat jadwal', err);
        } finally {
            loading.style.display = 'none';
            renderPlansGrid();
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
            const isCancelled = plan.status === 'cancelled';
            const isExpired = plan.status === 'expired';
            let statusLabel = 'Terjadwal';
            if (isCompleted) statusLabel = 'Selesai';
            if (isCancelled) statusLabel = 'Dibatalkan';
            if (isExpired) statusLabel = 'Tidak Terlaksana';

            const safeStatus = ['scheduled', 'completed', 'cancelled', 'expired'].includes(plan.status) ? plan.status : 'scheduled';
            const statusClass = `status-${safeStatus}`;

            const dateObj = new Date(plan.plan_date + 'T' + plan.planned_time);
            const dateStr = dateObj.toLocaleDateString('id-ID', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
            const timeStr = formatWaktuIndonesia(plan.planned_time);

            return `
                <tr>
                    <td style="white-space: nowrap;">
                        <strong style="color: var(--secondary); font-size: 0.9rem;">${dateStr}</strong>
                        <div style="font-size: 0.775rem; color: var(--primary); font-weight: 600;">${timeStr}</div>
                    </td>
                    <td style="white-space: nowrap;">
                        <strong style="font-size: 0.95rem; color: var(--secondary);">${escapeHtml(plan.doctor_name || 'Dokter')}</strong>
                    </td>
                    <td>
                        <div style="font-weight: 500;">${escapeHtml(plan.doctor_specialization || 'Spesialis Umum')}</div>
                        <small style="color: var(--text-muted); font-size: 0.75rem;">${escapeHtml(plan.doctor_address || '-')}</small>
                    </td>
                    <td>
                        <span class="status-badge ${statusClass}">${escapeHtml(statusLabel)}</span>
                        ${isCancelled && plan.cancellation_reason ? `
                            <div style="font-size: 0.725rem; color: #64748b; margin-top: 0.3rem; line-height: 1.35;">
                                <span style="color: #991b1b; font-weight: 500;">Alasan:</span> <span style="font-style: italic; color: #334155;">"${escapeHtml(plan.cancellation_reason)}"</span>
                            </div>
                        ` : ''}
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        ${plan.status === 'scheduled' ? `
                            <div style="display: inline-flex; gap: 0.35rem; justify-content: flex-end; align-items: center;">
                                <a href="/call-actuals?plan_id=${plan.id}&doctor_id=${plan.doctor_id}" class="btn btn-primary btn-sm" style="text-decoration: none;">
                                    Isi Laporan
                                </a>
                                <button type="button" class="btn btn-outline btn-sm" style="color: var(--danger); border-color: #fecdd3;" data-action="open-cancel-plan" data-plan-id="${plan.id}">
                                    Batalkan
                                </button>
                            </div>
                        ` : (isCompleted ? `
                            <a href="/call-actuals" class="btn btn-outline btn-sm" style="font-size: 0.775rem; color: var(--primary); border-color: #cbd5e1; background: #f8fafc; font-weight: 600; text-decoration: none;">
                                Lihat Laporan
                            </a>
                        ` : (isExpired ? `
                            <button type="button" class="btn btn-outline btn-sm" data-action="open-create-plan" style="font-size: 0.775rem; color: var(--primary); border-color: #cbd5e1; background: #f8fafc; font-weight: 600;">
                                Jadwalkan Ulang
                            </button>
                        ` : `
                            <button type="button" class="btn btn-outline btn-sm" data-action="open-plan-detail" data-plan-id="${plan.id}" style="font-size: 0.775rem; color: #475569; border-color: #cbd5e1; background: #f8fafc; font-weight: 600;">
                                Detail Batal
                            </button>
                        `))}
                    </td>
                </tr>
            `;
        }).join('');
    }

    async function loadApprovedCallLists() {
        const select = document.getElementById('callListSelect');
        try {
            const res = await window.apiFetch('/api/call-lists?status=approved');
            const result = await res.json();
            if (result.success) {
                approvedCallLists = result.data;
                if (!approvedCallLists.length) {
                    select.innerHTML = '<option value="">-- Belum ada target dokter yang disetujui --</option>';
                    return;
                }
                select.innerHTML = '<option value="">-- Pilih target bulanan --</option>' +
                    approvedCallLists.map(cl => `
                        <option value="${cl.id}" data-period="${escapeHtml(cl.period_month)}">
                            Periode ${escapeHtml(cl.period_month)} (${cl.doctor_count} Dokter)
                        </option>
                    `).join('');
            }
        } catch (err) {
            select.innerHTML = '<option value="">Gagal memuat target</option>';
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
            doctorSelect.innerHTML = '<option value="">-- Pilih target bulanan terlebih dahulu --</option>';
            doctorSelect.disabled = true;
            planDateInput.removeAttribute('min');
            planDateInput.removeAttribute('max');
            dateHelp.innerText = 'Tanggal mengikuti periode target dokter.';
            return;
        }

        // Set batasan tanggal sesuai periode target
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
        doctorSelect.innerHTML = '<option value="">Memuat dokter target...</option>';

        try {
            const res = await window.apiFetch(`/api/call-lists/${callListId}`);
            const result = await res.json();
            if (result.success) {
                currentCallListDoctors = result.data.doctors;
                if (!currentCallListDoctors.length) {
                    doctorSelect.innerHTML = '<option value="">Tidak ada dokter di target ini</option>';
                    return;
                }
                doctorSelect.innerHTML = '<option value="">-- Pilih Target Dokter --</option>' +
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

    let currentDoctorBusyDates = [];

    async function onDoctorChange() {
        const doctorSelect = document.getElementById('doctorSelect');
        const doctorId = doctorSelect.value;
        const callListSelect = document.getElementById('callListSelect');
        const selectedOption = callListSelect.options[callListSelect.selectedIndex];
        const periodMonth = selectedOption ? selectedOption.getAttribute('data-period') : '';
        const noticeEl = document.getElementById('availabilityNotice');

        currentDoctorBusyDates = [];
        if (!doctorId || !periodMonth) {
            noticeEl.style.display = 'none';
            return;
        }

        noticeEl.style.display = 'block';
        noticeEl.style.background = '#f1f5f9';
        noticeEl.style.borderColor = '#cbd5e1';
        noticeEl.style.color = 'var(--text-muted)';
        noticeEl.innerHTML = 'Memeriksa ketersediaan jadwal dokter...';

        try {
            const res = await window.apiFetch(`/api/doctors/${doctorId}/availability?month=${periodMonth}`);
            const result = await res.json();
            if (result.success && result.data) {
                currentDoctorBusyDates = result.data.busy_dates || [];
                renderAvailabilityNotice();
                onPlanDateChange();
            } else {
                noticeEl.style.display = 'none';
            }
        } catch (err) {
            noticeEl.style.display = 'none';
        }
    }

    function renderAvailabilityNotice() {
        const noticeEl = document.getElementById('availabilityNotice');
        if (!currentDoctorBusyDates.length) {
            noticeEl.style.display = 'block';
            noticeEl.style.background = '#f0fdf4';
            noticeEl.style.borderColor = '#bbf7d0';
            noticeEl.style.color = '#166534';
            noticeEl.innerHTML = '✓ Seluruh tanggal pada bulan ini masih tersedia untuk target dokter ini.';
            return;
        }

        const formattedDays = currentDoctorBusyDates.map(d => parseInt(d.split('-')[2])).join(', ');
        noticeEl.style.display = 'block';
        noticeEl.style.background = '#fffbeb';
        noticeEl.style.borderColor = '#fde68a';
        noticeEl.style.color = '#92400e';
        noticeEl.innerHTML = `⚠️ <strong>Tanggal terisi:</strong> Tanggal ${formattedDays} sudah ada jadwal/kunjungan dokter ini. Pilih tanggal lain agar tidak bentrok.`;
    }

    function onPlanDateChange() {
        const planDateInput = document.getElementById('planDateInput');
        const errEl = document.getElementById('createPlanModalError');
        const btn = document.getElementById('btnSubmitPlan');
        const selectedDate = planDateInput.value;

        if (!selectedDate || !currentDoctorBusyDates.length) {
            if (errEl.innerText.includes('sudah memiliki jadwal')) {
                errEl.style.display = 'none';
            }
            btn.disabled = false;
            return;
        }

        if (currentDoctorBusyDates.includes(selectedDate)) {
            errEl.innerText = `Dokter sudah memiliki jadwal/kunjungan pada tanggal ${selectedDate}. Silakan pilih tanggal lain.`;
            errEl.style.display = 'block';
            btn.disabled = true;
        } else {
            if (errEl.innerText.includes('sudah memiliki jadwal')) {
                errEl.style.display = 'none';
            }
            btn.disabled = false;
        }
    }

    function openCreatePlanModal() {
        const errEl = document.getElementById('createPlanModalError');
        if (errEl) {
            errEl.style.display = 'none';
            errEl.innerText = '';
        }
        const noticeEl = document.getElementById('availabilityNotice');
        if (noticeEl) {
            noticeEl.style.display = 'none';
            noticeEl.innerHTML = '';
        }
        currentDoctorBusyDates = [];
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
        let plannedTime = (document.getElementById('plannedTimeInput').value || '').trim().replace('.', ':');
        if (/^\d:\d{2}/.test(plannedTime)) {
            plannedTime = '0' + plannedTime;
        }

        if (!callListId) {
            errEl.innerText = 'Pilih target dokter bulanan yang sudah disetujui.';
            errEl.style.display = 'block';
            return;
        }

        if (!doctorId) {
            errEl.innerText = 'Pilih target dokter.';
            errEl.style.display = 'block';
            return;
        }

        if (!planDate) {
            errEl.innerText = 'Tanggal wajib diisi.';
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
            btn.innerText = 'Simpan';

            if (data.success) {
                closeCreatePlanModal();
                showAlert('Jadwal disimpan.');
                loadPlans();
            } else {
                errEl.innerText = data.message || 'Gagal menyimpan jadwal.';
                errEl.style.display = 'block';
            }
        } catch (err) {
            btn.disabled = false;
            btn.innerText = 'Simpan';
            errEl.innerText = 'Terjadi kesalahan jaringan.';
            errEl.style.display = 'block';
        }
    }

    function openCancelPlanModal(planId) {
        const plan = currentPlans.find(p => p.id === planId);
        if (!plan) return;

        document.getElementById('cancelPlanId').value = plan.id;
        document.getElementById('cancelPlanDoctorName').innerText = plan.doctor_name || 'Dokter';
        document.getElementById('cancelPlanDate').innerText = plan.plan_date || '-';
        document.getElementById('cancelPlanReasonInput').value = '';
        const errEl = document.getElementById('cancelPlanModalError');
        errEl.style.display = 'none';
        errEl.innerText = '';
        document.getElementById('cancelPlanModal').style.display = 'flex';
    }

    function closeCancelPlanModal() {
        document.getElementById('cancelPlanModal').style.display = 'none';
    }

    async function handleCancelPlanSubmit(e) {
        e.preventDefault();
        const planId = document.getElementById('cancelPlanId').value;
        const reason = document.getElementById('cancelPlanReasonInput').value.trim();
        const errEl = document.getElementById('cancelPlanModalError');
        const btn = document.getElementById('btnConfirmCancelPlan');

        errEl.style.display = 'none';

        if (reason.length < 3) {
            errEl.innerText = 'Alasan pembatalan wajib diisi minimal 3 karakter.';
            errEl.style.display = 'block';
            return;
        }

        btn.disabled = true;
        btn.innerText = 'Membatalkan...';

        try {
            const res = await window.apiFetch(`/api/call-plans/${planId}/cancel`, {
                method: 'PATCH',
                body: JSON.stringify({ reason: reason })
            });
            const result = await res.json();
            btn.disabled = false;
            btn.innerText = 'Batalkan Jadwal';

            if (result.success) {
                closeCancelPlanModal();
                showAlert('Jadwal dibatalkan.');
                loadPlans();
            } else {
                errEl.innerText = result.message || 'Gagal membatalkan jadwal.';
                errEl.style.display = 'block';
            }
        } catch (err) {
            btn.disabled = false;
            btn.innerText = 'Batalkan Jadwal';
            errEl.innerText = 'Gagal membatalkan jadwal. Coba lagi.';
            errEl.style.display = 'block';
        }
    }

    function openPlanDetailModal(planId) {
        const plan = currentPlans.find(p => p.id === planId);
        if (!plan) return;

        document.getElementById('planDetailDoctorName').innerText = plan.doctor_name || 'Dokter';
        document.getElementById('planDetailDoctorSub').innerText = `${plan.doctor_specialization || 'Spesialis Umum'} • ${plan.doctor_address || '-'}`;

        const dateObj = new Date(plan.plan_date + 'T' + plan.planned_time);
        const dateStr = dateObj.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        const timeStr = formatWaktuIndonesia(plan.planned_time);
        document.getElementById('planDetailSchedule').innerText = `${dateStr} pukul ${timeStr}`;

        document.getElementById('planDetailReason').innerText = plan.cancellation_reason || '-';

        const cancelledAtEl = document.getElementById('planDetailCancelledAt');
        const cancelledAtWrap = document.getElementById('planDetailCancelledAtWrap');
        if (plan.cancelled_at) {
            const cDate = new Date(plan.cancelled_at);
            cancelledAtEl.innerText = cDate.toLocaleString('id-ID');
            cancelledAtWrap.style.display = 'block';
        } else {
            cancelledAtWrap.style.display = 'none';
        }

        document.getElementById('planDetailModal').style.display = 'flex';
    }

    function closePlanDetailModal() {
        document.getElementById('planDetailModal').style.display = 'none';
    }
</script>
@endpush
@endsection
