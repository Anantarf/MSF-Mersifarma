@extends('layouts.app')

@section('title', 'Call Actual')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.45rem; font-weight: 700; color: var(--secondary);">Pelaksanaan Kunjungan <small style="font-size: 0.85rem; font-weight: 500; color: var(--text-muted);">(Call Actual)</small></h1>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Pelaporan kunjungan lapangan dengan bukti foto dan tanda tangan digital dokter.</p>
        </div>
        <button type="button" class="btn btn-outline" style="border-color: var(--primary); color: var(--primary); font-weight: 600;" onclick="openActualModal(null)">
            + Kunjungan di Luar Rencana
        </button>
    </div>

    <div id="actualStatusAlert" style="display: none;" class="alert"></div>

    <!-- Section 0: Kunjungan Sedang Berlangsung (Active Session P1) -->
    <section id="activeSessionSection" style="display: none; background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 8px; padding: 1.25rem 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <div style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.75rem; font-weight: 700; color: #15803d; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.35rem;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #22c55e; display: inline-block;"></span>
                    Kunjungan Sedang Berlangsung (Server Check-in)
                </div>
                <h3 id="activeSessionDoctorName" style="font-size: 1.15rem; font-weight: 700; color: var(--secondary); margin: 0 0 0.25rem 0;">-</h3>
                <p id="activeSessionDoctorDetail" style="font-size: 0.825rem; color: var(--text-muted); margin: 0;">-</p>
                <div style="margin-top: 0.5rem; font-size: 0.8rem; color: #166534; font-weight: 600;">
                    Waktu Check-in: <span id="activeSessionCheckInTime">-</span>
                    <span id="activeSessionCheckOutWrap" style="display: none; margin-left: 0.75rem; color: #0284c7;">
                        | Check-out: <span id="activeSessionCheckOutTime">-</span>
                    </span>
                </div>
            </div>
            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                <button type="button" id="btnSessionCheckout" class="btn btn-primary" onclick="handleSessionCheckout()">
                    Akhiri Kunjungan (Check-out)
                </button>
                <button type="button" id="btnSessionContinue" class="btn btn-primary" style="display: none;" onclick="openActualModalFromSession()">
                    Lanjutkan Pengisian Laporan
                </button>
                <button type="button" class="btn btn-outline" style="color: var(--danger); border-color: #fca5a5;" onclick="openCancelSessionModal()">
                    Batalkan Kunjungan
                </button>
            </div>
        </div>
    </section>

    <!-- Section 1: Today Plans (Planned First UX) -->
    <section style="display: flex; flex-direction: column; gap: 0.85rem;">
        <div>
            <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--secondary);">Jadwal Kunjungan Hari Ini</h2>
            <p style="font-size: 0.8rem; color: var(--text-muted);">Jadwal terencana (Planned Visit) diprioritaskan utama untuk efisiensi MR.</p>
        </div>
        <div id="todayPlansLoading" style="padding: 2.5rem; color: var(--text-muted); text-align: center; background: white; border: 1px solid var(--border); border-radius: 8px;">
            Memuat jadwal hari ini...
        </div>
        <div id="todayPlansTableWrap" class="table-responsive" style="display: none;">
            <table class="enterprise-table">
                <thead>
                    <tr>
                        <th>Jam Rencana</th>
                        <th>Dokter Sasaran</th>
                        <th>Spesialisasi & Alamat</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="todayPlansTableBody"></tbody>
            </table>
        </div>
        <div id="todayPlansEmpty" style="display: none; padding: 2.5rem 1.5rem; color: var(--text-muted); text-align: center; background: white; border: 1px solid var(--border); border-radius: 8px;">
            <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--secondary); margin-bottom: 0.25rem;">Tidak Ada Jadwal Kunjungan Hari Ini</h4>
            <p style="font-size: 0.825rem; color: var(--text-muted); max-width: 400px; margin: 0 auto;">
                Gunakan tombol <strong>+ Kunjungan di Luar Rencana</strong> di atas jika Anda melakukan kunjungan di luar jadwal terencana.
            </p>
        </div>
    </section>

    <!-- Section 2: Completed Actuals History -->
    <section style="display: flex; flex-direction: column; gap: 0.85rem;">
        <div>
            <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--secondary);">Riwayat Kunjungan Selesai</h2>
            <p style="font-size: 0.8rem; color: var(--text-muted);">Daftar kunjungan yang telah berhasil dilaporkan dan terverifikasi.</p>
        </div>
        <div id="actualsLoading" style="padding: 2.5rem; color: var(--text-muted); text-align: center; background: white; border: 1px solid var(--border); border-radius: 8px;">
            Memuat riwayat kunjungan...
        </div>
        <div id="actualsTableWrap" class="table-responsive" style="display: none;">
            <table class="enterprise-table">
                <thead>
                    <tr>
                        <th>Tanggal & Jam</th>
                        <th>Dokter Sasaran</th>
                        <th>Kategori Kunjungan</th>
                        <th>Detailing Produk</th>
                        <th>Verifikasi Bukti</th>
                    </tr>
                </thead>
                <tbody id="actualsTableBody"></tbody>
            </table>
        </div>
        <div id="actualsEmpty" style="display: none; padding: 2.5rem 1.5rem; color: var(--text-muted); text-align: center; background: white; border: 1px solid var(--border); border-radius: 8px;">
            <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--secondary); margin-bottom: 0.25rem;">Belum Ada Riwayat Kunjungan</h4>
            <p style="font-size: 0.825rem; color: var(--text-muted); max-width: 380px; margin: 0 auto;">
                Kunjungan yang telah berhasil diselesaikan akan tercatat rapi di tabel ini sebagai bukti audit operasional.
            </p>
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
            <input type="hidden" id="actualSessionId">

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
                        <i class="bi bi-camera-fill" style="font-size: 1rem; color: var(--primary);"></i>
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
                <button type="submit" id="actualSubmitButton" class="btn btn-primary">Simpan Laporan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Batalkan Sesi Kunjungan (P1 Feature) -->
<div id="cancelSessionModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--danger); margin: 0;">Batalkan Sesi Kunjungan</h3>
            <button type="button" class="close-btn" onclick="closeCancelSessionModal()">&times;</button>
        </div>

        <div id="cancelSessionModalError" style="display: none;" class="alert alert-danger"></div>

        <p style="font-size: 0.875rem; color: var(--secondary); margin-bottom: 1rem; line-height: 1.5;">
            Apakah Anda yakin ingin membatalkan sesi kunjungan ke <strong id="cancelSessionDoctorName">-</strong>? Jadwal terkait akan tetap tersimpan agar dapat dikunjungi kembali nanti.
        </p>

        <form id="cancelSessionForm" onsubmit="handleCancelSessionSubmit(event)">
            <input type="hidden" id="cancelSessionId">
            <div style="margin-bottom: 1.25rem;">
                <label for="cancelSessionReasonInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--secondary);">
                    Alasan Pembatalan Kunjungan (Wajib diisi):
                </label>
                <textarea id="cancelSessionReasonInput" required minlength="3" rows="3"
                          style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.875rem;"
                          placeholder="Contoh: Dokter dipanggil operasi darurat sebelum detailing obat"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeCancelSessionModal()">Kembali</button>
                <button type="submit" id="btnConfirmCancelSession" class="btn btn-danger" style="background: var(--danger); color: white; border: none; font-weight: 600;">
                    Konfirmasi Batalkan Kunjungan
                </button>
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
    let activeSession = null;
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
        await loadActiveSession();
        await loadTodayPlans();
        loadActuals();

        const urlParams = new URLSearchParams(window.location.search);
        const autoPlanId = urlParams.get('plan_id');
        const autoDocId = urlParams.get('doctor_id');
        if (autoPlanId && !activeSession) {
            openActualModal(parseInt(autoPlanId), autoDocId ? parseInt(autoDocId) : null);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeActualModal();
            closeCancelSessionModal();
        }
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
        const tableWrap = document.getElementById('todayPlansTableWrap');
        const tbody = document.getElementById('todayPlansTableBody');
        const empty = document.getElementById('todayPlansEmpty');
        if (!todayPlans.length) {
            if (tbody) tbody.innerHTML = '';
            if (tableWrap) tableWrap.style.display = 'none';
            empty.style.display = 'block';
            return;
        }
        empty.style.display = 'none';
        if (tableWrap) tableWrap.style.display = 'block';
        tbody.innerHTML = todayPlans.map((plan) => {
            const isThisPlanActive = activeSession && activeSession.plan_id === plan.id;
            const hasOtherSession = activeSession && !isThisPlanActive;

            let actionHtml = '';
            if (isThisPlanActive) {
                actionHtml = `
                    <span class="status-badge status-scheduled" style="padding: 0.35rem 0.65rem;">
                        ● Sedang Dikunjungi
                    </span>
                `;
            } else if (hasOtherSession) {
                actionHtml = `
                    <button type="button" class="btn btn-outline" style="font-size: 0.8rem; padding: 0.35rem 0.75rem; opacity: 0.6; cursor: not-allowed;" disabled title="Selesaikan sesi kunjungan aktif terlebih dahulu">
                        Mulai Kunjungan
                    </button>
                `;
            } else {
                actionHtml = `
                    <div style="display: inline-flex; gap: 0.4rem; justify-content: flex-end; align-items: center; flex-wrap: wrap;">
                        <button type="button" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;" onclick="startVisitSession(${plan.id}, ${plan.doctor_id})">
                            Mulai Kunjungan
                        </button>
                        <button type="button" class="btn btn-outline" style="font-size: 0.8rem; padding: 0.35rem 0.6rem; color: var(--text-muted);" onclick="openActualModal(${plan.id})" title="Atau isi laporan langsung secara manual">
                            Manual
                        </button>
                    </div>
                `;
            }

            return `
                <tr>
                    <td>
                        <strong style="color: var(--primary); font-size: 0.95rem;">${escapeHtml((plan.planned_time || '').substring(0, 5))} WIB</strong>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Hari Ini</div>
                    </td>
                    <td>
                        <strong style="font-size: 0.95rem; color: var(--secondary);">${escapeHtml(plan.doctor_name)}</strong>
                    </td>
                    <td>
                        <div>${escapeHtml(plan.doctor_specialization || 'Umum')}</div>
                        <small style="color: var(--text-muted); font-size: 0.75rem;">${escapeHtml(plan.doctor_address || '-')}</small>
                    </td>
                    <td style="text-align: right;">
                        ${actionHtml}
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderActuals() {
        const tableWrap = document.getElementById('actualsTableWrap');
        const tbody = document.getElementById('actualsTableBody');
        const empty = document.getElementById('actualsEmpty');
        if (!actuals.length) {
            if (tbody) tbody.innerHTML = '';
            if (tableWrap) tableWrap.style.display = 'none';
            empty.style.display = 'block';
            return;
        }
        empty.style.display = 'none';
        if (tableWrap) tableWrap.style.display = 'block';
        tbody.innerHTML = actuals.map((actual) => {
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
                <tr>
                    <td>
                        <strong style="color: var(--secondary); font-size: 0.9rem;">${escapeHtml(actual.visit_date)}</strong>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">${escapeHtml(actual.check_in || '')} - ${escapeHtml(actual.check_out || '')}</div>
                    </td>
                    <td>
                        <strong style="font-size: 0.95rem; color: var(--secondary);">${escapeHtml(actual.doctor_name)}</strong>
                        <div style="font-size: 0.775rem; color: var(--text-muted);">${escapeHtml(actual.doctor_specialization || 'Spesialis')}</div>
                    </td>
                    <td>
                        <span class="status-badge ${visitBadgeClass}">${escapeHtml(visitTypeLabel)}</span>
                        ${actual.visit_reason ? `
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem; font-style: italic;">
                                "${escapeHtml(actual.visit_reason)}"
                            </div>
                        ` : ''}
                    </td>
                    <td>
                        <div style="font-size: 0.825rem; font-weight: 600; color: var(--secondary);">
                            ${actual.products.map((p) => escapeHtml(p.name)).join(', ')}
                        </div>
                    </td>
                    <td>
                        <span style="display: inline-flex; align-items: center; gap: 0.25rem; color: var(--success); font-weight: 600; font-size: 0.8rem;">
                            ✓ Foto & TTD Terverifikasi
                        </span>
                    </td>
                </tr>
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
        button.innerText = 'Menyimpan Laporan...';

        try {
            const planId = document.getElementById('actualPlanId').value;
            const sessionId = document.getElementById('actualSessionId').value;
            const payload = {
                photo_url: photoUrl,
                signature_url: signatureUrl,
                products: productIds,
                visit_reason: planId ? null : (document.getElementById('visitReasonInput').value.trim() || null)
            };

            if (sessionId) {
                payload.visit_session_id = parseInt(sessionId);
            } else {
                payload.plan_id = planId ? parseInt(planId) : null;
                payload.doctor_id = parseInt(document.getElementById('actualDoctorSelect').value);
                payload.visit_date = document.getElementById('actualDateInput').value;
                payload.check_in = document.getElementById('checkInInput').value;
                payload.check_out = document.getElementById('checkOutInput').value;
            }

            const res = await window.apiFetch('/api/call-actuals', {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            if (result.success) {
                closeActualModal();
                showActualAlert('Laporan kunjungan dokter berhasil disimpan dan dicatat.');
                await loadActiveSession();
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
            button.innerText = 'Simpan Laporan';
        }
    }

    async function loadActiveSession() {
        try {
            const res = await window.apiFetch('/api/visit-sessions/active');
            const result = await res.json();
            activeSession = result.success ? result.data : null;
            renderActiveSession();
        } catch (err) {
            activeSession = null;
            renderActiveSession();
        }
    }

    function renderActiveSession() {
        const section = document.getElementById('activeSessionSection');
        if (!activeSession) {
            section.style.display = 'none';
            return;
        }

        section.style.display = 'block';
        document.getElementById('activeSessionDoctorName').innerText = activeSession.doctor_name || 'Dokter Sasaran';
        document.getElementById('activeSessionDoctorDetail').innerText = `${activeSession.doctor_specialization || 'Spesialis Umum'} - ${activeSession.doctor_address || '-'}`;

        const checkInDate = new Date(activeSession.check_in_at);
        document.getElementById('activeSessionCheckInTime').innerText = checkInDate.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';

        const checkoutWrap = document.getElementById('activeSessionCheckOutWrap');
        const btnCheckout = document.getElementById('btnSessionCheckout');
        const btnContinue = document.getElementById('btnSessionContinue');

        if (activeSession.check_out_at) {
            const checkOutDate = new Date(activeSession.check_out_at);
            document.getElementById('activeSessionCheckOutTime').innerText = checkOutDate.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
            checkoutWrap.style.display = 'inline';
            btnCheckout.style.display = 'none';
            btnContinue.style.display = 'inline-block';
        } else {
            checkoutWrap.style.display = 'none';
            btnCheckout.style.display = 'inline-block';
            btnContinue.style.display = 'none';
        }
    }

    async function startVisitSession(planId, doctorId) {
        try {
            const res = await window.apiFetch('/api/visit-sessions/start', {
                method: 'POST',
                body: JSON.stringify({
                    plan_id: planId ? parseInt(planId) : null,
                    doctor_id: doctorId ? parseInt(doctorId) : null
                })
            });
            const result = await res.json();
            if (result.success) {
                showActualAlert('Sesi kunjungan berhasil dimulai. Check-in tercatat otomatis oleh server.');
                await loadActiveSession();
                renderTodayPlans();
            } else {
                showActualAlert(result.message || 'Gagal memulai sesi kunjungan.', 'danger');
            }
        } catch (err) {
            showActualAlert('Terjadi kesalahan jaringan saat memulai kunjungan.', 'danger');
        }
    }

    async function handleSessionCheckout() {
        if (!activeSession) return;
        const btn = document.getElementById('btnSessionCheckout');
        btn.disabled = true;
        btn.innerText = 'Mencatat Check-out...';

        try {
            const res = await window.apiFetch(`/api/visit-sessions/${activeSession.id}/checkout`, {
                method: 'POST'
            });
            const result = await res.json();
            btn.disabled = false;
            btn.innerText = 'Akhiri Kunjungan (Check-out)';

            if (result.success) {
                showActualAlert('Waktu check-out berhasil dicatat server. Silakan lengkapi bukti foto dan tanda tangan.');
                await loadActiveSession();
                openActualModalFromSession();
            } else {
                showActualAlert(result.message || 'Gagal mencatat check-out.', 'danger');
            }
        } catch (err) {
            btn.disabled = false;
            btn.innerText = 'Akhiri Kunjungan (Check-out)';
            showActualAlert('Terjadi kesalahan jaringan saat check-out.', 'danger');
        }
    }

    function openActualModalFromSession() {
        if (!activeSession) return;

        openActualModal(activeSession.plan_id, activeSession.doctor_id);
        document.getElementById('actualSessionId').value = activeSession.id;
        document.getElementById('actualModalTitle').innerText = 'Lengkapi Laporan Kunjungan Sesi Ini';

        if (activeSession.check_in_at) {
            const cin = new Date(activeSession.check_in_at);
            const hh = String(cin.getHours()).padStart(2, '0');
            const mm = String(cin.getMinutes()).padStart(2, '0');
            document.getElementById('checkInInput').value = `${hh}:${mm}`;
        }

        if (activeSession.check_out_at) {
            const cout = new Date(activeSession.check_out_at);
            const hh = String(cout.getHours()).padStart(2, '0');
            const mm = String(cout.getMinutes()).padStart(2, '0');
            document.getElementById('checkOutInput').value = `${hh}:${mm}`;
        }
    }

    function openCancelSessionModal() {
        if (!activeSession) return;
        document.getElementById('cancelSessionId').value = activeSession.id;
        document.getElementById('cancelSessionDoctorName').innerText = activeSession.doctor_name || 'Dokter Sasaran';
        document.getElementById('cancelSessionReasonInput').value = '';
        const errEl = document.getElementById('cancelSessionModalError');
        errEl.style.display = 'none';
        errEl.innerText = '';
        document.getElementById('cancelSessionModal').style.display = 'flex';
    }

    function closeCancelSessionModal() {
        document.getElementById('cancelSessionModal').style.display = 'none';
    }

    async function handleCancelSessionSubmit(e) {
        e.preventDefault();
        const sessionId = document.getElementById('cancelSessionId').value;
        const reason = document.getElementById('cancelSessionReasonInput').value.trim();
        const errEl = document.getElementById('cancelSessionModalError');
        const btn = document.getElementById('btnConfirmCancelSession');

        errEl.style.display = 'none';

        if (reason.length < 3) {
            errEl.innerText = 'Alasan pembatalan kunjungan wajib diisi minimal 3 karakter.';
            errEl.style.display = 'block';
            return;
        }

        btn.disabled = true;
        btn.innerText = 'Membatalkan...';

        try {
            const res = await window.apiFetch(`/api/visit-sessions/${sessionId}/cancel`, {
                method: 'POST',
                body: JSON.stringify({ reason: reason })
            });
            const result = await res.json();
            btn.disabled = false;
            btn.innerText = 'Konfirmasi Batalkan Kunjungan';

            if (result.success) {
                closeCancelSessionModal();
                showActualAlert('Sesi kunjungan berhasil dibatalkan. Jadwal terkait tetap tersedia untuk dikunjungi kembali.');
                await loadActiveSession();
                renderTodayPlans();
            } else {
                errEl.innerText = result.message || 'Gagal membatalkan sesi kunjungan.';
                errEl.style.display = 'block';
            }
        } catch (err) {
            btn.disabled = false;
            btn.innerText = 'Konfirmasi Batalkan Kunjungan';
            errEl.innerText = 'Terjadi kesalahan jaringan atau server saat membatalkan sesi.';
            errEl.style.display = 'block';
        }
    }
</script>
@endpush

@push('styles')
<style>
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
    .status-scheduled { background: #e0f2fe; color: #0369a1; border-color: #bae6fd; }
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