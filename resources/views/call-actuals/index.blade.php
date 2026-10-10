@extends('layouts.app')

@section('title', 'Call Actual')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--secondary);">Realisasi Kunjungan (Call Actual)</h1>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Pelaporan kunjungan lapangan dengan bukti foto dan tanda tangan digital dokter.</p>
        </div>
        <button type="button" class="btn btn-outline" style="border-color: var(--primary); color: var(--primary);" onclick="openActualModal(null)">
            + Kunjungan di Luar Rencana
        </button>
    </div>

    <div id="actualStatusAlert" style="display: none;" class="alert"></div>

    <!-- Section 1: Today Plans (Planned First UX) -->
    <section style="display: flex; flex-direction: column; gap: 0.85rem;">
        <div>
            <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--secondary);">Jadwal Kunjungan Hari Ini</h2>
            <p style="font-size: 0.8rem; color: var(--text-muted);">Jadwal terencana (Planned Visit) diprioritaskan utama untuk efisiensi MR.</p>
        </div>
        <div id="todayPlansLoading" style="padding: 1.75rem; color: var(--text-muted); text-align: center; background: white; border: 1px solid var(--border); border-radius: 0.5rem;">
            Memuat jadwal hari ini...
        </div>
        <div id="todayPlansGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem;"></div>
        <div id="todayPlansEmpty" style="display: none; padding: 2rem; color: var(--text-muted); text-align: center; background: white; border: 1px dashed var(--border); border-radius: 0.5rem;">
            <p style="font-size: 0.9rem;">Tidak ada jadwal kunjungan untuk hari ini.</p>
            <p style="font-size: 0.8rem; margin-top: 0.35rem;">Gunakan tombol <strong>+ Kunjungan di Luar Rencana</strong> jika Anda berkunjung di luar jadwal.</p>
        </div>
    </section>

    <!-- Section 2: Completed Actuals History -->
    <section style="display: flex; flex-direction: column; gap: 0.85rem;">
        <div>
            <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--secondary);">Riwayat Kunjungan Selesai</h2>
            <p style="font-size: 0.8rem; color: var(--text-muted);">Daftar kunjungan yang telah berhasil dilaporkan dan terverifikasi.</p>
        </div>
        <div id="actualsLoading" style="padding: 1.75rem; color: var(--text-muted); text-align: center; background: white; border: 1px solid var(--border); border-radius: 0.5rem;">
            Memuat riwayat kunjungan...
        </div>
        <div id="actualsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem;"></div>
        <div id="actualsEmpty" style="display: none; padding: 2rem; color: var(--text-muted); text-align: center; background: white; border: 1px dashed var(--border); border-radius: 0.5rem;">
            Belum ada kunjungan yang diselesaikan.
        </div>
    </section>
</div>

<!-- MODAL: Form Pelaporan Kunjungan (Call Actual) -->
<div id="actualModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card" style="max-width: 580px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 id="actualModalTitle" style="font-size: 1.2rem; font-weight: 700; color: var(--secondary);">Catat Kunjungan Lapangan</h3>
            <button type="button" class="close-btn" onclick="closeActualModal()">&times;</button>
        </div>

        <div id="actualModalError" style="display: none;" class="alert alert-danger"></div>

        <form id="actualForm" onsubmit="submitActual(event)">
            <input type="hidden" id="actualPlanId">

            <!-- 1. Pilihan Dokter -->
            <div style="margin-bottom: 1rem;">
                <label for="actualDoctorSelect" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--secondary);">Dokter Sasaran (MCL)</label>
                <select id="actualDoctorSelect" required style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.9rem;"></select>
            </div>

            <!-- 2. Tanggal & Jam Kunjungan -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem; margin-bottom: 1.15rem;">
                <div>
                    <label for="actualDateInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--secondary);">Tanggal Kunjungan</label>
                    <input type="date" id="actualDateInput" required style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.9rem;">
                </div>
                <div>
                    <label for="checkInInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--secondary);">Jam Check-in</label>
                    <input type="time" id="checkInInput" required value="08:30" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.9rem;">
                </div>
                <div>
                    <label for="checkOutInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--secondary);">Jam Check-out</label>
                    <input type="time" id="checkOutInput" required value="09:00" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.9rem;">
                </div>
            </div>

            <!-- 3. Bukti Foto Kunjungan -->
            <div style="margin-bottom: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                    <label style="font-size: 0.85rem; font-weight: 600; color: var(--secondary);">Foto Kunjungan di Lokasi</label>
                    <button type="button" class="btn btn-outline" style="min-height: 28px; padding: 0.15rem 0.5rem; font-size: 0.75rem;" onclick="useSamplePhoto()">
                        + Contoh Foto Klinik
                    </button>
                </div>
                <input type="hidden" id="photoUrlInput" required>
                <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                    <label class="btn btn-outline" style="min-height: 40px; font-size: 0.8rem; cursor: pointer; gap: 0.35rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                            <circle cx="12" cy="13" r="4"/>
                        </svg>
                        <span>Pilih / Ambil Foto</span>
                        <input type="file" id="photoFileInput" accept="image/*" style="display: none;" onchange="handlePhotoFile(this)">
                    </label>
                    <div id="photoPreviewContainer" style="display: none; align-items: center; gap: 0.5rem;">
                        <img id="photoPreviewImg" src="" alt="Pratinjau Foto" style="width: 44px; height: 44px; object-fit: cover; border-radius: 0.375rem; border: 1px solid var(--border);">
                        <span id="photoFileName" style="font-size: 0.75rem; color: var(--text-muted); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></span>
                    </div>
                </div>
                <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 0.25rem;">Wajib menyertakan bukti foto di tempat praktik dokter.</small>
            </div>

            <!-- 4. Tanda Tangan Digital Dokter -->
            <div style="margin-bottom: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                    <label style="font-size: 0.85rem; font-weight: 600; color: var(--secondary);">Tanda Tangan Dokter</label>
                    <div style="display: flex; gap: 0.35rem;">
                        <button type="button" class="btn btn-outline" style="min-height: 28px; padding: 0.15rem 0.5rem; font-size: 0.75rem;" onclick="clearSignature()">
                            Hapus
                        </button>
                        <button type="button" class="btn btn-outline" style="min-height: 28px; padding: 0.15rem 0.5rem; font-size: 0.75rem;" onclick="useSampleSignature()">
                            + Contoh TTD
                        </button>
                    </div>
                </div>
                <input type="hidden" id="signatureUrlInput" required>
                <div style="border: 1px solid var(--border); border-radius: 0.5rem; background: #ffffff; padding: 0.25rem; touch-action: none;">
                    <canvas id="signatureCanvas" width="500" height="130" style="width: 100%; height: 130px; display: block; border-radius: 0.375rem; cursor: crosshair; background: #fafafa;"></canvas>
                </div>
                <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 0.25rem;">Dokter menandatangani langsung di kanvas dengan jari atau mouse.</small>
            </div>

            <!-- 5. Produk Detailing Farmasi -->
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--secondary);">
                    Produk Detailing yang Dipresentasikan (Minimal 1 Produk):
                </label>
                <div id="productPicker" style="display: flex; flex-direction: column; gap: 0.45rem; max-height: 170px; overflow-y: auto; border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.5rem; background: #fafafa;">
                    Memuat produk...
                </div>
            </div>

            <!-- 6. Alasan Kunjungan di Luar Rencana -->
            <div id="visitReasonWrap" style="margin-bottom: 1.25rem; display: none;">
                <label for="visitReasonInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--secondary);">
                    Alasan Kunjungan di Luar Rencana (Opsional):
                </label>
                <textarea id="visitReasonInput" rows="2" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.85rem;" placeholder="Contoh: Kunjungan mendadak atas permintaan dokter"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                <button type="button" class="btn btn-outline" onclick="closeActualModal()">Batal</button>
                <button type="submit" id="actualSubmitButton" class="btn btn-primary">Selesaikan Kunjungan</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    let todayPlans = [];
    let actuals = [];
    let doctors = [];
    let products = [];
    let isDrawing = false;
    let sigCanvas, sigCtx;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    document.addEventListener('DOMContentLoaded', async () => {
        initSignaturePad();
        await Promise.all([loadDoctors(), loadProducts()]);
        await loadTodayPlans();
        loadActuals();

        const urlParams = new URLSearchParams(window.location.search);
        const autoPlanId = urlParams.get('plan_id');
        const autoDocId = urlParams.get('doctor_id');
        if (autoPlanId) {
            openActualModal(parseInt(autoPlanId), autoDocId ? parseInt(autoDocId) : null);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeActualModal();
    });

    function initSignaturePad() {
        sigCanvas = document.getElementById('signatureCanvas');
        if (!sigCanvas) return;
        sigCtx = sigCanvas.getContext('2d');
        sigCtx.strokeStyle = '#0f172a';
        sigCtx.lineWidth = 2.5;
        sigCtx.lineCap = 'round';
        sigCtx.lineJoin = 'round';

        function getPos(e) {
            const rect = sigCanvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            const scaleX = sigCanvas.width / rect.width;
            const scaleY = sigCanvas.height / rect.height;
            return {
                x: (clientX - rect.left) * scaleX,
                y: (clientY - rect.top) * scaleY
            };
        }

        sigCanvas.addEventListener('mousedown', (e) => {
            isDrawing = true;
            const pos = getPos(e);
            sigCtx.beginPath();
            sigCtx.moveTo(pos.x, pos.y);
        });

        sigCanvas.addEventListener('mousemove', (e) => {
            if (!isDrawing) return;
            const pos = getPos(e);
            sigCtx.lineTo(pos.x, pos.y);
            sigCtx.stroke();
        });

        window.addEventListener('mouseup', () => {
            if (isDrawing) {
                isDrawing = false;
                document.getElementById('signatureUrlInput').value = sigCanvas.toDataURL('image/png');
            }
        });

        sigCanvas.addEventListener('touchstart', (e) => {
            e.preventDefault();
            isDrawing = true;
            const pos = getPos(e);
            sigCtx.beginPath();
            sigCtx.moveTo(pos.x, pos.y);
        }, { passive: false });

        sigCanvas.addEventListener('touchmove', (e) => {
            e.preventDefault();
            if (!isDrawing) return;
            const pos = getPos(e);
            sigCtx.lineTo(pos.x, pos.y);
            sigCtx.stroke();
        }, { passive: false });

        sigCanvas.addEventListener('touchend', (e) => {
            e.preventDefault();
            if (isDrawing) {
                isDrawing = false;
                document.getElementById('signatureUrlInput').value = sigCanvas.toDataURL('image/png');
            }
        }, { passive: false });
    }

    function clearSignature() {
        if (!sigCanvas || !sigCtx) return;
        sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
        document.getElementById('signatureUrlInput').value = '';
    }

    function useSampleSignature() {
        if (!sigCanvas || !sigCtx) return;
        clearSignature();
        sigCtx.beginPath();
        sigCtx.moveTo(60, 80);
        sigCtx.bezierCurveTo(90, 30, 140, 40, 180, 85);
        sigCtx.bezierCurveTo(200, 110, 230, 60, 280, 75);
        sigCtx.bezierCurveTo(310, 85, 340, 95, 400, 50);
        sigCtx.stroke();
        sigCtx.beginPath();
        sigCtx.moveTo(120, 95);
        sigCtx.lineTo(360, 95);
        sigCtx.stroke();
        document.getElementById('signatureUrlInput').value = sigCanvas.toDataURL('image/png');
    }

    function handlePhotoFile(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                const dataUrl = e.target.result;
                document.getElementById('photoUrlInput').value = dataUrl;
                document.getElementById('photoPreviewImg').src = dataUrl;
                document.getElementById('photoFileName').innerText = file.name;
                document.getElementById('photoPreviewContainer').style.display = 'flex';
            };
            reader.readAsDataURL(file);
        }
    }

    function useSamplePhoto() {
        const svgData = `<svg xmlns="http://www.w3.org/2000/svg" width="300" height="200" viewBox="0 0 300 200"><rect width="300" height="200" fill="#e0f2fe"/><rect x="20" y="20" width="260" height="160" rx="8" fill="#ffffff" stroke="#0284c7" stroke-width="2"/><circle cx="150" cy="85" r="35" fill="#f0f9ff" stroke="#0ea5e9" stroke-width="2"/><path d="M150 65v40M130 85h40" stroke="#0284c7" stroke-width="4" stroke-linecap="round"/><text x="150" y="145" font-family="sans-serif" font-size="12" font-weight="bold" fill="#0f172a" text-anchor="middle">KLINIK MITRA MERSIFARMA</text><text x="150" y="162" font-family="sans-serif" font-size="10" fill="#64748b" text-anchor="middle">Bukti Kunjungan Lapangan Terverifikasi</text></svg>`;
        const dataUrl = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svgData);
        document.getElementById('photoUrlInput').value = dataUrl;
        document.getElementById('photoPreviewImg').src = dataUrl;
        document.getElementById('photoFileName').innerText = 'klinik-mitra-verified.svg';
        document.getElementById('photoPreviewContainer').style.display = 'flex';
    }

    function todayString() {
        return new Date().toISOString().split('T')[0];
    }

    function showActualAlert(message, type = 'success') {
        const alertEl = document.getElementById('actualStatusAlert');
        alertEl.className = `alert alert-${type}`;
        alertEl.innerText = message;
        alertEl.style.display = 'block';
        setTimeout(() => { alertEl.style.display = 'none'; }, 4000);
    }

    async function loadDoctors() {
        const res = await window.apiFetch('/api/doctors');
        const result = await res.json();
        doctors = result.success ? result.data : [];
        renderDoctorSelect();
    }

    async function loadProducts() {
        const res = await window.apiFetch('/api/products');
        const result = await res.json();
        products = result.success ? result.data : [];
        renderProductPicker();
    }

    async function loadTodayPlans() {
        const loading = document.getElementById('todayPlansLoading');
        loading.style.display = 'block';
        try {
            const res = await window.apiFetch(`/api/call-plans?date=${todayString()}&status=scheduled`);
            const result = await res.json();
            todayPlans = result.success ? result.data : [];
            renderTodayPlans();
        } finally {
            loading.style.display = 'none';
        }
    }

    async function loadActuals() {
        const loading = document.getElementById('actualsLoading');
        loading.style.display = 'block';
        try {
            const res = await window.apiFetch('/api/call-actuals');
            const result = await res.json();
            actuals = result.success ? result.data : [];
            renderActuals();
        } finally {
            loading.style.display = 'none';
        }
    }

    function renderDoctorSelect(selectedDoctorId = '') {
        const select = document.getElementById('actualDoctorSelect');
        if (!select) return;
        select.innerHTML = '<option value="">-- Pilih Dokter MCL --</option>' + doctors.map((doctor) => `
            <option value="${doctor.id}" ${String(doctor.id) === String(selectedDoctorId) ? 'selected' : ''}>
                ${escapeHtml(doctor.name)} (${escapeHtml(doctor.specialization || 'Umum')})
            </option>
        `).join('');
    }

    function renderProductPicker() {
        const picker = document.getElementById('productPicker');
        if (!picker) return;
        if (!products.length) {
            picker.innerHTML = '<span style="color: var(--text-muted); font-size: 0.85rem;">Produk belum tersedia.</span>';
            return;
        }
        picker.innerHTML = products.map((product) => `
            <label style="display: flex; gap: 0.5rem; align-items: flex-start; background: white; border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.5rem; font-size: 0.85rem; cursor: pointer; transition: background 0.15s ease;">
                <input type="checkbox" name="product_ids" value="${product.id}" style="margin-top: 0.2rem;">
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong>${escapeHtml(product.name)}</strong>
                        <span style="font-size: 0.7rem; font-weight: 600; color: var(--primary); background: var(--primary-light); padding: 0.1rem 0.4rem; border-radius: 0.25rem;">${escapeHtml(product.category || 'Farmasi')}</span>
                    </div>
                    <small style="color: var(--text-muted); font-size: 0.75rem;">${escapeHtml(product.dosage_form || 'Sediaan Tablet/Kapsul')}</small>
                </div>
            </label>
        `).join('');
    }

    function renderTodayPlans() {
        const grid = document.getElementById('todayPlansGrid');
        const empty = document.getElementById('todayPlansEmpty');
        if (!todayPlans.length) {
            grid.innerHTML = '';
            empty.style.display = 'block';
            return;
        }
        empty.style.display = 'none';
        grid.innerHTML = todayPlans.map((plan) => `
            <article class="call-card" style="background: white; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between; gap: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <span style="font-size: 0.75rem; font-weight: 700; color: var(--primary); background: var(--primary-light); padding: 0.2rem 0.5rem; border-radius: 0.25rem;">
                            TERJADWAL HARI INI
                        </span>
                        <span style="font-size: 0.8rem; font-weight: 700; color: var(--secondary);">
                            ${escapeHtml((plan.planned_time || '').substring(0, 5))} WIB
                        </span>
                    </div>
                    <div style="font-size: 1.05rem; font-weight: 700; color: var(--secondary);">${escapeHtml(plan.doctor_name)}</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">${escapeHtml(plan.doctor_specialization || 'Umum')}</div>
                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">${escapeHtml(plan.doctor_address || '-')}</div>
                </div>
                <div style="border-top: 1px solid var(--border); padding-top: 0.75rem; margin-top: 0.5rem;">
                    <button type="button" class="btn btn-primary" style="width: 100%; min-height: 42px; font-size: 0.9rem;" onclick="openActualModal(${plan.id})">
                        Mulai Kunjungan
                    </button>
                </div>
            </article>
        `).join('');
    }

    function renderActuals() {
        const grid = document.getElementById('actualsGrid');
        const empty = document.getElementById('actualsEmpty');
        if (!actuals.length) {
            grid.innerHTML = '';
            empty.style.display = 'block';
            return;
        }
        empty.style.display = 'none';
        grid.innerHTML = actuals.map((actual) => {
            let visitTypeLabel = 'Terencana';
            let visitBadgeClass = 'status-approved';
            if (actual.visit_type === 'unplanned') {
                visitTypeLabel = 'Unplanned Target';
                visitBadgeClass = 'status-scheduled';
            } else if (actual.visit_type === 'non_target') {
                visitTypeLabel = 'Non-Target Dokter';
                visitBadgeClass = 'status-draft';
            }

            return `
                <article class="call-card" style="background: white; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between; gap: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; gap: 0.5rem;">
                            <span style="font-size: 0.8rem; font-weight: 700; color: var(--primary);">${escapeHtml(actual.visit_date)}</span>
                            <span class="status-badge ${visitBadgeClass}">${escapeHtml(visitTypeLabel)}</span>
                        </div>
                        <div style="font-size: 1.05rem; font-weight: 700; color: var(--secondary);">${escapeHtml(actual.doctor_name)}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">${escapeHtml(actual.doctor_specialization || 'Spesialis')}</div>
                        <div style="font-size: 0.8rem; color: var(--secondary); margin-top: 0.5rem;">
                            <strong>Produk Detailing:</strong> ${actual.products.map((p) => escapeHtml(p.name)).join(', ')}
                        </div>
                        ${actual.visit_reason ? `
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem; font-style: italic;">
                                Alasan: "${escapeHtml(actual.visit_reason)}"
                            </div>
                        ` : ''}
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 0.5rem; font-size: 0.75rem; color: var(--text-muted);">
                        <span>Status: <strong style="color: var(--success);">Selesai Terverifikasi</strong></span>
                        <span>Foto & TTD Tersimpan</span>
                    </div>
                </article>
            `;
        }).join('');
    }

    function openActualModal(planId, fallbackDoctorId = null) {
        const plan = planId ? todayPlans.find((item) => item.id === planId) : null;
        document.getElementById('actualPlanId').value = plan ? plan.id : (planId || '');
        document.getElementById('actualModalTitle').innerText = (plan || planId) ? 'Catat Planned Visit' : 'Catat Kunjungan di Luar Rencana';
        document.getElementById('actualDateInput').value = todayString();
        document.getElementById('visitReasonWrap').style.display = (plan || planId) ? 'none' : 'block';
        document.getElementById('actualDoctorSelect').disabled = !!(plan || planId);
        
        const docIdToSelect = plan ? plan.doctor_id : (fallbackDoctorId || '');
        renderDoctorSelect(docIdToSelect);
        renderProductPicker();

        // Reset foto & tanda tangan
        document.getElementById('photoUrlInput').value = '';
        document.getElementById('photoPreviewContainer').style.display = 'none';
        document.getElementById('photoPreviewImg').src = '';
        document.getElementById('photoFileName').innerText = '';
        document.getElementById('photoFileInput').value = '';

        clearSignature();

        document.getElementById('visitReasonInput').value = '';
        document.getElementById('actualModalError').style.display = 'none';
        document.getElementById('actualModal').style.display = 'flex';
    }

    function closeActualModal() {
        document.getElementById('actualModal').style.display = 'none';
    }

    async function submitActual(event) {
        event.preventDefault();
        const errorEl = document.getElementById('actualModalError');
        const button = document.getElementById('actualSubmitButton');
        errorEl.style.display = 'none';

        const photoUrl = document.getElementById('photoUrlInput').value.trim();
        if (!photoUrl) {
            errorEl.innerText = 'Foto kunjungan wajib diambil atau diunggah.';
            errorEl.style.display = 'block';
            return;
        }

        const signatureUrl = document.getElementById('signatureUrlInput').value.trim();
        if (!signatureUrl) {
            errorEl.innerText = 'Tanda tangan dokter wajib diisi di kanvas.';
            errorEl.style.display = 'block';
            return;
        }

        const productIds = Array.from(document.querySelectorAll('#productPicker input[name="product_ids"]:checked')).map((input) => parseInt(input.value));
        if (!productIds.length) {
            errorEl.innerText = 'Pilih minimal satu produk detailing farmasi.';
            errorEl.style.display = 'block';
            return;
        }

        button.disabled = true;
        button.innerText = 'Menyimpan Kunjungan...';

        try {
            const planId = document.getElementById('actualPlanId').value;
            const res = await window.apiFetch('/api/call-actuals', {
                method: 'POST',
                body: JSON.stringify({
                    plan_id: planId ? parseInt(planId) : null,
                    doctor_id: parseInt(document.getElementById('actualDoctorSelect').value),
                    visit_date: document.getElementById('actualDateInput').value,
                    check_in: document.getElementById('checkInInput').value,
                    check_out: document.getElementById('checkOutInput').value,
                    photo_url: photoUrl,
                    signature_url: signatureUrl,
                    products: productIds,
                    visit_reason: planId ? null : (document.getElementById('visitReasonInput').value.trim() || null)
                })
            });
            const result = await res.json();
            if (result.success) {
                closeActualModal();
                showActualAlert('Kunjungan dokter berhasil diselesaikan dan dicatat.');
                loadTodayPlans();
                loadActuals();
            } else {
                errorEl.innerText = result.message || 'Gagal menyimpan kunjungan.';
                errorEl.style.display = 'block';
            }
        } catch (error) {
            errorEl.innerText = 'Terjadi kesalahan jaringan atau server saat menyimpan.';
            errorEl.style.display = 'block';
        } finally {
            button.disabled = false;
            button.innerText = 'Selesaikan Kunjungan';
        }
    }
</script>
@endpush
@endsection