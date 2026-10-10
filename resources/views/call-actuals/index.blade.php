@extends('layouts.app')

@section('title', 'Call Actual')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <!-- Header Section -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--secondary);">Realisasi Kunjungan (Call Actual)</h1>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Pelaporan bukti kunjungan dokter, tanda tangan digital, dan produk farmasi yang dipromosikan.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <button type="button" class="btn btn-outline" onclick="openWithoutPlanModal()">
                + Kunjungan di Luar Rencana
            </button>
        </div>
    </div>

    <!-- Alert Banner inside view -->
    <div id="actualStatusAlert" style="display: none;" class="alert"></div>

    <!-- Section 1: Antrean Jadwal Kunjungan (Planned-First Principle) -->
    <div style="background: white; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1.25rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
            <div>
                <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--secondary);">Jadwal Kunjungan Siap Dilakukan</h2>
                <p style="font-size: 0.8rem; color: var(--text-muted);">Pilih rencana kunjungan dari Call Plan untuk memulai pelaporan kunjungan dokter.</p>
            </div>
            <a href="{{ route('call-plans.index') }}" style="font-size: 0.85rem; color: var(--primary); text-decoration: none; font-weight: 600;">
                Kelola Jadwal &rarr;
            </a>
        </div>

        <div id="loadingScheduledPlans" style="text-align: center; padding: 1.5rem; color: var(--text-muted);">
            Memuat jadwal kunjungan...
        </div>

        <div id="scheduledPlansGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem;"></div>

        <div id="emptyScheduledPlans" style="display: none; text-align: center; padding: 2rem; background: #f8fafc; border-radius: 0.375rem; border: 1px dashed var(--border);">
            <p style="font-size: 0.875rem; color: var(--text-muted);">Tidak ada jadwal kunjungan aktif yang menunggu realisasi saat ini.</p>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                Gunakan tombol <strong>+ Kunjungan di Luar Rencana</strong> di atas jika Anda melakukan kunjungan mendadak.
            </p>
        </div>
    </div>

    <!-- Section 2: Riwayat Kunjungan Selesai (Completed Actuals) -->
    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
            <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--secondary);">
                Riwayat Kunjungan Selesai (<span id="actualCount">0</span>)
            </h2>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <label for="filterActualDate" style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">Filter Tanggal:</label>
                <input type="date" id="filterActualDate" onchange="loadActuals()"
                       style="padding: 0.35rem 0.5rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.85rem;">
                <button type="button" class="btn btn-outline" style="min-height: 36px; padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="resetActualFilter()">Semua</button>
            </div>
        </div>

        <div id="loadingActuals" style="text-align: center; padding: 2rem; color: var(--text-muted);">
            Memuat riwayat kunjungan...
        </div>

        <div id="actualsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem;"></div>

        <div id="emptyActuals" style="display: none; text-align: center; padding: 3rem; background: white; border: 1px dashed var(--border); border-radius: 0.5rem;">
            <p style="color: var(--text-muted); font-size: 0.95rem;">Belum ada riwayat kunjungan dokter yang tercatat.</p>
        </div>
    </div>
</div>

<!-- MODAL: Formulir Realisasi Kunjungan (Call Actual Modal) -->
<div id="actualModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card" style="max-width: 580px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 id="modalTitle" style="font-size: 1.15rem; font-weight: 700;">Catat Realisasi Kunjungan</h3>
            <button type="button" class="close-btn" onclick="closeActualModal()">&times;</button>
        </div>

        <div id="actualModalError" style="display: none;" class="alert alert-danger"></div>

        <form id="callActualForm" onsubmit="handleActualSubmit(event)">
            <input type="hidden" id="formPlanId" value="">
            <input type="hidden" id="formOriginalPlanDate" value="">

            <!-- Informasi Mode Kunjungan -->
            <div id="visitTypeIndicator" style="margin-bottom: 1rem; padding: 0.5rem 0.75rem; background: #f0fdfa; border: 1px solid #ccfbf1; border-radius: 0.375rem; font-size: 0.825rem; color: #0f766e;">
                Mode: <strong>Kunjungan Terencana (Planned)</strong>
            </div>

            <!-- Dokter Sasaran -->
            <div style="margin-bottom: 1rem;" id="doctorSelectionGroup">
                <label for="formDoctorSelect" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">
                    Dokter Sasaran:
                </label>
                <!-- Jika Planned: teks display read-only -->
                <div id="plannedDoctorDisplay" style="display: none; padding: 0.55rem 0.75rem; background: #f8fafc; border: 1px solid var(--border); border-radius: 0.375rem; font-weight: 700; font-size: 0.9rem;"></div>
                <input type="hidden" id="plannedDoctorId" value="">

                <!-- Jika Without Plan: dropdown dokter MCL -->
                <select id="formDoctorSelect"
                        style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.9rem;">
                    <option value="">-- Memuat dokter MCL... --</option>
                </select>
            </div>

            <!-- Tanggal & Waktu Kunjungan -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                <div>
                    <label for="formVisitDate" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">
                        Tanggal Kunjungan:
                    </label>
                    <input type="date" id="formVisitDate" required onchange="checkDateDeviation()"
                           style="width: 100%; padding: 0.5rem 0.65rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.875rem;">
                </div>
                <div style="display: flex; flex-direction: column; justify-content: flex-end;">
                    <button type="button" class="btn btn-outline" style="min-height: 40px; font-size: 0.775rem;" onclick="fillCurrentTimes()">
                        Gunakan Jam Sekarang
                    </button>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                <div>
                    <label for="formCheckIn" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">
                        Waktu Check-in (Mulai):
                    </label>
                    <input type="time" id="formCheckIn" required value="09:00"
                           style="width: 100%; padding: 0.5rem 0.65rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.875rem;">
                </div>
                <div>
                    <label for="formCheckOut" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">
                        Waktu Check-out (Selesai):
                    </label>
                    <input type="time" id="formCheckOut" required value="09:30"
                           style="width: 100%; padding: 0.5rem 0.65rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.875rem;">
                </div>
            </div>

            <!-- Alasan Deviasi Tanggal (Hanya muncul jika Planned dan tanggal bergeser) -->
            <div id="deviationReasonGroup" style="display: none; margin-bottom: 1rem;">
                <label for="formDeviationReason" style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--danger); margin-bottom: 0.35rem;">
                    Alasan Pergeseran Tanggal Rencana (Wajib):
                </label>
                <input type="text" id="formDeviationReason" placeholder="Contoh: Dokter meminta jadwal dimajukan karena ada operasi."
                       style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--danger); border-radius: 0.375rem; font-size: 0.875rem;">
            </div>

            <!-- Alasan Kunjungan (Hanya jika Kunjungan di Luar Rencana) -->
            <div id="visitReasonGroup" style="display: none; margin-bottom: 1rem;">
                <label for="formVisitReason" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">
                    Alasan Kunjungan di Luar Rencana (Opsional):
                </label>
                <input type="text" id="formVisitReason" placeholder="Contoh: Kunjungan darurat atas permintaan kepala klinik."
                       style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.875rem;">
            </div>

            <!-- Bukti Foto Kunjungan (PDF-04 Wajib) -->
            <div style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                    <label for="formPhotoUrl" style="font-size: 0.85rem; font-weight: 600;">
                        Foto Bukti Kunjungan (URL Wajib):
                    </label>
                    <button type="button" class="btn btn-outline" style="min-height: 28px; padding: 0.15rem 0.45rem; font-size: 0.725rem;"
                            onclick="fillSamplePhoto()">
                        Gunakan Foto Sampel
                    </button>
                </div>
                <input type="url" id="formPhotoUrl" required placeholder="https://example.test/uploads/foto-kunjungan.jpg"
                       onchange="updatePhotoPreview()"
                       style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.875rem;">
                <div id="photoPreviewContainer" style="display: none; margin-top: 0.5rem;">
                    <img id="photoPreviewImg" src="" alt="Pratinjau Foto" style="height: 90px; border-radius: 0.25rem; object-fit: cover; border: 1px solid var(--border);">
                </div>
            </div>

            <!-- Tanda Tangan Digital Dokter (Wajib) -->
            <div style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                    <label for="formSignatureUrl" style="font-size: 0.85rem; font-weight: 600;">
                        Tanda Tangan Digital Dokter (URL Wajib):
                    </label>
                    <button type="button" class="btn btn-outline" style="min-height: 28px; padding: 0.15rem 0.45rem; font-size: 0.725rem;"
                            onclick="fillSampleSignature()">
                        Gunakan Tanda Tangan Sampel
                    </button>
                </div>
                <input type="url" id="formSignatureUrl" required placeholder="https://example.test/uploads/ttd-dokter.png"
                       onchange="updateSignaturePreview()"
                       style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.875rem;">
                <div id="sigPreviewContainer" style="display: none; margin-top: 0.5rem;">
                    <img id="sigPreviewImg" src="" alt="Pratinjau TTD" style="height: 60px; background: white; padding: 0.25rem; border-radius: 0.25rem; border: 1px solid var(--border);">
                </div>
            </div>

            <!-- Detailing Produk Farmasi (Wajib Minimal 1) -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">
                    Produk Farmasi yang Dipromosikan (Pilih Minimal 1 Produk):
                </label>
                <div id="productChecklist" style="max-height: 160px; overflow-y: auto; border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.5rem; display: flex; flex-direction: column; gap: 0.4rem; background: #fafafa;">
                    <span style="font-size: 0.825rem; color: var(--text-muted);">Memuat produk farmasi...</span>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeActualModal()">Batal</button>
                <button type="submit" id="btnSubmitActual" class="btn btn-primary">Simpan Realisasi Kunjungan</button>
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
    .actual-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 0.75rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .status-badge {
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
        text-transform: uppercase;
        display: inline-block;
    }
    .badge-planned { background: #dcfce7; color: #166534; }
    .badge-unplanned { background: #fef3c7; color: #92400e; }
    .badge-non_target { background: #f1f5f9; color: #475569; }

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
        max-width: 580px;
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
    let scheduledPlans = [];
    let completedActuals = [];
    let allProducts = [];
    let allMclDoctors = [];

    document.addEventListener('DOMContentLoaded', () => {
        loadScheduledPlans();
        loadActuals();
        loadProducts();
        loadMclDoctors();

        // Cek URL params jika datang dari halaman Call Plan
        const urlParams = new URLSearchParams(window.location.search);
        const planId = urlParams.get('plan_id');
        const doctorId = urlParams.get('doctor_id');
        if (planId && doctorId) {
            setTimeout(() => {
                startPlannedVisit(parseInt(planId), parseInt(doctorId));
            }, 300);
        }
    });

    // R-32 Keyboard Accessibility: Escape key closes modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeActualModal();
        }
    });

    function showAlert(message, type = 'success') {
        const alertEl = document.getElementById('actualStatusAlert');
        alertEl.className = `alert alert-${type}`;
        alertEl.innerText = message;
        alertEl.style.display = 'block';
        setTimeout(() => { alertEl.style.display = 'none'; }, 4000);
    }

    async function loadScheduledPlans() {
        const loading = document.getElementById('loadingScheduledPlans');
        loading.style.display = 'block';

        try {
            const res = await window.apiFetch('/api/call-plans?status=scheduled');
            const result = await res.json();
            loading.style.display = 'none';

            if (result.success) {
                scheduledPlans = result.data;
                renderScheduledPlansGrid();
            }
        } catch (err) {
            loading.style.display = 'none';
            console.error('Gagal memuat jadwal', err);
        }
    }

    function renderScheduledPlansGrid() {
        const grid = document.getElementById('scheduledPlansGrid');
        const empty = document.getElementById('emptyScheduledPlans');

        if (!scheduledPlans.length) {
            grid.innerHTML = '';
            empty.style.display = 'block';
            return;
        }

        empty.style.display = 'none';
        grid.innerHTML = scheduledPlans.map(plan => `
            <div class="actual-card" style="border-left: 4px solid var(--primary);">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                        <span style="font-size: 0.8rem; font-weight: 700; color: var(--primary);">${plan.plan_date}</span>
                        <span class="status-badge" style="background: #e0f2fe; color: #0369a1;">Pukul ${plan.planned_time.substring(0, 5)} WIB</span>
                    </div>
                    <div style="font-size: 1.05rem; font-weight: 700;">${plan.doctor_name}</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.15rem;">
                        ${plan.doctor_specialization || 'Spesialis Umum'}
                    </div>
                    <div style="font-size: 0.775rem; color: #64748b; margin-top: 0.25rem;">
                        ${plan.doctor_address || '-'}
                    </div>
                </div>
                <div style="margin-top: 0.5rem; border-top: 1px solid var(--border); padding-top: 0.75rem; display: flex; justify-content: flex-end;">
                    <button type="button" class="btn btn-primary" style="font-size: 0.85rem; padding: 0.4rem 0.85rem;"
                            onclick="startPlannedVisit(${plan.id}, ${plan.doctor_id}, '${plan.doctor_name}', '${plan.plan_date}')">
                        Mulai Kunjungan
                    </button>
                </div>
            </div>
        `).join('');
    }

    async function loadActuals() {
        const loading = document.getElementById('loadingActuals');
        loading.style.display = 'block';

        const filterDate = document.getElementById('filterActualDate').value;
        const query = filterDate ? `?date=${encodeURIComponent(filterDate)}` : '';

        try {
            const res = await window.apiFetch(`/api/call-actuals${query}`);
            const result = await res.json();
            loading.style.display = 'none';

            if (result.success) {
                completedActuals = result.data;
                document.getElementById('actualCount').innerText = completedActuals.length;
                renderActualsGrid();
            }
        } catch (err) {
            loading.style.display = 'none';
            console.error('Gagal memuat riwayat kunjungan', err);
        }
    }

    function renderActualsGrid() {
        const grid = document.getElementById('actualsGrid');
        const empty = document.getElementById('emptyActuals');

        if (!completedActuals.length) {
            grid.innerHTML = '';
            empty.style.display = 'block';
            return;
        }

        empty.style.display = 'none';
        grid.innerHTML = completedActuals.map(act => {
            const typeBadge = act.visit_type === 'planned'
                ? '<span class="status-badge badge-planned">Planned</span>'
                : (act.visit_type === 'unplanned'
                    ? '<span class="status-badge badge-unplanned">Unplanned</span>'
                    : '<span class="status-badge badge-non_target">Non Target</span>');

            const inTime = act.check_in_at ? new Date(act.check_in_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-';
            const outTime = act.check_out_at ? new Date(act.check_out_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-';

            return `
                <div class="actual-card">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                            <span style="font-size: 0.8rem; font-weight: 700; color: var(--secondary);">${act.visit_date}</span>
                            ${typeBadge}
                        </div>
                        <div style="font-size: 1.05rem; font-weight: 700;">${act.doctor_name || 'Dokter'}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">${act.doctor_specialization || 'Umum'}</div>

                        <div style="font-size: 0.8rem; color: #0f766e; font-weight: 600; margin-top: 0.4rem;">
                            Waktu: ${inTime} - ${outTime} WIB
                        </div>

                        <!-- Produk Detailing -->
                        <div style="margin-top: 0.5rem;">
                            <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted);">PRODUK DITAWARKAN:</span>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.25rem; margin-top: 0.2rem;">
                                ${act.products.map(p => `
                                    <span style="font-size: 0.75rem; background: #f1f5f9; padding: 0.15rem 0.4rem; border-radius: 0.25rem;">
                                        ${p.name}
                                    </span>
                                `).join('')}
                            </div>
                        </div>

                        <!-- Bukti Foto & Signature Mini -->
                        <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem; padding-top: 0.5rem; border-top: 1px dashed var(--border);">
                            ${act.photo_url ? `
                                <a href="${act.photo_url}" target="_blank" rel="noopener" style="font-size: 0.75rem; color: var(--primary); font-weight: 600;">
                                    Lihat Foto Bukti &nearr;
                                </a>
                            ` : ''}
                            ${act.signature_url ? `
                                <a href="${act.signature_url}" target="_blank" rel="noopener" style="font-size: 0.75rem; color: var(--primary); font-weight: 600;">
                                    Lihat TTD Dokter &nearr;
                                </a>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function resetActualFilter() {
        document.getElementById('filterActualDate').value = '';
        loadActuals();
    }

    async function loadProducts() {
        try {
            const res = await window.apiFetch('/api/products');
            const result = await res.json();
            if (result.success) {
                allProducts = result.data;
                renderProductChecklist();
            }
        } catch (err) {
            console.error('Gagal memuat produk', err);
        }
    }

    function renderProductChecklist() {
        const container = document.getElementById('productChecklist');
        if (!allProducts.length) {
            container.innerHTML = '<span style="font-size:0.8rem; color:red;">Gagal memuat daftar produk.</span>';
            return;
        }

        container.innerHTML = allProducts.map(p => `
            <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer; background: white; padding: 0.35rem 0.5rem; border: 1px solid var(--border); border-radius: 0.25rem;">
                <input type="checkbox" name="actual_products" value="${p.id}">
                <div>
                    <strong>${p.name}</strong> <span style="font-size: 0.75rem; color: var(--text-muted);">(${p.category || 'Farmasi'})</span>
                </div>
            </label>
        `).join('');
    }

    async function loadMclDoctors() {
        try {
            const res = await window.apiFetch('/api/doctors');
            const result = await res.json();
            if (result.success) {
                allMclDoctors = result.data;
                const select = document.getElementById('formDoctorSelect');
                select.innerHTML = '<option value="">-- Pilih Dokter MCL --</option>' +
                    allMclDoctors.map(doc => `
                        <option value="${doc.id}">
                            ${doc.name} (${doc.specialization || 'Umum'})
                        </option>
                    `).join('');
            }
        } catch (err) {
            console.error('Gagal memuat dokter MCL', err);
        }
    }

    function startPlannedVisit(planId, doctorId, doctorName = '', planDate = '') {
        resetActualModal();

        document.getElementById('modalTitle').innerText = 'Realisasi Kunjungan Terencana';
        document.getElementById('formPlanId').value = planId;
        document.getElementById('plannedDoctorId').value = doctorId;

        // Tampilkan teks dokter terencana, sembunyikan dropdown
        const docDisplay = document.getElementById('plannedDoctorDisplay');
        docDisplay.innerText = doctorName || `Dokter ID #${doctorId}`;
        docDisplay.style.display = 'block';
        document.getElementById('formDoctorSelect').style.display = 'none';

        document.getElementById('visitTypeIndicator').innerHTML = 'Mode: <strong>Kunjungan Terencana (Planned)</strong>';
        document.getElementById('visitReasonGroup').style.display = 'none';

        // Tanggal default dari rencana
        if (planDate) {
            document.getElementById('formVisitDate').value = planDate;
            document.getElementById('formOriginalPlanDate').value = planDate;
        } else {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('formVisitDate').value = today;
            document.getElementById('formOriginalPlanDate').value = today;
        }

        fillSamplePhoto();
        fillSampleSignature();
        fillCurrentTimes();

        document.getElementById('actualModal').style.display = 'flex';
    }

    function openWithoutPlanModal() {
        resetActualModal();

        document.getElementById('modalTitle').innerText = 'Kunjungan di Luar Rencana';
        document.getElementById('formPlanId').value = '';
        document.getElementById('plannedDoctorId').value = '';

        // Tampilkan dropdown dokter, sembunyikan text read-only
        document.getElementById('plannedDoctorDisplay').style.display = 'none';
        document.getElementById('formDoctorSelect').style.display = 'block';

        document.getElementById('visitTypeIndicator').innerHTML = 'Mode: <strong>Kunjungan di Luar Rencana (Unplanned / Non Target)</strong>';
        document.getElementById('visitReasonGroup').style.display = 'block';
        document.getElementById('deviationReasonGroup').style.display = 'none';

        const today = new Date().toISOString().split('T')[0];
        document.getElementById('formVisitDate').value = today;

        fillSamplePhoto();
        fillSampleSignature();
        fillCurrentTimes();

        document.getElementById('actualModal').style.display = 'flex';
    }

    function closeActualModal() {
        document.getElementById('actualModal').style.display = 'none';
    }

    function resetActualModal() {
        const errEl = document.getElementById('actualModalError');
        errEl.style.display = 'none';
        errEl.innerText = '';
        document.getElementById('callActualForm').reset();
        document.getElementById('deviationReasonGroup').style.display = 'none';
        document.getElementById('photoPreviewContainer').style.display = 'none';
        document.getElementById('sigPreviewContainer').style.display = 'none';
    }

    function checkDateDeviation() {
        const planId = document.getElementById('formPlanId').value;
        const origDate = document.getElementById('formOriginalPlanDate').value;
        const currentDate = document.getElementById('formVisitDate').value;
        const deviationGroup = document.getElementById('deviationReasonGroup');

        if (planId && origDate && currentDate && origDate !== currentDate) {
            deviationGroup.style.display = 'block';
        } else {
            deviationGroup.style.display = 'none';
        }
    }

    function fillCurrentTimes() {
        const now = new Date();
        const outStr = now.toTimeString().substring(0, 5);

        // Checkin 30 menit sebelumnya
        const inTime = new Date(now.getTime() - 30 * 60000);
        const inStr = inTime.toTimeString().substring(0, 5);

        document.getElementById('formCheckIn').value = inStr;
        document.getElementById('formCheckOut').value = outStr;
    }

    function fillSamplePhoto() {
        const sampleUrl = 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=600&auto=format&fit=crop&q=80';
        document.getElementById('formPhotoUrl').value = sampleUrl;
        updatePhotoPreview();
    }

    function updatePhotoPreview() {
        const url = document.getElementById('formPhotoUrl').value.trim();
        const container = document.getElementById('photoPreviewContainer');
        const img = document.getElementById('photoPreviewImg');
        if (url) {
            img.src = url;
            container.style.display = 'block';
        } else {
            container.style.display = 'none';
        }
    }

    function fillSampleSignature() {
        const sampleSig = 'https://upload.wikimedia.org/wikipedia/commons/f/fa/Signature_sample.svg';
        document.getElementById('formSignatureUrl').value = sampleSig;
        updateSignaturePreview();
    }

    function updateSignaturePreview() {
        const url = document.getElementById('formSignatureUrl').value.trim();
        const container = document.getElementById('sigPreviewContainer');
        const img = document.getElementById('sigPreviewImg');
        if (url) {
            img.src = url;
            container.style.display = 'block';
        } else {
            container.style.display = 'none';
        }
    }

    async function handleActualSubmit(e) {
        e.preventDefault();
        const errEl = document.getElementById('actualModalError');
        errEl.style.display = 'none';

        const planId = document.getElementById('formPlanId').value;
        const doctorId = planId ? document.getElementById('plannedDoctorId').value : document.getElementById('formDoctorSelect').value;
        const visitDate = document.getElementById('formVisitDate').value;
        const checkIn = document.getElementById('formCheckIn').value;
        const checkOut = document.getElementById('formCheckOut').value;
        const photoUrl = document.getElementById('formPhotoUrl').value.trim();
        const signatureUrl = document.getElementById('formSignatureUrl').value.trim();
        const deviationReason = document.getElementById('formDeviationReason').value.trim();
        const visitReason = document.getElementById('formVisitReason').value.trim();

        const selectedProducts = Array.from(document.querySelectorAll('#productChecklist input[name="actual_products"]:checked')).map(cb => parseInt(cb.value));

        if (!doctorId) {
            errEl.innerText = 'Pilih dokter sasaran kunjungan.';
            errEl.style.display = 'block';
            return;
        }

        if (!selectedProducts.length) {
            errEl.innerText = 'Pilih minimal satu produk farmasi yang dipromosikan (detailing).';
            errEl.style.display = 'block';
            return;
        }

        const btn = document.getElementById('btnSubmitActual');
        btn.disabled = true;
        btn.innerText = 'Menyimpan...';

        const payload = {
            plan_id: planId ? parseInt(planId) : null,
            doctor_id: parseInt(doctorId),
            visit_date: visitDate,
            check_in: checkIn,
            check_out: checkOut,
            photo_url: photoUrl,
            signature_url: signatureUrl,
            products: selectedProducts,
            visit_reason: visitReason || null,
            deviation_reason: deviationReason || null,
        };

        try {
            const res = await window.apiFetch('/api/call-actuals', {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            btn.disabled = false;
            btn.innerText = 'Simpan Realisasi Kunjungan';

            if (data.success) {
                closeActualModal();
                showAlert(`Kunjungan dokter berhasil dicatat! (Tipe: ${data.data.visit_type})`);
                loadScheduledPlans();
                loadActuals();
            } else {
                errEl.innerText = data.message || 'Gagal menyimpan kunjungan.';
                errEl.style.display = 'block';
            }
        } catch (err) {
            btn.disabled = false;
            btn.innerText = 'Simpan Realisasi Kunjungan';
            errEl.innerText = 'Terjadi kesalahan koneksi jaringan.';
            errEl.style.display = 'block';
        }
    }
</script>
@endpush
@endsection
