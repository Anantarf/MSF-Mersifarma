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

function formatPeriodMonth(periodMonth) {
    if (!periodMonth) return '-';
    if (window.formatPeriodIndonesian) {
        return window.formatPeriodIndonesian(periodMonth);
    }
    const parts = String(periodMonth).split('-');
    if (parts.length < 2) return periodMonth;
    const year = parts[0];
    const monthNum = parseInt(parts[1], 10);
    const monthNames = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    return `${monthNames[monthNum - 1] || parts[1]} ${year}`;
}

function formatDateLabel(dateStr) {
    return window.formatTanggalIndonesia ? window.formatTanggalIndonesia(dateStr) : dateStr;
}

function formatDateTimeLabel(dateStr) {
    return window.formatDateTimeIndonesia ? window.formatDateTimeIndonesia(dateStr) : dateStr;
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

    const plannedTimeInput = document.getElementById('plannedTimeInput');
    plannedTimeInput?.addEventListener('input', () => window.autoFormatTimeInput(plannedTimeInput));
    plannedTimeInput?.addEventListener('blur', () => window.normalizeTime24(plannedTimeInput));
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
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        input.value = `${yyyy}-${mm}-${dd}`;
    } else if (type === 'tomorrow') {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const yyyy = tomorrow.getFullYear();
        const mm = String(tomorrow.getMonth() + 1).padStart(2, '0');
        const dd = String(tomorrow.getDate()).padStart(2, '0');
        input.value = `${yyyy}-${mm}-${dd}`;
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
        const dateStr = formatDateLabel(plan.plan_date);
        const timeStr = formatWaktuIndonesia(plan.planned_time);

        return `
            <tr>
                <td class="plan-nowrap-cell">
                    <strong class="plan-table-date">${dateStr}</strong>
                    <div class="plan-table-time">${timeStr}</div>
                </td>
                <td class="plan-nowrap-cell">
                    <strong class="plan-table-doctor">${escapeHtml(plan.doctor_name || 'Dokter')}</strong>
                </td>
                <td>
                    <div class="plan-table-specialty">${escapeHtml(plan.doctor_specialization || 'Spesialis Umum')}</div>
                    <small class="plan-help-text">${escapeHtml(plan.doctor_address || '-')}</small>
                </td>
                <td>
                    <span class="status-badge ${statusClass}">${escapeHtml(statusLabel)}</span>
                    ${isCancelled && plan.cancellation_reason ? `
                        <div class="plan-cancel-note">
                            <span class="plan-cancel-note-label">Alasan:</span> <span class="plan-cancel-note-text">"${escapeHtml(plan.cancellation_reason)}"</span>
                        </div>
                    ` : ''}
                </td>
                <td class="plan-action-cell">
                    ${plan.status === 'scheduled' ? `
                        <div class="plan-inline-actions">
                            <a href="/call-actuals?plan_id=${plan.id}&doctor_id=${plan.doctor_id}" class="btn btn-primary btn-sm plan-link-button">
                                Isi Laporan
                            </a>
                            <button type="button" class="btn btn-outline btn-sm plan-danger-outline" data-action="open-cancel-plan" data-plan-id="${plan.id}">
                                Batalkan
                            </button>
                        </div>
                    ` : (isCompleted ? `
                        <a href="/call-actuals" class="btn btn-outline btn-sm plan-secondary-action plan-link-button">
                            Lihat Laporan
                        </a>
                    ` : (isExpired ? `
                        <button type="button" class="btn btn-outline btn-sm plan-secondary-action" data-action="open-create-plan">
                            Jadwalkan Ulang
                        </button>
                    ` : `
                        <button type="button" class="btn btn-outline btn-sm plan-muted-action" data-action="open-plan-detail" data-plan-id="${plan.id}">
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
                        ${escapeHtml(formatPeriodMonth(cl.period_month))}
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
        planDateInput.disabled = true;
        dateHelp.innerText = 'Tanggal mengikuti periode target dokter.';
        const previewEl = document.getElementById('planSelectedDateText');
        if (previewEl) previewEl.style.display = 'none';
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

        // Default ke tanggal hari ini jika berada dalam bulan target, selain itu ke minDate
        const today = new Date();
        const yyyyNow = today.getFullYear();
        const mmNow = String(today.getMonth() + 1).padStart(2, '0');
        const ddNow = String(today.getDate()).padStart(2, '0');
        const todayStr = `${yyyyNow}-${mmNow}-${ddNow}`;

        if (todayStr >= minDate && todayStr <= maxDate) {
            planDateInput.value = todayStr;
        } else {
            planDateInput.value = minDate;
        }

        planDateInput.disabled = false;
        const monthName = formatPeriodMonth(periodMonth);
        dateHelp.innerText = `Pilih tanggal antara 1 s.d. ${lastDay} ${monthName}.`;
        onPlanDateChange();
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
    noticeEl.className = 'plan-availability is-loading';
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
        noticeEl.className = 'plan-availability is-available';
        noticeEl.innerHTML = 'Tersedia. Seluruh tanggal pada bulan ini masih tersedia untuk target dokter ini.';
        return;
    }

    const formattedDays = currentDoctorBusyDates.map(d => parseInt(d.split('-')[2])).join(', ');
    noticeEl.style.display = 'block';
    noticeEl.className = 'plan-availability is-busy';
    noticeEl.innerHTML = `<strong>Tanggal terisi:</strong> Tanggal ${formattedDays} sudah ada jadwal/kunjungan dokter ini. Pilih tanggal lain agar tidak bentrok.`;
}

function onPlanDateChange() {
    const planDateInput = document.getElementById('planDateInput');
    const errEl = document.getElementById('createPlanModalError');
    const btn = document.getElementById('btnSubmitPlan');
    const previewEl = document.getElementById('planSelectedDateText');
    const selectedDate = planDateInput.value;

    if (previewEl) {
        if (selectedDate) {
            previewEl.style.display = 'inline-flex';
            previewEl.innerHTML = `<i class="bi bi-calendar-check plan-date-preview-icon"></i> Tanggal terpilih: <strong>${formatDateLabel(selectedDate)}</strong>`;
        } else {
            previewEl.style.display = 'none';
        }
    }

    if (!selectedDate || !currentDoctorBusyDates.length) {
        if (errEl.innerText.includes('sudah memiliki jadwal')) {
            errEl.style.display = 'none';
        }
        btn.disabled = false;
        return;
    }

    if (currentDoctorBusyDates.includes(selectedDate)) {
        errEl.innerText = `Dokter sudah memiliki jadwal/kunjungan pada tanggal ${formatDateLabel(selectedDate)}. Silakan pilih tanggal lain.`;
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

    const planDate = (document.getElementById('planDateInput')?.value || '').trim();

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
    document.getElementById('planDetailDoctorSub').innerText = `${plan.doctor_specialization || 'Spesialis Umum'} - ${plan.doctor_address || '-'}`;

    const dateObj = new Date(plan.plan_date + 'T' + plan.planned_time);
    const dateStr = formatDateLabel(plan.plan_date);
    const timeStr = formatWaktuIndonesia(plan.planned_time);
    document.getElementById('planDetailSchedule').innerText = `${dateStr} pukul ${timeStr}`;

    document.getElementById('planDetailReason').innerText = plan.cancellation_reason || '-';

    const cancelledAtEl = document.getElementById('planDetailCancelledAt');
    const cancelledAtWrap = document.getElementById('planDetailCancelledAtWrap');
    if (plan.cancelled_at) {
        const cDate = new Date(plan.cancelled_at);
        cancelledAtEl.innerText = formatDateTimeLabel(plan.cancelled_at);
        cancelledAtWrap.style.display = 'block';
    } else {
        cancelledAtWrap.style.display = 'none';
    }

    document.getElementById('planDetailModal').style.display = 'flex';
}

function closePlanDetailModal() {
    document.getElementById('planDetailModal').style.display = 'none';
}
