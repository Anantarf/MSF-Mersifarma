@extends('layouts.app')

@section('title', 'Call Actual')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--secondary);">Realisasi Kunjungan (Call Actual)</h1>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Catat kunjungan selesai dari jadwal hari ini atau kunjungan di luar rencana.</p>
        </div>
        <button type="button" class="btn btn-outline" onclick="openActualModal(null)">Kunjungan di Luar Rencana</button>
    </div>

    <div id="actualStatusAlert" style="display: none;" class="alert"></div>

    <section style="display: flex; flex-direction: column; gap: 1rem;">
        <div>
            <h2 style="font-size: 1.05rem; font-weight: 700; color: var(--secondary);">Jadwal Hari Ini</h2>
            <p style="font-size: 0.8rem; color: var(--text-muted);">Planned visit ditampilkan lebih dulu untuk alur MR.</p>
        </div>
        <div id="todayPlansLoading" style="padding: 1.5rem; color: var(--text-muted); text-align: center; background: white; border: 1px solid var(--border); border-radius: 0.5rem;">Memuat jadwal hari ini...</div>
        <div id="todayPlansGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem;"></div>
        <div id="todayPlansEmpty" style="display: none; padding: 2rem; color: var(--text-muted); text-align: center; background: white; border: 1px dashed var(--border); border-radius: 0.5rem;">Tidak ada jadwal hari ini.</div>
    </section>

    <section style="display: flex; flex-direction: column; gap: 1rem;">
        <div>
            <h2 style="font-size: 1.05rem; font-weight: 700; color: var(--secondary);">Riwayat Kunjungan Selesai</h2>
        </div>
        <div id="actualsLoading" style="padding: 1.5rem; color: var(--text-muted); text-align: center; background: white; border: 1px solid var(--border); border-radius: 0.5rem;">Memuat riwayat...</div>
        <div id="actualsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem;"></div>
        <div id="actualsEmpty" style="display: none; padding: 2rem; color: var(--text-muted); text-align: center; background: white; border: 1px dashed var(--border); border-radius: 0.5rem;">Belum ada kunjungan selesai.</div>
    </section>
</div>

<div id="actualModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card" style="max-width: 560px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 id="actualModalTitle" style="font-size: 1.15rem; font-weight: 700;">Catat Kunjungan</h3>
            <button type="button" class="close-btn" onclick="closeActualModal()">&times;</button>
        </div>

        <div id="actualModalError" style="display: none;" class="alert alert-danger"></div>

        <form id="actualForm" onsubmit="submitActual(event)">
            <input type="hidden" id="actualPlanId">

            <div style="margin-bottom: 1rem;">
                <label for="actualDoctorSelect" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">Dokter</label>
                <select id="actualDoctorSelect" required style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem;"></select>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem; margin-bottom: 1rem;">
                <div>
                    <label for="actualDateInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">Tanggal</label>
                    <input type="date" id="actualDateInput" required style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem;">
                </div>
                <div>
                    <label for="checkInInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">Check-in</label>
                    <input type="time" id="checkInInput" required value="08:30" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem;">
                </div>
                <div>
                    <label for="checkOutInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">Check-out</label>
                    <input type="time" id="checkOutInput" required value="09:00" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem;">
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="photoUrlInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">URL Foto</label>
                <input type="url" id="photoUrlInput" required placeholder="https://example.test/photo.jpg" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="signatureUrlInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">URL Tanda Tangan</label>
                <input type="url" id="signatureUrlInput" required placeholder="https://example.test/signature.png" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">Produk Detailing</label>
                <div id="productPicker" style="display: flex; flex-direction: column; gap: 0.5rem; max-height: 170px; overflow-y: auto; border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.5rem; background: #fafafa;">
                    Memuat produk...
                </div>
            </div>

            <div id="visitReasonWrap" style="margin-bottom: 1rem;">
                <label for="visitReasonInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">Alasan Kunjungan di Luar Rencana</label>
                <textarea id="visitReasonInput" rows="2" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem;"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
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
        await Promise.all([loadDoctors(), loadProducts()]);
        loadTodayPlans();
        loadActuals();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeActualModal();
    });

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
            <label style="display: flex; gap: 0.5rem; align-items: flex-start; background: white; border: 1px solid var(--border); border-radius: 0.25rem; padding: 0.5rem; font-size: 0.85rem;">
                <input type="checkbox" name="product_ids" value="${product.id}" style="margin-top: 0.2rem;">
                <span><strong>${escapeHtml(product.name)}</strong><br><small style="color: var(--text-muted);">${escapeHtml(product.category || '-')}</small></span>
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
            <article style="background: white; border: 1px solid var(--border); border-left: 4px solid var(--primary); border-radius: 0.5rem; padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                <div>
                    <strong>${escapeHtml(plan.doctor_name)}</strong>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">${escapeHtml(plan.doctor_specialization || 'Umum')}</div>
                    <div style="font-size: 0.8rem; color: var(--primary); font-weight: 600; margin-top: 0.4rem;">Pukul ${escapeHtml((plan.planned_time || '').substring(0, 5))} WIB</div>
                </div>
                <button type="button" class="btn btn-primary" onclick="openActualModal(${plan.id})">Mulai Kunjungan</button>
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
        grid.innerHTML = actuals.map((actual) => `
            <article style="background: white; border: 1px solid var(--border); border-left: 4px solid var(--success); border-radius: 0.5rem; padding: 1rem;">
                <div style="display: flex; justify-content: space-between; gap: 0.75rem;">
                    <strong>${escapeHtml(actual.doctor_name)}</strong>
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--success); text-transform: uppercase;">${escapeHtml(actual.visit_type)}</span>
                </div>
                <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.4rem;">${escapeHtml(actual.visit_date)}</div>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.4rem;">Produk: ${actual.products.map((product) => escapeHtml(product.name)).join(', ')}</div>
            </article>
        `).join('');
    }

    function openActualModal(planId) {
        const plan = planId ? todayPlans.find((item) => item.id === planId) : null;
        document.getElementById('actualPlanId').value = plan ? plan.id : '';
        document.getElementById('actualModalTitle').innerText = plan ? 'Catat Planned Visit' : 'Catat Kunjungan di Luar Rencana';
        document.getElementById('actualDateInput').value = todayString();
        document.getElementById('visitReasonWrap').style.display = plan ? 'none' : 'block';
        document.getElementById('actualDoctorSelect').disabled = !!plan;
        renderDoctorSelect(plan ? plan.doctor_id : '');
        renderProductPicker();
        document.getElementById('photoUrlInput').value = '';
        document.getElementById('signatureUrlInput').value = '';
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

        const productIds = Array.from(document.querySelectorAll('#productPicker input[name="product_ids"]:checked')).map((input) => parseInt(input.value));
        if (!productIds.length) {
            errorEl.innerText = 'Pilih minimal satu produk detailing.';
            errorEl.style.display = 'block';
            return;
        }

        button.disabled = true;
        button.innerText = 'Menyimpan...';

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
                    photo_url: document.getElementById('photoUrlInput').value.trim(),
                    signature_url: document.getElementById('signatureUrlInput').value.trim(),
                    products: productIds,
                    visit_reason: planId ? null : document.getElementById('visitReasonInput').value.trim()
                })
            });
            const result = await res.json();
            if (result.success) {
                closeActualModal();
                showActualAlert('Kunjungan berhasil diselesaikan.');
                loadTodayPlans();
                loadActuals();
            } else {
                errorEl.innerText = result.message || 'Gagal menyimpan kunjungan.';
                errorEl.style.display = 'block';
            }
        } catch (error) {
            errorEl.innerText = 'Terjadi kesalahan jaringan.';
            errorEl.style.display = 'block';
        } finally {
            button.disabled = false;
            button.innerText = 'Selesaikan Kunjungan';
        }
    }
</script>
@endpush
@endsection